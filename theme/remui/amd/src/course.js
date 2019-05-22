 define(['jquery', 'jqueryui'], function($, jqui) {
     return {
         initialise: function(Params) {

             $('[data-toggle=tooltip]').tooltip();
             
             $('.material-button-toggle').click(function() {
                 
                 var element = $(this).parent().siblings('.course-content ul li.section ul.section');

                 if(!$(element).hasClass('open')){
                    
                    // show content
                    $(element).slideDown();
                    $(element).addClass('open');
                    
                    // change button state
                    $(this).find('span').text(M.util.get_string('hidesection', 'theme_remui'));
                    $(this).find('i').removeClass('fa-angle-down');
                    $(this).find('i').addClass('fa-angle-up');

                 } else if($(element).hasClass('open')){
                    
                    // hide content
                    $(element).slideUp();
                    $(element).removeClass('open');
                    
                    // change button state
                    $(this).find('span').text(M.util.get_string('showsection', 'theme_remui'));
                    $(this).find('i').removeClass('fa-angle-up');
                    $(this).find('i').addClass('fa-angle-down');

                 }
             });

             $('.toggle-section-btn').click(function() {
                 if(!$('.course-content ul li.section ul.section').hasClass('active')){
                    
                    // show content
                    $('.course-content ul li.section ul.section').slideDown();
                    $('.course-content ul li.section ul.section').addClass('active');
                    $('.course-content ul li.section ul.section').addClass('open');
                    
                    // change button state
                    $(this).find('span').text(M.util.get_string('hidesections', 'theme_remui'));
                    $(this).find('i').removeClass('fa-angle-down');
                    $(this).find('i').addClass('fa-angle-up');

                    // change button state
                    $('.material-button-toggle').find('span').text(M.util.get_string('hidesection', 'theme_remui'));
                    $('.material-button-toggle').find('i').removeClass('fa-angle-down');
                    $('.material-button-toggle').find('i').addClass('fa-angle-up');

                 } else if($('.course-content ul li.section ul.section').hasClass('active')){
                    
                    // hide content
                    $('.course-content ul li.section ul.section').slideUp();
                    $('.course-content ul li.section ul.section').removeClass('active');
                    $('.course-content ul li.section ul.section').removeClass('open');
                    
                    // change button state
                    $(this).find('span').text(M.util.get_string('showsections', 'theme_remui'));
                    $(this).find('i').removeClass('fa-angle-up');
                    $(this).find('i').addClass('fa-angle-down');

                    // change button state
                    $('.material-button-toggle').find('span').text(M.util.get_string('showsection', 'theme_remui'));
                    $('.material-button-toggle').find('i').removeClass('fa-angle-up');
                    $('.material-button-toggle').find('i').addClass('fa-angle-down');
                 }
             });
         }
     };
 });