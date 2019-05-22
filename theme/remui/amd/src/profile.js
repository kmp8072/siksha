/* jshint ignore:start */
define(['jquery', 'jqueryui'], function($, jqui) {
    return {
        initialise: function($params) {

            // Add / Remove / Block / Unblock Contacts Functionality
            $('.widget-user-2 .btn-block').click(function(e) {
                e.preventDefault();
                $(this).find('.fa-refresh').show();

                var type = $(this).attr('data-action');
                var otheruserid = $(this).attr('data-id');
                $.ajax({
                    type: "GET",
                    async: true,
                    url: M.cfg.wwwroot + '/theme/remui/rest.php?action=set_contact&type=' + type + '&otheruserid=' + otheruserid,
                    success: function(data) {
                        $('.widget-user-2 .btn-block .fa-refresh').hide();
                        if (!data) {
                            $('.widget-user-2 #add-contacts-error').show();
                            $('.widget-user-2 #add-contacts-error').html('<center><i class="icon fa fa-warning"></i>' + M.util.get_string('actioncouldnotbeperformed', 'theme_remui'));
                        } else if (type === 'add') {
                            $('.widget-user-2 .btn-block[data-action="add"]').removeClass('btn-primary');
                            $('.widget-user-2 .btn-block[data-action="add"]').addClass('btn-warning');
                            $('.widget-user-2 .btn-block[data-action="add"]').text(M.util.get_string('removefromcontacts', 'theme_remui'));
                            $('.widget-user-2 .btn-block[data-action="add"]').css("font-weight", "Bold");
                            $('.widget-user-2 .btn-block[data-action="add"]').attr('data-action', 'remove');

                            $('.widget-user-2 .btn-block[data-action="unblock"]').removeClass('btn-success');
                            $('.widget-user-2 .btn-block[data-action="unblock"]').addClass('btn-danger');
                            $('.widget-user-2 .btn-block[data-action="unblock"]').text(M.util.get_string('block', 'theme_remui'));
                            $('.widget-user-2 .btn-block[data-action="unblock"]').css("font-weight", "Bold");
                            $('.widget-user-2 .btn-block[data-action="unblock"]').attr('data-action', 'block');

                        } else if (type === 'remove') {
                            $('.widget-user-2 .btn-block[data-action="remove"]').removeClass('btn-warning');
                            $('.widget-user-2 .btn-block[data-action="remove"]').addClass('btn-primary');
                            $('.widget-user-2 .btn-block[data-action="remove"]').text(M.util.get_string('addtocontacts', 'theme_remui'));
                            $('.widget-user-2 .btn-block[data-action="remove"]').css("font-weight", "Bold");
                            $('.widget-user-2 .btn-block[data-action="remove"]').attr('data-action', 'add');

                        } else if (type === 'block') {
                            $('.widget-user-2 .btn-block[data-action="block"]').removeClass('btn-danger');
                            $('.widget-user-2 .btn-block[data-action="block"]').addClass('btn-success');
                            $('.widget-user-2 .btn-block[data-action="block"]').text(M.util.get_string('removeblock', 'theme_remui'));
                            $('.widget-user-2 .btn-block[data-action="block"]').css("font-weight", "Bold");
                            $('.widget-user-2 .btn-block[data-action="block"]').attr('data-action', 'unblock');

                            $('.widget-user-2 .btn-block[data-action="remove"]').removeClass('btn-warning');
                            $('.widget-user-2 .btn-block[data-action="remove"]').addClass('btn-primary');
                            $('.widget-user-2 .btn-block[data-action="remove"]').text(M.util.get_string('addtocontacts', 'theme_remui'));
                            $('.widget-user-2 .btn-block[data-action="remove"]').css("font-weight", "Bold");
                            $('.widget-user-2 .btn-block[data-action="remove"]').attr('data-action', 'add');

                        } else if (type === 'unblock') {
                            $('.widget-user-2 .btn-block[data-action="unblock"]').removeClass('btn-success');
                            $('.widget-user-2 .btn-block[data-action="unblock"]').addClass('btn-danger');
                            $('.widget-user-2 .btn-block[data-action="unblock"]').text(M.util.get_string('block', 'theme_remui'));
                            $('.widget-user-2 .btn-block[data-action="unblock"]').css("font-weight", "Bold");
                            $('.widget-user-2 .btn-block[data-action="unblock"]').attr('data-action', 'block');

                            $('.widget-user-2 .btn-block[data-action="add"]').removeClass('btn-primary');
                            $('.widget-user-2 .btn-block[data-action="add"]').addClass('btn-warning');
                            $('.widget-user-2 .btn-block[data-action="add"]').text(M.util.get_string('removefromcontacts', 'theme_remui'));
                            $('.widget-user-2 .btn-block[data-action="add"]').css("font-weight", "Bold");
                            $('.widget-user-2 .btn-block[data-action="add"]').attr('data-action', 'remove');
                        }
                    }
                });
            });

            // Edit Profile Page
            $('#page-user-profile .form-horizontal #btn-save-changes').click(function() {
                var fname = $('#inputfName').val();
                var lname = $('#inputlName').val();
                var emailid = $('#inputEmail').val();
                var description = $('textarea#inputDescription').val();
                var city = $('#inputCity').val();
                var country = $('#page-user-profile .form-horizontal #select-country option:selected').val();
                var countryname = $('#page-user-profile .form-horizontal #select-country option:selected').text();
                if (fname === '') {
                    $('div#error-message').show();
                    $('div#error-message').removeClass('alert-success').addClass('alert-danger');
                    $('div#error-message').html(M.util.get_string('enterfirstname', 'theme_remui'));
                    $('#inputfName').focus();
                    return false;
                }
                if (lname === '') {
                    $('div#error-message').show();
                    $('div#error-message').removeClass('alert-success').addClass('alert-danger');
                    $('div#error-message').html(M.util.get_string('enterlastname', 'theme_remui'));
                    $('#inputlName').focus();
                    return false;
                }
                if (emailid === '') {
                    $('div#error-message').show();
                    $('div#error-message').removeClass('alert-success').addClass('alert-danger');
                    $('div#error-message').html(M.util.get_string('enteremailid', 'theme_remui'));
                    $('#inputEmail').focus();
                    return false;
                }
                // Validate email text
                var regEx = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-Z0-9]{2,4}$/;
                if (!regEx.test(emailid)) {
                    $('div#error-message').show();
                    $('div#error-message').removeClass('alert-success').addClass('alert-danger');
                    $('div#error-message').html(M.util.get_string('enterproperemailid', 'theme_remui'));
                    $('#inputEmail').focus();
                    return false;
                }
                emailid = encodeURIComponent(emailid);
                if (country === M.util.get_string('selectcountry', 'theme_remui')) {
                    countryname = '';
                    country = '';
                }
                $.ajax({
                    type: "GET",
                    async: true,
                    url: M.cfg.wwwroot + '/theme/remui/rest.php?action=save_user_profile_settings&fname=' + fname + '&lname=' + lname + '&emailid=' + emailid + '&description=' + description + '&city=' + city + '&country=' + country,
                    success: function() {
                        $('div#error-message').show();
                        $('div#error-message').removeClass('alert-danger').addClass('alert-success').html(M.util.get_string('detailssavedsuccessfully', 'theme_remui'));
                        $('.main-header .user-menu a span').text(fname + " " + lname);
                        $('section.content-header h1').text(fname + " " + lname);
                        $('section.content h3.widget-user-username').text(fname + " " + lname);
                        $('.main-header .user-menu .dropdown-menu .user-header p').text(fname + " " + lname);

                        if (city) {
                            var location;
                            $('div#user-location').html('<hr><strong><i class="fa fa-map-marker margin-r-5"></i>' + M.util.get_string('location', 'theme_remui') + '</strong>');
                            location = city;

                            if (countryname) {
                                location = city + ', ' + countryname;
                            }
                            $('div#user-location').append('<p class="text-muted">' + location + '</p>');
                        } else if (countryname) {
                            $('div#user-location').html('<hr><strong><i class="fa fa-map-marker margin-r-5"></i>' + M.util.get_string('location', 'theme_remui') + '</strong>');
                            $('div#user-location').append('<p class="text-muted">' + countryname + '</p>');
                        } else {
                            $('div#user-location').empty();
                        }

                        if (description) {
                            $('div#user-description').html('<hr><strong><i class="fa fa-file-text-o margin-r-5"></i>' + M.util.get_string('description', 'theme_remui') + '</strong>');
                            $('div#user-description').append('<p>' + description + '</p>');
                        } else {
                            $('div#user-description').empty();
                        }
                    },
                     error: function(requestObject, error, errorThrown) {
                        alert(error);
                        alert(errorThrown);
                    }     
                });
            });
        }
    };
});
/* jshint ignore:end */