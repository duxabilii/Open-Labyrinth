<?php
/**
 * Open Labyrinth [ http://www.openlabyrinth.ca ]
 *
 * Open Labyrinth is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Open Labyrinth is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Open Labyrinth.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @copyright Copyright 2012 Open Labyrinth. All Rights Reserved.
 *
 */
defined('SYSPATH') or die('No direct script access.');

class Updates
{
    public static function update(){
        $result = 0;
        $dir = DOCROOT.'updates/';
        if(is_dir($dir)){

            $infoFile = $dir.'history.json';
            $alreadyUpdated = array();
            if (!file_exists($infoFile)){

                if (!is_writable($dir)){
                    return 3;
                }

                $executedFiles = array();

            } else {
                $executedFiles = self::getListOfExecutedFiles($infoFile);
                $alreadyUpdated = self::getAlreadyUpdatedVersions($executedFiles);
            }

            $filesForUpdate = self::getFilesForUpdate($dir);

            if (count($filesForUpdate) > 0) {
                $lastVersion = end($filesForUpdate);
                $lastUpdatedVersion = end($alreadyUpdated);

                if (!empty($lastUpdatedVersion)) {
                    $resultA = self::sortVersionInOrderPregReplace($lastVersion);
                    $resultB = self::sortVersionInOrderPregReplace($lastUpdatedVersion);

                    if ($resultB > $resultA){
                        $rollbackResult = self::rollback($lastVersion);
                        $executedFiles = self::getListOfExecutedFiles($infoFile);
                    }
                }

                foreach ($filesForUpdate as $f) {
                    $pathToFile = $dir . $f;
                    if (!isset($executedFiles[$f])) {
                        Updates::populateDatabase($pathToFile);
                        $executedFiles[$f] = 1;
                        $result = 1;
                    }
                }

                file_put_contents($infoFile, json_encode($executedFiles));
            }else{
                //roll back to the earliest version
                if(!empty($alreadyUpdated)) {
                    $rollbackToVersion = reset($alreadyUpdated);
                    $rollbackToVersion = substr($rollbackToVersion, 0, strlen($rollbackToVersion) - 4); //cut .sql
                }else{
                    $rollbackToVersion = 'v3.0.0';
                }
                $rollbackResult = self::rollback($rollbackToVersion);
            }

            if(isset($rollbackResult)){
                switch($rollbackResult){
                    case 0:
                        $result = 4;
                        break;
                    case 1:
                        $result = 5;
                        break;
                    case 2:
                        $result = 6;
                        break;
                }
            }
        } else {
            return 2;
        }

        return $result;
    }

    public static function getListOfExecutedFiles($infoFile)
    {
        $fileString = file_get_contents($infoFile);
        return json_decode($fileString, true);
    }

    public static function getAlreadyUpdatedVersions($executedFiles)
    {
        $alreadyUpdated = array();
        if(!empty($executedFiles)) {
            foreach($executedFiles as $version => $v) {
                $alreadyUpdated[] = $version;
            }
            usort($alreadyUpdated, array('Updates', 'sortVersionInOrder'));
        }

        return $alreadyUpdated;
    }

    public static function getFilesForUpdate($dir)
    {
        $files = scandir($dir);

        if (count($files) > 0) {
            foreach ($files as $k => $f) {
                $ext = pathinfo($f, PATHINFO_EXTENSION);
                if (in_array($f, array('.','..')) || $ext != 'sql') {
                    unset($files[$k]);
                }
            }

            if(count($files) > 0){
                usort($files, array('Updates', 'sortVersionInOrder'));
            }
        }

        return $files;
    }

    public static function rollback($toVersion){
        $result = 0;
        $dir = DOCROOT.'updates/roll_back/';
        if(is_dir($dir)){
            $files = scandir($dir);
            array_shift($files);
            array_shift($files);
            if (count($files) > 0){
                $historyPath = DOCROOT.'updates/history.json';
                if(file_exists($historyPath)){
                    $history = file_get_contents($historyPath);
                    $history = json_decode($history, true);
                }
                usort($files, array('Updates', 'sortVersionInOrder'));
                $files = array_reverse($files);
                foreach($files as $f){
                    $ext = pathinfo($f, PATHINFO_EXTENSION);
                    if ($ext == 'sql'){
                        $pathToFile = $dir.$f;
                        Updates::populateDatabase($pathToFile);
                        $result = 1;

                        if(isset($history[$f])) {
                            unset($history[$f]);
                        }

                        $version = pathinfo($f, PATHINFO_FILENAME);
                        if($version == $toVersion) break;
                    }
                }
                if(isset($history)) {
                    $history = json_encode($history);
                    file_put_contents($historyPath, $history);
                }
            }
        } else {
            return 2;
        }

        return $result;
    }

