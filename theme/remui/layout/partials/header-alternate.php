<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Partial - Header - Alternate
 * This layout is called only on the Admin Layout Pages.
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $DB, $USER;
$logoorsitename = get_config('theme_remui', 'logoorsitename');
$siteicon = get_config('theme_remui', 'siteicon');
$checklogo = $PAGE->theme->setting_file_url('logo', 'logo');
if (!empty($checklogo)) {
    $logo = $PAGE->theme->setting_file_url('logo', 'logo');
} else {
    $logo = $CFG->wwwroot.'/theme/remui/pix/logo.png';
}

// login popup
$loginpopup = get_config('theme_remui', 'navlogin_popup');

// get current user info
if (isloggedin() && !isguestuser()) {

    // get license data from license controller
    $lcontroller = new \theme_remui\controller\license_controller();
    $getlidatafromdb = $lcontroller->getDataFromDb();

    $userfullname = fullname($USER);
    $userpicture = \theme_remui\controller\theme_controller::get_user_image_link($USER->id, 100);


    // Header messages
    if (!empty($CFG->messaging)) {
        $recentmessages = message_get_recent_conversations($USER, 0, 5);

        $conversations = array();
        $totalunreadcount = 0;
        $realunreadcount = 0;
        // get overall conversations
        foreach ($recentmessages as $recentmessage) {

            $otheruser = $DB->get_record('user', array('id' => $recentmessage->id));
            $totalunreadcount = message_count_unread_messages($USER, $otheruser);

            $convuids = key($recentmessage);
            $convuids = $recentmessage->$convuids;
            $convuidsarray = explode("-", $convuids);

            if ($totalunreadcount and $USER->id != $convuidsarray[0]) {
                $realunreadcount++;
                $conversations[$recentmessage->id]['unread'] = 'unread';
            } else {
                $conversations[$recentmessage->id]['unread'] = '';
            }
            $conversations[$recentmessage->id]['otheruserid'] = $recentmessage->id;
            $conversations[$recentmessage->id]['otheruser'] = $recentmessage->firstname . " " . $recentmessage->lastname;
            $conversations[$recentmessage->id]['otheruserimage'] = \theme_remui\controller\
                theme_controller::get_user_image_link($recentmessage->id, 20);
            $conversations[$recentmessage->id]['smallmessage'] = $recentmessage->smallmessage;

            $timecreated = time() - $recentmessage->timecreated;

            $conversations[$recentmessage->id]['timecreated'] = get_string('ago', 'message', \theme_remui\controller\
                theme_controller::get_time_format($timecreated));
        }
        // User is redirected to this link when clicks on See all messages.
        $seeallmessageslink = new moodle_url('/message/index.php?viewing=recentconversations');


        // Header notifications
        $recentnotifications = message_get_recent_notifications($USER, 0, 5);
        $seeallnotificationslink = new moodle_url('/message/index.php?viewing=recentnotifications');
    }


    // Header events
    $eventslist = \theme_remui\controller\theme_controller::get_events();
    $seealleventslink = new moodle_url('/calendar/view.php?view=upcoming');


    $userprofileurl = new moodle_url('/user/profile.php', array('id' => $USER->id));
    $userdashboardurl = new moodle_url('/my');
    $userlogouturl = new moodle_url('/login/logout.php', array('sesskey' => sesskey(), 'alt' => 'logout'));
    $coursearchive = new moodle_url('/course/index.php');
    $preferences = new moodle_url('/user/preferences.php');
} else {
    $userloginurl = new moodle_url('/login/index.php', array('alt' => get_string('login')));
    $forgotpasswordurl = new moodle_url('/login/forgot_password.php');
    $userregisterurl = new moodle_url('/login/signup.php', array('alt' => get_string('startsignup')));
}

$isregistration = $DB->get_record('config', array('name' => 'registerauth'));
?>

<!-- Main Header -->
<header class="main-header">
    <!-- logo -->
    <?php
        
        if ($logoorsitename == 'logo') { ?>
            <a href="<?php echo $CFG->wwwroot; ?>" class="logo">
              <div style="background-image: url(<?php echo $logo;?>);
                    background-position: center; height:50px; background-size: contain; background-repeat: no-repeat;">
              </div>
            </a>
        <?php }
        else if ($logoorsitename == 'sitename') { ?>
            <a class="logo" href="<?php echo $CFG->wwwroot; ?>">
              <span class="logo-lg">
                <?php echo format_string($SITE->shortname); ?>
              </span>
            </a>
        <?php }
        else {  ?>
            <a class="logo" href="<?php echo $CFG->wwwroot; ?>">
              <span class="logo-lg">
                  <i class="fa fa-<?php echo $siteicon; ?>"></i>
                  <?php echo format_string($SITE->shortname); ?>
              </span>
            </a>
        <?php }
    ?>

  <!-- Header Navbar -->
  <nav class="navbar navbar-static-top" role="navigation">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
      <span class="sr-only">Toggle navigation</span>
    </a>
    <!-- render custom menu -->
    <div class="pull-left hidden-xs mdl-custom-menu">
     <div class="shabmenu2">
     <?php $hasshezarmenu = false;
