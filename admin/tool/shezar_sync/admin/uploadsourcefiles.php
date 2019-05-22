<?php
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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package shezar
 * @subpackage shezar_sync
 */

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/admin/tool/shezar_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/shezar_sync/admin/forms.php');

admin_externalpage_setup('uploadsyncfiles');

$form = new shezar_sync_source_files_form();

/// Process actions
$elements = shezar_sync_get_elements($onlyenabled=true);
$systemcontext = context_system::instance();
$can_upload_any = false;
foreach ($elements as $e) {
    $elementname = $e->get_name();
    if (has_capability('tool/shezar_sync:upload' . $elementname, $systemcontext)) {
        $can_upload_any = true;
        break;
    }
}

if ($data = $form->get_data()) {

    $fileaccess = get_config('shezar_sync', 'fileaccess');
    if ($fileaccess == FILE_ACCESS_UPLOAD) {
        $fs = get_file_storage();
        $readyfiles = array();
        foreach ($elements as $e) {
            $elementname = $e->get_name();
            if (!has_capability('tool/shezar_sync:upload' . $elementname, $systemcontext)) {
                continue;
            }

            //delete any existing uploaded files
            $fs->delete_area_files($systemcontext->id, 'shezar_sync', $elementname);

            //save draftfile to file directory
            if (isset($data->$elementname)) {
                $draftid = $data->$elementname;
                file_save_draft_area_files($draftid, $systemcontext->id, 'shezar_sync', $elementname, $draftid, array('subdirs' => true));
                set_config('sync_'.$elementname.'_itemid', $draftid, 'shezar_sync');
            } else {
                continue;
            }

            //delete the draftfile - at this point it should be safe to assume $USER is the uploader
            $draft_context = context_user::instance($USER->id);
            $fs->delete_area_files($draft_context->id, 'user', 'draft', $draftid);
        }
   }

    shezar_set_notification(get_string('uploadsuccess', 'tool_shezar_sync'), $FULLME,
        array('class'=>'notifysuccess'));
}


/// Output
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadsyncfiles', 'tool_shezar_sync'));

if (get_config('shezar_sync', 'fileaccess') == FILE_ACCESS_DIRECTORY) {
    $link = html_writer::link(new moodle_url('admin/tool/shezar_sync/admin/elements.php', null), get_string('uploadaccessdeniedlink', 'tool_shezar_sync'));
    print_string('uploadaccessdenied', 'tool_shezar_sync', $link);
} else if ($can_upload_any) {
    $form->display();
} else {
    // @todo error message
}

echo $OUTPUT->footer();
