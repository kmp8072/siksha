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
 * This theme has been deprecated.
 * We strongly recommend basing all new themes on roots and basis.
 * This theme will be removed from core in a future release at which point
 * it will no longer receive updates from shezar.
 *
 * @deprecated since shezar 9
 * @author Brian Barnes <brian.barnes@shezarlms.com>
 * @package theme
 * @subpackage customshezarresponsive
 */

/**
 * Settings for the customshezarresponsive theme
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Favicon file setting.
    $name = 'theme_customshezarresponsive/favicon';
    $title = new lang_string('favicon', 'theme_customshezarresponsive');
    $description = new lang_string('favicondesc', 'theme_customshezarresponsive');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon', 0, array('accepted_types' => '.ico'));
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo file setting.
    $name = 'theme_customshezarresponsive/logo';
    $title = new lang_string('logo', 'theme_customshezarresponsive');
    $description = new lang_string('logodesc', 'theme_customshezarresponsive');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo alt text.
    $name = 'theme_customshezarresponsive/alttext';
    $title = new lang_string('alttext', 'theme_customshezarresponsive');
    $description = new lang_string('alttextdesc', 'theme_customshezarresponsive');
    $setting = new admin_setting_configtext($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Site text color
    $name = 'theme_customshezarresponsive/textcolor';
    $title = get_string('textcolor', 'theme_customshezarresponsive');
    $description = get_string('textcolor_desc', 'theme_customshezarresponsive');
    $default = '#333366';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, null, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Link colour setting.
    $name = 'theme_customshezarresponsive/linkcolor';
    $title = new lang_string('linkcolor', 'theme_customshezarresponsive');
    $description = new lang_string('linkcolordesc', 'theme_customshezarresponsive');
    $default = '#087BB1';
    $previewconfig = array('selector' => 'a', 'style' => 'color');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    //Link visited colour setting.
    $name = 'theme_customshezarresponsive/linkvisitedcolor';
    $title = new lang_string('linkvisitedcolor', 'theme_customshezarresponsive');
    $description = new lang_string('linkvisitedcolordesc', 'theme_customshezarresponsive');
    $default = '#087BB1';
    $previewconfig = array('selector' => 'a:visited', 'style' => 'color');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Button colour setting.
    $name = 'theme_customshezarresponsive/buttoncolor';
    $title = new lang_string('buttoncolor','theme_customshezarresponsive');
    $description = new lang_string('buttoncolordesc', 'theme_customshezarresponsive');
    $default = '#E6E6E6';
    $previewconfig = array('selector'=>'input[\'type=submit\']]', 'style'=>'background-color');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Site content background color.
    $name = 'theme_customshezarresponsive/bodybackground';
    $title = get_string('bodybackground', 'theme_customshezarresponsive');
    $description = get_string('bodybackground_desc', 'theme_customshezarresponsive');
    $default = '#FFFFFF';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, null, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background image setting.
    $name = 'theme_customshezarresponsive/backgroundimage';
    $title = get_string('backgroundimage', 'theme_customshezarresponsive');
    $description = get_string('backgroundimage_desc', 'theme_customshezarresponsive');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'backgroundimage');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background repeat setting.
    $name = 'theme_customshezarresponsive/backgroundrepeat';
    $title = get_string('backgroundrepeat', 'theme_customshezarresponsive');
    $description = get_string('backgroundrepeat_desc', 'theme_customshezarresponsive');;
    $default = 'repeat';
    $choices = array(
        '0' => get_string('default'),
        'repeat' => get_string('backgroundrepeatrepeat', 'theme_customshezarresponsive'),
        'repeat-x' => get_string('backgroundrepeatrepeatx', 'theme_customshezarresponsive'),
        'repeat-y' => get_string('backgroundrepeatrepeaty', 'theme_customshezarresponsive'),
        'no-repeat' => get_string('backgroundrepeatnorepeat', 'theme_customshezarresponsive'),
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background position setting.
    $name = 'theme_customshezarresponsive/backgroundposition';
    $title = get_string('backgroundposition', 'theme_customshezarresponsive');
    $description = get_string('backgroundposition_desc', 'theme_customshezarresponsive');
    $default = '0';
    $choices = array(
        '0' => get_string('default'),
        'left_top' => get_string('backgroundpositionlefttop', 'theme_customshezarresponsive'),
        'left_center' => get_string('backgroundpositionleftcenter', 'theme_customshezarresponsive'),
        'left_bottom' => get_string('backgroundpositionleftbottom', 'theme_customshezarresponsive'),
        'right_top' => get_string('backgroundpositionrighttop', 'theme_customshezarresponsive'),
        'right_center' => get_string('backgroundpositionrightcenter', 'theme_customshezarresponsive'),
        'right_bottom' => get_string('backgroundpositionrightbottom', 'theme_customshezarresponsive'),
        'center_top' => get_string('backgroundpositioncentertop', 'theme_customshezarresponsive'),
        'center_center' => get_string('backgroundpositioncentercenter', 'theme_customshezarresponsive'),
        'center_bottom' => get_string('backgroundpositioncenterbottom', 'theme_customshezarresponsive'),
    );
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Background fixed setting.
    $name = 'theme_customshezarresponsive/backgroundfixed';
    $title = get_string('backgroundfixed', 'theme_customshezarresponsive');
    $description = get_string('backgroundfixed_desc', 'theme_customshezarresponsive');
    $setting = new admin_setting_configcheckbox($name, $title, $description, 0);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Main content background color.
    $name = 'theme_customshezarresponsive/contentbackground';
    $title = get_string('contentbackground', 'theme_customshezarresponsive');
    $description = get_string('contentbackground_desc', 'theme_customshezarresponsive');
    $default = '#FFFFFF';
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, null, false);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Page header background colour setting.
    $name = 'theme_customshezarresponsive/headerbgc';
    $title = new lang_string('headerbgc', 'theme_customshezarresponsive');
    $description = new lang_string('headerbgcdesc', 'theme_customshezarresponsive');
    $default = '#F5F5F5';
    $previewconfig = array('selector' => '#page-header', 'style' => 'backgroundColor');
    $setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Footnote setting.
    $name = 'theme_customshezarresponsive/footnote';
    $title = get_string('footnote', 'theme_customshezarresponsive');
    $description = get_string('footnotedesc', 'theme_customshezarresponsive');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Custom CSS file.
    $name = 'theme_customshezarresponsive/customcss';
    $title = new lang_string('customcss','theme_customshezarresponsive');
    $description = new lang_string('customcssdesc', 'theme_customshezarresponsive');
    $setting = new admin_setting_configtextarea($name, $title, $description, '');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
