//* jshint ignore:start */
define(['jquery', 'theme_remui/Chart', 'theme_remui/select2', 'jqueryui', 'theme_remui/app'], function($, Chart, select2, jqueryui, AdminLTE) {
    return {
        initialise: function($params) {
            var piechartdata = {};
            var chart = null;
            var barChart = null;
            var legendtemplatestr1 = "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%>";
            var legendtemplatestr2 = "<span style=\"background-color:<%=segments[i].fillColor%>\"></span>";
            var legendtemplatestr3 = "<%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>";

            var pieOptions = {
                //Boolean - Whether we should show a stroke on each segment
                segmentShowStroke: true,
                //String - The colour of each segment stroke
                segmentStrokeColor: "#fff",
                //Number - The width of each segment stroke
                segmentStrokeWidth: 1,
                //Number - The percentage of the chart that we cut out of the middle
                percentageInnerCutout: 50, // This is 0 for Pie charts
                //Number - Amount of animation steps
                animationSteps: 100,
                //String - Animation easing effect
                animationEasing: "easeOutBounce",
                //Boolean - Whether we animate the rotation of the Doughnut
                animateRotate: true,
                //Boolean - Whether we animate scaling the Doughnut from the centre
                animateScale: false,
                //Boolean - whether to make the chart responsive to window resizing
                responsive: true,
                // Boolean - whether to maintain the starting aspect ratio or not when responsive,
                // if set to false, will take up entire container
                maintainAspectRatio: true,
                //String - A legend template
                legendTemplate: legendtemplatestr1 + legendtemplatestr2 + legendtemplatestr3,
                //String - A tooltip template
                tooltipTemplate: "<%=value %> <%=label%> users"
            };


            function render_pie_chart() {

                if (chart !== null) {
                    chart.destroy();
                }

                var pieChartCanvas = $("#pieChart").get(0).getContext("2d");

                chart = new Chart(pieChartCanvas).Doughnut(piechartdata, pieOptions);
            }


            function createpiechart() {

                var category_id = $('#coursecategorylist option:selected').data('id');
                $.ajax({
                    type: "GET",
                    async: true,
                    url: M.cfg.wwwroot + '/theme/remui/rest.php?action=get_courses_by_category&categoryid=' + category_id,
                    success: function(data) {
                        if (data[0] === undefined) {
                            $('canvas#pieChart').hide();
                            $('.enroll-stats-nouserserror').hide();
                            $('.chart-legend').hide();
                            $('.enroll-stats-error').show();
                        } else {
                            if (data.totalusercount === 0) {
                                $('canvas#pieChart').hide();
                                $('.enroll-stats-error').hide();
                                $('.chart-legend').hide();
                                $('.enroll-stats-nouserserror').show();
                            } else {
                                // delete data['totalusercount'];
                                $('.enroll-stats-error').hide();
                                $('.enroll-stats-nouserserror').hide();
                                $('.chart-legend').show();
                                $('canvas#pieChart').show();
                                var PieData = [];

                                var colors = ['#2196f3', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#ffeb3b',
                                    '#ff9800', '#f44336', '#9c27b0', '#673ab7', '#3f51b5'
                                ];
                                var i = 0;
                                $('#enrolled_users_stats .chart-legend').empty();
                                if (data.length > 11) {
                                    var count = 0;
                                    for (var j = 10; j < data.length; j++) {
                                        count += data[j].count;
                                    }
                                    data.splice(11, data.length - 1);
                                    data[10].shortname = 'Other Courses';
                                    data[10].fullname = 'Other Courses';
                                    data[10].count = count;
                                }
                                $.each(data, function(index, value) {
                                    $('#enrolled_users_stats .chart-legend').append('<li><i class="fa fa-circle-o" style="color: ' +
                                        colors[i] + '"></i> ' + value.shortname + '</li>');
                                    PieData.push({
                                        value: value.count,
                                        color: colors[i],
                                        highlight: colors[i],
                                        label: value.shortname
                                    });
                                    i++;
                                });

                                piechartdata = PieData;

                                render_pie_chart();
                            }

                        }
                    },
                    error: function(xhr, status, error) {
                        $('canvas#pieChart').hide();
                        $('.enroll-stats-error').show();
                    }
                });
            }
            // update pie chart on category selection

            if ($('#enrolled_users_stats select').length) {
                $('#enrolled_users_stats select#coursecategorylist').on('change', function() {
                    createpiechart();
                });
                createpiechart();
            }

            //-----------------
            //- END PIE CHART -
            //-----------------


            /* Bar Chart */

            function createBarChart() {
                var course_id = $('#quiz-course-list option:selected').data('id');
                $.ajax({
                    type: "GET",
                    async: true,
                    url: M.cfg.wwwroot + '/theme/remui/rest.php?action=get_courses_for_quiz&courseid=' + course_id,
                    success: function(data) {
                        if (data.datasets === undefined) {
                            $('div#quiz-chart-area').hide();
                            $('.quiz-stats-error').show();
                        } else {
                            var context = $("#barChart").get(0).getContext("2d");
                            if (barChart !== null) {
                                barChart.destroy();
                            }
                            barChart = new Chart(context).Bar(data, {
                                responsive: true,
                                maintainAspectRatio: true
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        $('div#quiz-chart-area').hide();
                        $('.quiz-stats-error').show();
                    }
                });
            }

            if ($('#barChart').length) {
                $('#quiz_stats select#quiz-course-list').on('change', function() {
                    createBarChart();
                });
                createBarChart();
            }
            /* End Bar Chart */

            // console.log($params); //user to check whats in the $params
            //Make the dashboard widgets sortable Using jquery UI
            $("#col7,#col5").sortable({
                placeholder: "sort-highlight",
                connectWith: "#col5,#col7",
                handle: ".box-header, .nav-tabs",
                forcePlaceholderSize: true,
                zIndex: 999999,
                items: "> div",
                stop: function() {
                    
                    var order7 = $('#col7').sortable('toArray');
                    var order5 = $('#col5').sortable('toArray');
                    M.util.set_user_preference("layout_7", JSON.stringify(order7));
                    M.util.set_user_preference("layout_5", JSON.stringify(order5));
                    
                    $('#col7').prepend($('#default_layout7_element')); // making it to the first element of layout7
                    $('#col5').prepend($('#default_layout5_element')); // making it to the first element of layout5
                }
            });
            $("#col7 > * , #col5 > *").css("cursor", "move");
            
            //jQuery UI sortable for the todo list
            $(".todo-list").sortable({
                placeholder: "sort-highlight",
                handle: ".handle",
                forcePlaceholderSize: true,
                zIndex: 999999
            });

            /* Quick Message Block */
            if ($('div#quick_message select#id_comboboxcontacts').length) {
                //$('div#quick_message select#id_comboboxcontacts option:first-child').attr('disabled', true);
                $('div#quick_message div#fitem_id_comboboxcontacts div.fitemtitle').remove();
                $('#id_submitbutton').click(function(event) {
                    event.preventDefault();
                    $('#message').removeClass('alert-success').addClass('alert-info').html(M.util.get_string("sendingmessage", "theme_remui")).show();
                    var userid = $('#id_user').val();
                    var contactid = $('#id_comboboxcontacts').val();
                    var message = $.trim($('#id_messagebody').val());
                    var type = 'quickmessage';
                    if (contactid === '0') {
                        $('#message').removeClass('alert-info').addClass('alert-warning').html(M.util.get_string("selectcontact",
                            "theme_remui")).show();
                        return false;
                    }
                    if (message === '') {
                        $('#message').removeClass('alert-info').addClass('alert-warning').html(M.util.get_string("entermessage",
                            "theme_remui")).show();
                    } else {
                        //sendmessage('quickmessage', contactid, message);
                        $.ajax({
                            type: "GET",
                            async: true,
                            url: M.cfg.wwwroot + '/theme/remui/rest.php?action=send_' + type + '&contactid=' + contactid +
                                '&message=' + message + '&contextid=' + $params,
                            success: function(data) {
                                if ($.trim(data.html) === 'success') {
                                    $('#id_messagebody').val('');
                                    $('#id_messagebody').attr('placeholder',
                                        (M.util.get_string("sendmoremessage", "theme_remui")));
                                    $('#message').removeClass().addClass('alert alert-success').html(M.util.get_string(
                                            "messagesent", "theme_remui") +
                                        "<a class='pull-right' style='text-decoration: none; font-weight:bold;' target='_blank' href='" +
                                        M.cfg.wwwroot + "/message/index.php?user1=" + userid + "&user2=" +
                                        contactid + "'>View conversation</a>").show();
                                } else {
                                    $('#message').removeClass().addClass('alert alert-danger').html(M.util.get_string(
                                        "messagenotsent", "theme_remui")).show();
                                }
                                // window.sessionStorage[cache_key] = data.html;
                                // Note: we can't use .data because that does not manipulate the dom, we need the data
                                // attribute populated immediately so things like behat can utilise it.
                                // .data just sets the value in memory, not the dom.
                                //$(container).attr('data-content-loaded', '1');
                                //$(container).html(data.html);
                            },
                            error: function(xhr, status, error) {
                                $('#message').removeClass().addClass('alert-danger').html(M.util.get_string(
                                    "messagenotsenterror", "theme_remui") + " <br />" + error).show();
                            }
                        });
                    }
                    return false;
                });
            }


            /* Add Notes Block */
            if ($('.add-notes-select').length) {
                $('.add-notes-select select').select2();
                $('.add-notes-select select option:first-child').attr('disabled', true);

                var course_id, student_count, user_id, course_name;

                $('.add-notes-select select').on('change', function() {
                    $('.add-notes-button a').hide();
                    course_id = $(this).children(":selected").attr("id");
                    course_name = $(this).children(":selected").text();
                    if (course_id === undefined) {
                        $('.select2-studentlist').empty();
                        $('.select2-studentlist').select2({
                            placeholder: {
                                "id": "-1",
                                "text": M.util.get_string("selectcoursetodisplayusers", "theme_remui")
                            },
                        });
                        return;
                    }
                    var type = 'userlist';
                    //$('.select2-studentlist').css('display', 'block');
                    $.ajax({
                        type: "GET",
                        async: true,
                        url: M.cfg.wwwroot + '/theme/remui/rest.php?action=get_' + type + '&courseid=' + course_id,
                        success: function(data) {
                            student_count = Object.keys(data).length;
                            $('.select2-studentlist').empty();
                            if (student_count) {
                                $('.select2-studentlist').append('<option>' + M.util.get_string(
                                        "selectastudent", "theme_remui") + ' (' + M.util.get_string("total", "theme_remui") +
                                    ': ' + student_count + ')</option>');
                                $('.select2-studentlist').select2({
                                    placeholder: {
                                        "id": "-1",
                                        "text": M.util.get_string("selectastudent", "theme_remui") + " (" +
                                            M.util.get_string("total", "theme_remui") + ": " + student_count + ")"
                                    },
                                });
                            } else {
                                $('.select2-studentlist').append(M.util.get_string("nousersenrolledincourse",
                                    "theme_remui", course_name));
                                $('.select2-studentlist').select2({
                                    placeholder: {
                                        "id": "-1",
                                        "text": M.util.get_string("nousersenrolledincourse", "theme_remui", course_name)
                                    },
                                });
                            }

                            $.each(data, function(index, value) {

                                $('.select2-studentlist').append('<option value="' + index + '">' + value.firstname + " " +
                                    value.lastname + '</option>');
                            });
                            data = "";
                        },
                        error: function(xhr, status, error) {
                            $('.select2-studentlist').html('<option>' + error + '</option>');
                        }
                    });
                });
                $('.select2-studentlist').select2({
                    placeholder: {
                        "id": "-1",
                        "text": M.util.get_string("selectcoursetodisplayusers", "theme_remui")
                    },
                });

                $('.select2-studentlist').on('change', function() {
                    $('.add-notes-button a').show();
                    user_id = $('.select2-studentlist').select2('data')[0].id;
                    var notes_link = M.cfg.wwwroot + '/notes/edit.php?courseid=' + course_id +
                        '&userid=' + user_id + '&publishstate=site';
                    $('.add-notes-button .site-note').attr('href', notes_link);
                    notes_link = M.cfg.wwwroot + '/notes/edit.php?courseid=' + course_id +
                        '&userid=' + user_id + '&publishstate=public';
                    $('.add-notes-button .course-note').attr('href', notes_link);
                    notes_link = M.cfg.wwwroot + '/notes/edit.php?courseid=' + course_id +
                        '&userid=' + user_id + '&publishstate=draft';
                    $('.add-notes-button .personal-note').attr('href', notes_link);
                });

            }
            $('[aria-selected="false"]').attr('aria-selected', 'true');

        }
    };
});