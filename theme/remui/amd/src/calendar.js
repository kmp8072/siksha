 define(['jquery', 'jqueryui'], function($, jqui) {
     return {
         initialise: function($params) {

             $('.new-activity-modal').click(function() {

                 var courseid = $(this).data("id");
                 var type = 'get_add_activity_course_list';
                 console.log('hello');
                 $.ajax({
                     type: "GET",
                     async: true,
                     url: M.cfg.wwwroot + '/theme/remui/rest.php?action=' + type + '&courseid=' + courseid,
                     success: function(data) {
                         var coursename = $('.maincalendar select.cal_courses_flt option:selected').text();
                         if (data === 'has_capability') {
                             $('.modal-title').html(M.util.get_string('redirectingtocourse', 'theme_remui', coursename));
                             $('.modal-body').html('<center><i class="fa fa-3x fa-refresh fa-spin"></i></center>');
                             window.location.href = M.cfg.wwwroot + '/course/view.php?id=' + courseid + '&sesskey=' + M.cfg.sesskey + '&edit=on';
                         } else if (data === 'no_capability') {
                             $('.modal-body').html('<div class="alert alert-danger">' + M.util.get_string('nopermissiontoaddactivityincourse', 'theme_remui', coursename) + '</div>');
                         } else {
                             $('.modal-body select').empty();
                             if ($.isEmptyObject(data)) {
                                 $('.modal-body').html('<div class="alert alert-danger">' + M.util.get_string('nopermissiontoaddactivityinanycourse', 'theme_remui') + '</div>');
                                 $('#calnedar-modal-btn-add').hide();
                             } else {
                                 $('.modal-body select').append('<option value="" disabled selected>' + M.util.get_string('selectyouroption', 'theme_remui') + '</option>');
                                 $.each(data, function(index, value) {
                                     $('.modal-body select').append('<option id=' + index + '>' + value + '</option>');
                                 });
                             }
                         }
                     },
                    error: function(xhr, status, error) {
                        console.log(xhr + '\n' + error + '\n' + status);
                        $('.modal-body').html('<div class="alert alert-danger">' + error + '</div>');
                    }
                 });
             });

             $('select').on('change', function(e) {
                 var coursename = $(this).children(":selected").text();
                 $('.modal-title').html(M.util.get_string('redirectingtocourse', 'theme_remui', coursename));
                 $('.modal-body').html('<center><i class="fa fa-3x fa-refresh fa-spin"></i></center>');
                 var course_id = $(this).children(":selected").attr("id");
                 window.location.href = M.cfg.wwwroot + '/course/view.php?id=' + course_id + '&sesskey=' + M.cfg.sesskey + '&edit=on';
             });
         }
     };
 });