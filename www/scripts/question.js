$(function() {
   var $container,
       $redirectContainer,
       currentCount, 
       currentIndex, 
       scoreOptions;

    $container = $('#responsesContainer');
    $redirectContainer = $('.submitSettingsContainer');
    currentCount = $container.children('fieldset').length;
    currentIndex = 0;
    
    scoreOptions = '';
    for(var i = -10; i <= 10; i += 1) { scoreOptions += '<option value="' + i + '"' + (i == 0 ? 'selected=""' : '') + '>' + i + '</option>'; }
    
    $('#showSubmit').click(function() {
        if($redirectContainer != null)
            $redirectContainer.removeClass('hide');
    });
    
    $('#hideSubmit').click(function() {
        if($redirectContainer != null)
            $redirectContainer.addClass('hide');
    });
    
    $('#addResponse').click(function() {
        currentCount += 1;
        currentIndex += 1;
        var html = '<fieldset class="fieldset" id="fieldset_' + currentIndex + '_n">'+
                        '<legend class="legend-title">Response #' + currentCount + '</legend>'+ 
                        '<div class="control-group">'+
                            '<label for="response_' + currentIndex + '_n" class="control-label">Response</label>'+
                            '<div class="controls"><input autocomplete="off" type="text" id="response_' + currentIndex + '_n" name="response_' + currentIndex + '_n" value=""/></div>'+
                        '</div>'+

                        '<div class="control-group">'+
                            '<label for="feedback_' + currentIndex + '_n" class="control-label">Feedback</label>'+
                            '<div class="controls"><input autocomplete="off" type="text" id="feedback_' + currentIndex + '_n" name="feedback_' + currentIndex + '_n" value=""/></div>'+
                        '</div>'+

                        '<div class="control-group">'+
                            '<label class="control-label">Correctness</label>'+
                            '<div class="controls">'+
                                '<div class="radio_extended btn-group">'+
                                    '<input autocomplete="off" id="correctness1_' + currentIndex + '" type="radio" name="correctness_' + currentIndex + '_n" value="1"/>'+
                                    '<label data-class="btn-success" class="btn" for="correctness1_' + currentIndex + '">Correct</label>'+
                                    '<input autocomplete="off" id="correctness2_' + currentIndex + '" type="radio" checked="checked" name="correctness_' + currentIndex + '_n" value="2"/> '+
                                    '<label class="btn active" for="correctness2_' + currentIndex + '">Neutral</label>'+
                                    '<input autocomplete="off" id="correctness0_' + currentIndex + '" type="radio" name="correctness_' + currentIndex + '_n" value="0" />'+
                                    '<label data-class="btn-danger" class="btn" for="correctness0_' + currentIndex + '">Incorrect</label>'+
                                '</div>'+
                            '</div>'+
                        '</div>'+

                        '<div class="control-group">'+
                            '<label for="score_' + currentIndex + '_n" class="control-label">Score</label>'+
                            '<div class="controls">'+
                                '<select id="score_' + currentIndex + '_n" name="score_' + currentIndex + '_n">' + scoreOptions + '</select>'+
                            '</div>'+
                        '</div>'+
                        '<div class="form-actions">'+
                            '<a class="btn btn-danger removeBtn" removeid="fieldset_' + currentIndex + '_n" href="#"><i class="icon-minus-sign"></i>Remove</a>'+
                        '</div>'+
                    '</fieldset>';
            
        $container.append(html);
        
        return false;
    });
    
    $('.removeBtn').live('click', function() {
        var id = $(this).attr('removeid');
        if(id != null && id.length > 0) {
            currentCount -= 1;
            $('#' + id).remove();
            RecalculateLegends($container);
        }
        
        return false; 
    });
    
    function RecalculateLegends($responsesContinaer) {
        var index = 1;
        $responsesContinaer.children('fieldset').children('legend.legend-title').each(function(i) {
            $(this).text('Response #' + index);
            index += 1;
        });
    }

    var parent;
    var id;
    var button;
    $(".radio_extended input[type=radio]").each(function(){
        if ($(this).is(':checked')){
            parent = $(this).parent('.radio_extended');
            id = $(this).attr('id');
            button = $(parent).find('label[for=' + id + ']');
            changeRadioBootstrap(button);
        }
    });

    $(".radio_extended .btn").live("click", function() {
        changeRadioBootstrap(this);
    });

    function changeRadioBootstrap(obj){
        $(obj).parent(".radio_extended").find(".btn").removeAttr('class').addClass('btn');
        $(obj).addClass('active');
        var additionClass = $(obj).attr('data-class');
        if (additionClass !== null){
            $(obj).addClass(additionClass);
        }
    }
});