$shezarmenu = '';
if (empty($PAGE->layout_options['nocustommenu'])) {
    // load shezar menu
    $menudata = shezar_build_menu();
    $shezar_core_renderer = $PAGE->get_renderer('shezar_core');
    $shezarmenu = $shezar_core_renderer->shezar_menu($menudata);
    $hasshezarmenu = !empty($shezarmenu);
}

if($hasshezarmenu) { ?>
                <div id="shezarmenu" class="nav-collapse"><?php echo $shezarmenu; ?></div>
            <?php } ?>
        <?php echo $OUTPUT->custom_menu(); ?>
       </div>
    </div>
	
    <!-- Navbar Right Menu -->
    <div class="navbar-custom-menu">
    
      <ul class="nav navbar-nav">
      <div class="shabmenu1">
     <li class= "dropdown shab-menu">
      <a href="#" class="toggle-right" data-toggle="dropdown">
      <div></div>

    </a>
 <ul class="dropdown-menu">

              <li>

                <!-- inner menu: contains the messages -->
                <ul class="menu">

                  <li>
    <div id="shezarmenu" class="nav-collapse collapsed-menu"><?php echo $shezarmenu; ?></div>
                    </li>
    
                </ul><!-- /.menu -->

              </li>
             
            </ul>    </li>
            </div>
      <?php
          if ($CFG->version / 1000000 >= 2016 ) {
              echo "<li>";
              echo $OUTPUT->search_box();
              echo "</li>";
          }
      ?>
      
      <!-- section to be shown for logged in users -->
      <?php if (isloggedin() && !isguestuser()) {


    if (!empty($CFG->messaging)) {
?>
          <!-- Messages: style can be found in dropdown.less-->
          <li class="dropdown messages-menu">
            <!-- Menu toggle button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Messages">
              <i class="fa fa-envelope-o"></i>
              <span class="label label-success"><?php
        if ($realunreadcount) {
            echo $realunreadcount;
        } else {
            echo '';
        }
?></span>
  </a>
  <ul class="dropdown-menu">

<?php
        if ($realunreadcount) {
        ?>
        <li class="header"><?php echo get_string('youhavemessages', 'theme_remui', $realunreadcount); ?></li>
        <?php
        } else {
        ?>
        <li class="header"><?php echo get_string('youhavenomessages', 'theme_remui'); ?></li>
        <?php
        }
?>

              <li>

                <!-- inner menu: contains the messages -->
                <ul class="menu">
<?php

        foreach ($conversations as $key => $conversation) { ?>
                  <li>
    <?php
                    $viewfullmessage = new moodle_url('/message/index.php?user1=' . $USER->id .
                      '&user2=' . $conversation['otheruserid']);
    ?>
                      <a href='<?php echo $viewfullmessage; ?>' class='<?php echo $conversation['unread']; ?>'>

                        <!-- User Image -->
                        <div class="pull-left">
                          <img src="<?php echo \theme_remui\controller\theme_controller::get_user_image_link($key, 20); ?>"
                          class="img-circle" alt="User Image">
                        </div>

                        <!-- Message title and timestamp -->
                        <h4>
                            <?php echo $conversation['otheruser']; ?>

                          <small><i class="fa fa-clock-o"></i>&nbsp;<?php echo $conversation['timecreated']; ?></small>
                        </h4>

                        <!-- The message -->
                        <p><?php echo substr(strip_tags($conversation['smallmessage'], '<br>'), 0, 50).'...'; ?></p>
                        <!-- end message -->

                      </a>
                    </li>
    <?php
        }
?>
                </ul><!-- /.menu -->

              </li>
              <li class="footer"><a href="<?php echo $seeallmessageslink; ?>"><?php echo get_string('seeallmessages', 'theme_remui'); ?></a></li>
            </ul>
          </li><!-- /.messages-menu -->

          <!-- Notifications Menu -->
          <li class="dropdown notifications-menu">
            <!-- Menu toggle button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Notifications">
              <i class="fa fa-bell-o"></i>
              <!-- <span class="label label-warning"><?php echo count($recentnotifications); ?></span> -->
            </a>
            <ul class="dropdown-menu">
                <?php
        if (count($recentnotifications)) {
        ?>
      <li class="header"><?php echo get_string('youhavenotifications', 'theme_remui', count($recentnotifications)); ?></li>
        <?php
        } else {
        ?>
      <li class="header"><?php echo get_string('youhavenonotifications', 'theme_remui'); ?></li>
        <?php
        }
?>
              <li>
                <!-- Inner Menu: contains the notifications -->
                <ul class="menu">
                <?php
        foreach ($recentnotifications as $recentnotification) { ?>
      <li><!-- start notification -->
        <a href="<?php echo $seeallnotificationslink; ?>">
<i class="fa fa-users text-aqua"></i>
<?php echo $recentnotification->fullmessage; ?>
        </a>
      </li><!-- end notification -->
        <?php
        }
                ?>
                </ul>
              </li>
              <li class="footer"><a href="<?php echo $seeallnotificationslink; ?>"><?php echo get_string('viewallnotifications', 'theme_remui'); ?></a></li>
            </ul>
          </li>
<?php
    }
?>
          <!-- Events Menu -->
          <li class="dropdown tasks-menu">
            <!-- Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" title="Upcoming Events">
              <i class="fa fa-flag-o"></i>
              <span class="label label-danger"><?php
    if (count($eventslist)) {
        echo count($eventslist);
    }
    ?></span>
            </a>
            <ul class="dropdown-menu">
    <?php
    if (count($eventslist)) {
        ?><li class="header"><?php echo get_string('youhaveupcomingevents', 'theme_remui', count($eventslist)); ?></li>
        <?php
    } else {
        ?><li class="header"><?php echo get_string('youhavenoupcomingevents', 'theme_remui'); ?></li>
        <?php
    }
    ?>
              <li>
                <!-- Inner menu: contains the tasks -->
                <ul class="menu">
    <?php
    foreach ($eventslist as $event) { ?>
                  <li><!-- Task item -->
                    <a href="<?php echo $seealleventslink; ?>">
                      <!-- Task title and progress text -->
                      <h3>
                        <?php echo $event->name; ?>
                        <small class="pull-right">
        <?php
        if ($event->timestart < time()) {
            echo get_string('startedsince', 'theme_remui') . " " . \theme_remui\controller\theme_controller::get_time_format(time() - $event->timestart);
        } else {
            echo get_string('startingin', 'theme_remui') . " " . \theme_remui\controller\theme_controller::get_time_format($event->timestart - time());
        }
        ?>
                        </small>
                        <br />
                        <small class="pull-right">
        <?php
        if ($event->timeduration) {
            echo get_string('duration', 'search') . " : " . \theme_remui\controller\theme_controller::get_time_format($event->timeduration);
        }
        ?>
                        </small>
                      </h3>

                    </a>
                  </li><!-- end task item -->
    <?php
    }
    ?>
                </ul>
              </li>
              <li class="footer">
                <a href="<?php echo $seealleventslink; ?>"><?php echo get_string('viewallupcomingevents', 'theme_remui'); ?></a>
              </li>
            </ul>
          </li>
          <!-- User Account Menu -->
          <li class="dropdown user user-menu">
            <!-- Menu Toggle Button -->
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
              <!-- The user image in the navbar-->
              <img src=" <?php echo $userpicture; ?>" class="user-image" alt="<?php echo get_string('userimage', 'theme_remui'); ?>">
              <!-- hidden-xs hides the username on small devices so only the image appears. -->
              <span class="hidden-xs"><?php echo $userfullname; ?></span>
            </a>
            <ul class="dropdown-menu">
              <!-- The user image in the menu -->
              <li class="user-header">
                <img src=" <?php echo $userpicture; ?>" class="img-circle" alt="<?php echo get_string('userimage', 'theme_remui'); ?>">
                <p>
                    <?php echo $userfullname; ?>
                </p>
              </li>
              <!-- Menu Body -->
              <li class="user-body">
                <div class="col-xs-4 text-center">
                  <a href="<?php echo $userdashboardurl ?>"><?php echo get_string('myhome');?></a>
                </div>
                <div class="col-xs-4 text-center">
                  <a href="<?php echo $coursearchive ?>"><?php echo get_string('courses');?></a>
                </div>
                <div class="col-xs-4 text-center">
                  <a href="<?php echo $preferences ?>"><?php echo get_string('preferences');?></a>
                </div>
              </li>
              <!-- Menu Footer-->
              <li class="user-footer">
                <div class="pull-left">
                  <a href="<?php echo $userprofileurl; ?>" class="btn btn-default btn-flat"><?php echo get_string('profile'); ?></a>
                </div>
                <div class="pull-right">
                  <a href="<?php echo $userlogouturl; ?>" class="btn btn-default btn-flat"><?php echo get_string('logout'); ?></a>
                </div>
              </li>
            </ul>
          </li>
        <?php } else { ?>
          <!-- User login panel -->
          <?php if($loginpopup) { ?>
            <li class="dropdown user user-menu login-menu">
              <!-- Menu Toggle Button -->
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                <i class="fa fa-sign-in"></i>&nbsp;<?php echo get_string('login'); ?>
              </a>

              <ul class="dropdown-menu">
                <!-- Menu Body -->
                <li class="box-body">
                  <form class="login-form" method="post" action="<?php echo $CFG->wwwroot; ?>/login/index.php?authldap_skipntlmsso=1">
                    
                    <div class="input-group form-group">
                      <span class="input-group-addon bg-gray"><i class="fa fa-user text-muted"></i>&nbsp;</span>
                      <input type="text" class="form-control" id="username" name="username" placeholder="Username">
                    </div>

                    <div class="input-group form-group">
                      <span class="input-group-addon bg-gray"><i class="fa fa-key text-muted"></i></span>
                      <input type="password" name="password" id="password" class="form-control" placeholder="Password">
                    </div>

                    <div class="form-group">
                      <div class="checkbox">
                        <label class="text-muted">
                          <input type="checkbox" name="rememberusername" id="rememberusername" value="1" style="margin: 4px -19px 0px;"> Remember Me
                        </label>
                      </div>
                    </div>
                </li>

                <!-- Menu Footer-->
                <li class="box-footer">
                    <div class="pull-left">
                      <a href="<?php echo $forgotpasswordurl; ?>" class="btn text-black"><?php echo 'Forgot Password?' ?></a>
                    </div>
                    <div class="pull-right">
                      <input type="submit" class="btn btn-default btn-flat" id="submit" name="submit" value="<?php echo get_string('login'); ?>"/>
                    </div>
                    </form>
                </li>
              </ul>
            </li>
            <?php } else { ?>
              <li>
                <a href="<?php echo $CFG->wwwroot.'/login'; ?>"><i class="fa fa-sign-in"></i>&nbsp;<?php  echo get_string('login'); ?></a>
              </li>
            <?php } ?>


        <?php if ($isregistration->value == 'email') {
                ?>
          <li>
            <a href=" <?php echo $CFG->wwwroot.'/login/signup.php'; ?>"><i class="fa fa-user"></i>&nbsp;<?php  echo get_string('startsignup'); ?></a>
          </li>
            <?php }
} ?>

        <!-- Control/Post Sidebar Toggle Button -->
