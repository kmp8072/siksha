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
 * shezar navigation edit page.
 *
 * @package    shezar
 * @subpackage navigation
 * @author     Chris Wharton <chris.wharton@catalyst-eu.net>
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/shezar/core/menu/rules_form.php');
require_once($CFG->dirroot . '/shezar/core/js/lib/setup.php');
require_once($CFG->dirroot . '/shezar/cohort/lib.php');

// Item id.
$id = required_param('id', PARAM_INT);

admin_externalpage_setup('shezarnavigation');

$context = \context_system::instance();
$PAGE->set_context($context);
$renderer = $PAGE->get_renderer('shezar_core');

$url = new moodle_url('/shezar/core/menu/rules.php', array('id' => $id));
$redirecturl = new moodle_url('/shezar/core/menu/index.php');

$item = \shezar_core\shezar\menu\menu::get($id);
$property = $item->get_property();
$node = \shezar_core\shezar\menu\menu::node_instance($property);

// Set up JS.
local_js(array(
    shezar_JS_UI,
    shezar_JS_DIALOG,
    shezar_JS_TREEVIEW
));

$PAGE->requires->strings_for_js(array('menucohortsvisible'), 'shezar_cohort');
$jsmodule = array(
    'name' => 'shezar_restrictcohort',
    'fullpath' => '/shezar/core/menu/rules.js',
    'requires' => array('json')
);

$visibleselected = $item->get_setting('audience_access', 'active_audiences');
$args = array('args'=>'{"visibleselected":"' . $visibleselected . '", "type":"menu", "instancetype":"' .
    COHORT_ASSN_ITEMTYPE_MENU . '", "instanceid":"' . $id . '"}');
$PAGE->requires->js_init_call('M.shezar_restrictcohort.init', $args, true, $jsmodule);
unset($visibleselected);

$customdata = array(
    'item' => $item,
);

$mform = new rules_form(null, $customdata);
if ($mform->is_cancelled()) {
    redirect($redirecturl);
}
if ($data = $mform->get_data()) {
    try {

        $roleenable = !empty($data->role_enable);
        $enableaudience = !empty($data->audience_enable);
        $enablepreset = !empty($data->preset_enable);

        $settings = array(
            array('type' => 'visibility_restriction', 'name' => 'item_visibility', 'value' => $data->item_visibility),
            array('type' => 'role_access', 'name' => 'enable', 'value' => $roleenable),
            array('type' => 'audience_access', 'name' => 'enable', 'value' => $enableaudience),
            array('type' => 'preset_access', 'name' => 'enable', 'value' => $enablepreset)
        );

        $associations = array();

        // Restrict by role.
        if ($roleenable) {
            $settings[] = array(
                'type' => 'role_access',
                'name' => 'aggregation',
                'value' => $data->role_aggregation
            );
            if (isset($data->role_context)) {
                $settings[] = array(
                    'type' => 'role_access',
                    'name' => 'context',
                    'value' => $data->role_context
                );
            }
            if (isset($data->role_activeroles)) {
                $activeroles = array();
                foreach ($data->role_activeroles as $roleid => $setting) {
                    if ($setting == 1) {
                        $activeroles[] = $roleid;
                    }
                }
                // Implode into string and update setting.
                $settings[] = array(
                    'type' => 'role_access',
                    'name' => 'active_roles',
                    'value' => implode('|', $activeroles)
                );
            }
        }

        // Restrict by audience.
        if ($enableaudience) {
            $settings[] = array(
                'type' => 'audience_access',
                'name' => 'aggregation',
                'value' => $data->audience_aggregation
            );
            if (isset($data->cohortsvisible)) {
                // Build an array of settings from the submitted data.
                $cohortsvisible = explode(',', $data->cohortsvisible);
                $cohortids = $DB->get_fieldset_select('cohort', 'id', 'active = 1 AND broken = 0');
                $activeaudiences = array();
                foreach ($cohortids as $cohortid) {
                    if (in_array($cohortid, $cohortsvisible)) {
                        $activeaudiences[$cohortid] = true;
                        $associations[] = array($cohortid, $item->id, COHORT_ASSN_ITEMTYPE_MENU, COHORT_ASSN_VALUE_PERMITTED);
                    }
                }
                $settings[] = array(
                    'type' => 'audience_access',
                    'name' => 'active_audiences',
                    'value' => implode(',', array_keys($activeaudiences))
                );
            }
        }

        // Restrict by preset rules.
        if ($enablepreset) {
            $settings[] = array(
                'type' => 'preset_access',
                'name' => 'aggregation',
                'value' => $data->preset_aggregation
            );
            if (isset($data->preset_active_presets)) {
                $activepresets = array_filter($data->preset_active_presets);
                // Implode into string and update setting.
                $settings[] = array(
                    'type' => 'preset_access',
                    'name' => 'active_presets',
                    'value' => implode(',', array_keys($activepresets))
                );
            }
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('cohort_visibility', array('instanceid' => $item->id, 'instancetype' => COHORT_ASSN_ITEMTYPE_MENU));
        foreach ($associations as $association) {
            call_user_func_array('shezar_cohort_add_association', $association);
        }
        $item->update_settings($settings);
        $transaction->allow_commit();

        \shezar_core\event\menuitem_updated::create_from_item($data->id)->trigger();

        shezar_set_notification(get_string('menuitem:updateaccesssuccess', 'shezar_core'), $url, array('class' => 'notifysuccess'));
    } catch (moodle_exception $e) {
        shezar_set_notification($e->getMessage());
    }
}

$PAGE->set_url($url);
$title = ($id ? get_string('menuitem:editingx', 'shezar_core', $node->get_title()) : get_string('menuitem:addnew', 'shezar_core'));
$PAGE->set_title($title);
$PAGE->navbar->add($title, $url);
$PAGE->set_heading($title);

// Display page header.
echo $renderer->header();
echo $renderer->heading($title);

// Set up tabs for access controls and detail editing.
echo $renderer->shezar_menu_tabs('rules', $item);

// Warning if custom visibility not in use.
if ($item->visibility != \shezar_core\shezar\menu\menu::SHOW_CUSTOM) {
    echo $renderer->notification(get_string('menuitem:accessnotenabled', 'shezar_core'), 'notifynotice');
}

echo $mform->display();
echo $renderer->footer();