    public static function sortVersionInOrder($a, $b) {
        $ext = pathinfo($a, PATHINFO_EXTENSION);
        if ($ext != 'sql'){
            return -1;
        }

        $ext = pathinfo($b, PATHINFO_EXTENSION);
        if ($ext != 'sql'){
            return 1;
        }


        $resultA = self::sortVersionInOrderPregReplace($a);
        $resultB = self::sortVersionInOrderPregReplace($b);

        if ($resultA == $resultB) {
            return 0;
        }

        return ($resultA-$resultB > 0) ? 1 : -1;
    }

    public static function sortVersionInOrderPregReplace($str) {
        $result = '';
        $regExp = '/(?<=v)(.*?)(?=\.sql)/is';
        $regExpDot = '/(\.)/e';

        if ($c=preg_match_all ($regExp, $str, $matches)) {
            if (isset($matches[0][0])) {
                $found = 0;
                //TODO: preg_replace(): The /e modifier is deprecated, use preg_replace_callback instead
                $result = @self::replaceSpecialChar(preg_replace($regExpDot, '$found++ ? \'\' : \'$1\'', $matches[0][0]));
            }
        }

        return $result;
    }

    public static function replaceSpecialChar($str) {
        $array = explode('_', $str);
        $resultStr = $array[0];
        if (isset($array[1])) {
            $length = strlen($array[1]);
            for($i = 0; $i < 3 - $length; $i++) {
                $resultStr .= '0';
            }

            $resultStr .= $array[1];
        }
        return $resultStr;
    }

    public static function populateDatabase($schema)
    {
        $return = true;

        // Get the contents of the schema file.
        if (!($buffer = file_get_contents($schema)))
        {
            return false;
        }

        // Get an array of queries from the schema and process them.
        $queries = Updates::_splitQueries($buffer);
        if (count($queries) > 0){
            $db = Database::instance('default');
            $db->connect();

            foreach ($queries as $query)
            {
                // Trim any whitespace.
                $query = trim($query);

                // If the query isn't empty and is not a MySQL or PostgreSQL comment, execute it.
                if (!empty($query) && ($query{0} != '#') && ($query{0} != '-'))
                {
                    // Execute the query.

                    $result = @mysql_query($query);

                    if (!$result){
                        $return = false;
                    }
                }
            }
            $db->disconnect();
        }

        return $return;
    }

    public static function _splitQueries($sql)
    {
        $buffer    = array();
        $queries   = array();
        $in_string = false;

        // Trim any whitespace.
        $sql = trim($sql);

        // Remove comment lines.
        $sql = preg_replace("/\n\#[^\n]*/", '', "\n" . $sql);

        // Remove PostgreSQL comment lines.
        $sql = preg_replace("/\n\--[^\n]*/", '', "\n" . $sql);

        // find function
        $funct = explode('CREATE OR REPLACE FUNCTION', $sql);
        // save sql before function and parse it
        $sql = $funct[0];

        // Parse the schema file to break up queries.
        for ($i = 0; $i < strlen($sql) - 1; $i++)
        {
            if ($sql[$i] == ";" && !$in_string)
            {
                $queries[] = substr($sql, 0, $i);
                $sql = substr($sql, $i + 1);
                $i = 0;
            }

            if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\")
            {
                $in_string = false;
            }
            elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset ($buffer[0]) || $buffer[0] != "\\"))
            {
                $in_string = $sql[$i];
            }
            if (isset ($buffer[1]))
            {
                $buffer[0] = $buffer[1];
            }
            $buffer[1] = $sql[$i];
        }

        // If the is anything left over, add it to the queries.
        if (!empty($sql))
        {
            $queries[] = $sql;
        }

        // add function part as is
        for ($f = 1; $f < count($funct); $f++)
        {
            $queries[] = 'CREATE OR REPLACE FUNCTION ' . $funct[$f];
        }

        return $queries;
    }
}