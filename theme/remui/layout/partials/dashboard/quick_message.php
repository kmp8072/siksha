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
 * Dashboard - Quick Message
 *
 * @package   theme_remui
 * @copyright Copyright (c) 2016 WisdmLabs
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

if (isguestuser()) {
    redirect($CFG->wwwroot);
}

// get and set box state
user_preference_allow_ajax_update("quickmessage", PARAM_TEXT);
$box_state = json_decode(get_user_preferences("quickmessage", 0));

?>
<div id="quick_message">
<?php
if (!empty($CFG->messaging)) {

    // Preparing quick message form.
    require_once("$CFG->libdir/formslib.php");

    class quick_message_form extends moodleform {
        // Add elements to form
        public function definition() {
            // echo "<pre>"; print_r($this->_customdata); echo "</pre>";
            $quickmessageform = $this->_form; // Don't forget the underscore!

            if ($this->_customdata) {
                $this->_customdata = array(0 => get_string('selectacontact', 'theme_remui')) + $this->_customdata;
                $quickmessageform->addElement('select', 'comboboxcontacts', null, $this->_customdata);
                $textareaattributes = array('class' => 'textarea',
                                        'placeholder' => 'Message',
                                        'wrap' => 'virtual',
                                        'rows' => '10',
                                        'cols' => '10'
                                        );
                $quickmessageform->addElement('textarea', 'messagebody', '', $textareaattributes);
                $buttonattributes = array('class' => 'pull-right');
                // $quickmessageform->addElement('html', '<div class="box-footer clearfix">');
                $quickmessageform->addElement('button', 'submitbutton', get_string('sendmessage', 'theme_remui'), $buttonattributes);
                // $quickmessageform->addElement('html', '</div>');
            } else {
                global $OUTPUT;
                echo $OUTPUT->notification(get_string('yourcontactlisistempty', 'theme_remui'), 'notifymessage');
            }
        }
    }

    $usercontactsobj = message_get_contacts($USER);
    $contactlist = array();
    foreach ($usercontactsobj[1] as $key => $usercontact) {
        $contactlist[$usercontact->id] = $usercontact->firstname . " " . $usercontact->lastname;
    }
    ?>

    <div class="box<?php echo ($box_state)?' collapsed-box':'';?>" data-name="quickmessage">
    <div class="box-header ui-sortable-handle">
      <i class="fa fa-envelope"  aria-hidden="true"></i>
      <h3 class="box-title"><?php echo get_string('quickmessage', 'theme_remui'); ?></h3>
      <!-- tools box -->
      <div class="pull-right box-tools">
            <?php if ( $this->page->user_is_editing()) { ?>
       <button class="btn btn-box-tool" ><i class="fa fa-arrows"></i></button>
        <?php } ?>
        <button class="btn btn-box-tool" data-widget="collapse"><i class="<?php echo ($box_state)?'fa fa-plus':'fa fa-minus'; ?>"></i></button>
      </div><!-- /. tools -->
    </div>
    <div class="box-body"  <?php echo ($box_state)?'style="display:none;"':'';?>>
      <form action="#" method="post">
    <?php
        // require_once($CFG->dirroot.'/theme/remui/layout/partials/dashboard/simplehtml_form.php');
        $quickmessageform = new quick_message_form(null, $contactlist);
        $quickmessageform->display();
        // User is redirected to this link when clicks on See all messages.
        $seeallmessageslink = new moodle_url('/message/index.php?viewing=recentconversations');
            ?>
        <div class="box-footer text-center">
            <a target='_blank' href="<?php echo $seeallmessageslink; ?>" class="uppercase"><?php echo get_string('viewallmessages', 'theme_remui') ?></a>
        </div>
        <input type="hidden" id="id_user" name="id_user" value="<?php echo $USER->id; ?>" />
        </form>
        <div class="alert" id="message" style="margin-top: 10px;"></div>
      </div>
    </div>
<?php
}
?>
</div>
