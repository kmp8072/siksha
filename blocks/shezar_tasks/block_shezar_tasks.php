<?PHP //$Id$
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Piers Harding <piers@catalyst.net.nz>
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage blocks_shezar_tasks
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/shezar/message/messagelib.php');

class block_shezar_tasks extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_shezar_tasks');
    }

    // Only one instance of this block is required.
    function instance_allow_multiple() {
      return false;
    }

    // Label and button values can be set in admin.
    function has_config() {
      return true;
    }

    function get_content() {
        global $CFG, $FULLME, $DB, $OUTPUT, $PAGE;

        // Cache block contents
        if ($this->content !== NULL) {
        return $this->content;
        }

        $this->content = new stdClass();
        // initialise jquery and confirm requirements
        require_once($CFG->dirroot.'/shezar/reportbuilder/lib.php');
        require_once($CFG->dirroot.'/shezar/core/js/lib/setup.php');

        $code = array();
        $code[] = shezar_JS_DIALOG;
        local_js($code);
        $PAGE->requires->js_init_call('M.shezar_message.init');

        // Just get the tasks for this user.
        $total = tm_messages_count('shezar_task', false);
        $this->msgs = tm_messages_get('shezar_task', 'timecreated DESC ', false, true);
        $count = is_array($this->msgs) ? count($this->msgs) : 0;

        $this->title = get_string('tasks', 'block_shezar_tasks');

        if (empty($this->instance)) {
            return $this->content;
        }

        $output = '';
        if (!empty($this->msgs)) {
            $output .= html_writer::tag('p', get_string('showingxofx', 'block_shezar_tasks', array('count' => $count, 'total' => $total)));
            $output .= html_writer::start_tag('ul');

            foreach ($this->msgs as $msg) {
                $output .= html_writer::start_tag('li');
                $msgmeta = $DB->get_record('message_metadata', array('messageid' => $msg->id));
                $msgacceptdata = shezar_message_eventdata($msg->id, 'onaccept', $msgmeta);
                $msgrejectdata = shezar_message_eventdata($msg->id, 'onreject', $msgmeta);
                $msginfodata = shezar_message_eventdata($msg->id, 'oninfo', $msgmeta);

                // User name + link.
                $userfrom_link = $CFG->wwwroot.'/user/view.php?id='.$msg->useridfrom;
                $from = $DB->get_record('user', array('id' => $msg->useridfrom));
                $fromname = fullname($from);

                // Message creation time.
                $when = userdate($msg->timecreated, get_string('strftimedate', 'langconfig'));

                // Statement - multipart: user + statment + object.
                $cssclass = shezar_message_cssclass($msg->msgtype);
                $msglink = !empty($msg->contexturl) ? $msg->contexturl : '';

                // Status icon.
                $output .= $OUTPUT->pix_icon('msgicons/' . $msg->icon, '', 'shezar_core',
                    array('class'=>"msgicon {$cssclass}", 'title' => format_string($msg->subject)));

                // Details.
                $text = format_string($msg->subject ? $msg->subject : $msg->fullmessage);
                if (!empty($msglink)) {
                    $url = new moodle_url($msglink);
                    $attributes = array('href' => $url);
                    $output .= html_writer::tag('a', $text, $attributes);
                } else {
                    $output .= $text;
                }

                // Info icon/dialog.
                $detailbuttons = array();
                // Add 'accept' button.
                if (!empty($msgacceptdata) && count((array)$msgacceptdata)) {
                    $btn = new stdClass();
                    $btn->text = !empty($msgacceptdata->acceptbutton) ?
                        $msgacceptdata->acceptbutton : get_string('onaccept', 'block_shezar_tasks');
                    $btn->action = "{$CFG->wwwroot}/shezar/message/accept.php?id={$msg->id}";
                    $btn->redirect = !empty($msgacceptdata->data['redirect']) ?
                        $msgacceptdata->data['redirect'] : $FULLME;
                    $detailbuttons[] = $btn;
                }
                // Add 'reject' button.
                if (!empty($msgrejectdata) && count((array)$msgrejectdata)) {
                    $btn = new stdClass();
                    $btn->text = !empty($msgrejectdata->rejectbutton) ?
                        $msgrejectdata->rejectbutton : get_string('onreject', 'block_shezar_tasks');
                    $btn->action = "{$CFG->wwwroot}/shezar/message/reject.php?id={$msg->id}";
                    $btn->redirect = !empty($msgrejectdata->data['redirect']) ?
                        $msgrejectdata->data['redirect'] : $FULLME;
                    $detailbuttons[] = $btn;
                }
                // Add 'info' button.
                if (!empty($msginfodata) && count((array)$msginfodata)) {
                    $btn = new stdClass();
                    $btn->text = !empty($msginfodata->infobutton) ?
                        $msginfodata->infobutton : get_string('oninfo', 'block_shezar_tasks');
                    $btn->action = "{$CFG->wwwroot}/shezar/message/link.php?id={$msg->id}";
                    $btn->redirect = $msginfodata->data['redirect'];
                    $detailbuttons[] = $btn;
                }
                $moreinfotext = get_string('clickformoreinfo', 'block_shezar_tasks');
                $icon = $OUTPUT->pix_icon('i/info', $moreinfotext, 'moodle', array('class'=>'msgicon', 'title' => $moreinfotext, 'alt' => $moreinfotext));
                $detailjs = shezar_message_alert_popup($msg->id, $detailbuttons, 'detailtask');
                $url = new moodle_url($msglink);
                $attributes = array('href' => $url, 'id' => 'detailtask'.$msg->id.'-dialog', 'class' => 'information');
                $output .= html_writer::tag('a', $icon, $attributes) . $detailjs;
                $output .= html_writer::end_tag('li');
            }
            $output .= html_writer::end_tag('ul');
        } elseif (!empty($CFG->block_shezar_tasks_showempty)) {
            $output = html_writer::tag('p', get_string('notasks', 'block_shezar_tasks'));
        }

        $this->content->text = $output;
        if (!empty($this->msgs)) {
            $url = new moodle_url('/shezar/message/tasks.php', array('sesskey' => sesskey()));
            $link = html_writer::link($url, get_string('viewallnot', 'block_shezar_tasks'));
            $this->content->footer = html_writer::tag('div', $link, array('class' => 'viewall'));
        }
        return $this->content;
    }
}