<?php

$iconclass = 'fa-arrow-left';
if (get_config('theme_remui', 'rightsidebarslide') == 1) {
    $slide = false;
    $iconclass = 'fa-arrow-right';

    if($PAGE->pagetype == 'site-index' && !isloggedin()) {
      $iconclass = 'fa-arrow-left';
    }
} else {
    $slide = true;
}
?>
      </ul>
    </div>
  </nav>
 
</header>
<!-- </header> -->

<?php if (isloggedin() && !isguestuser()) { ?>
<?php if ('available' != $getlidatafromdb) { ?>
  <!-- l nag -->
  <div class="alert alert-danger text-center license-nag">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <?php if (is_siteadmin()) {
            echo get_string('licensenotactiveadmin', 'theme_remui');
} else {
    echo get_string('licensenotactive', 'theme_remui');
}
        ?>
  </div>
<?php }
}
if(is_siteadmin()){


?>
<script type="text/javascript">
    // $("#inst8 > div.content.block-content >ul.block_tree list >li").append('<li><a href="/user/messages"><span class="tab">Message Center</span></a></li>');
  $(document).ready(function(){
    $('#inst8 > div.content ').append($('<ul style="list-style-type: square; font-size:18px;margin-left: 24px;margin-top: -3px;"><li><a href="http://139.59.7.236/nucleus/enrol/nomination/manage.php"><span style="margin-left: -4px;font-size: 14px;">Manage enrolment request</span></a> </li></ul>'));
  });
</script>


<?php
}
?>
<script>
$(document).ready(function(){
	$('.toggle-right').click(function(){
		$(".dropdown .shab-menu").toggleClass("open");	
		});
		
	});
</script>