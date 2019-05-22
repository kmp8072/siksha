<?php

if ($hassiteconfig) { // speedup for non-admins, add all caps used on this page

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableoutcomes', new lang_string('enableoutcomes', 'grades'), new lang_string('enableoutcomes_help', 'grades'), 0));
    $optionalsubsystems->add(new admin_setting_configcheckbox('usecomments', new lang_string('enablecomments', 'admin'), new lang_string('configenablecomments', 'admin'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('usetags', new lang_string('usetags','admin'),new lang_string('configusetags', 'admin'), '1'));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablenotes', new lang_string('enablenotes', 'notes'), new lang_string('configenablenotes', 'notes'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableportfolios', new lang_string('enabled', 'portfolio'), new lang_string('enableddesc', 'portfolio'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablewebservices', new lang_string('enablewebservices', 'admin'), new lang_string('configenablewebservices', 'admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messaging', new lang_string('messaging', 'admin'), new lang_string('configmessaging','admin'), 1));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messaginghidereadnotifications', new lang_string('messaginghidereadnotifications', 'admin'), new lang_string('configmessaginghidereadnotifications','admin'), 0));

    $options = array(DAYSECS=>new lang_string('secondstotime86400'), WEEKSECS=>new lang_string('secondstotime604800'), 2620800=>new lang_string('nummonths', 'moodle', 1), 15724800=>new lang_string('nummonths', 'moodle', 6),0=>new lang_string('never'));
    $optionalsubsystems->add(new admin_setting_configselect('messagingdeletereadnotificationsdelay', new lang_string('messagingdeletereadnotificationsdelay', 'admin'), new lang_string('configmessagingdeletereadnotificationsdelay', 'admin'), 604800, $options));

    $optionalsubsystems->add(new admin_setting_configcheckbox('messagingallowemailoverride', new lang_string('messagingallowemailoverride', 'admin'), new lang_string('configmessagingallowemailoverride','admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablestats', new lang_string('enablestats', 'admin'), new lang_string('configenablestats', 'admin'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablerssfeeds', new lang_string('enablerssfeeds', 'admin'), new lang_string('configenablerssfeeds', 'admin'), 0));

    // shezar: blogs are disabled in shezar by default since 2.9.2.
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableblogs', new lang_string('enableblogs', 'admin'), new lang_string('configenableblogs', 'admin'), 0));

    $options = array('off'=>new lang_string('off', 'mnet'), 'strict'=>new lang_string('on', 'mnet'));
    $optionalsubsystems->add(new admin_setting_configselect('mnet_dispatcher_mode', new lang_string('net', 'mnet'), new lang_string('configmnet', 'mnet'), 'off', $options));

    // Conditional activities: completion and availability
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecompletion',
        new lang_string('enablecompletion','completion'),
        new lang_string('configenablecompletion','completion'), 1));

    $options = array(
        1 => get_string('completionactivitydefault', 'completion'),
        0 => get_string('completion_none', 'completion')
    );
    $optionalsubsystems->add(new admin_setting_configselect('completiondefault', new lang_string('completiondefault', 'completion'),
            new lang_string('configcompletiondefault', 'completion'), 1, $options));

    $optionalsubsystems->add($checkbox = new admin_setting_configcheckbox('enableavailability',
            new lang_string('enableavailability', 'availability'),
            new lang_string('enableavailability_desc', 'availability'), 0));
    $checkbox->set_affects_modinfo(true);

    // Course RPL
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablecourserpl', new lang_string('enablecourserpl', 'completion'), new lang_string('configenablecourserpl', 'completion'), 1));

    // Module RPLs
    // Get module list
    if ($modules = $DB->get_records("modules")) {
        // Default to all
        $defaultmodules = array();

        foreach ($modules as $module) {
            $strmodulename = new lang_string("modulename", "$module->name");
            // Deal with modules which are lacking the language string
            if ($strmodulename == '[[modulename]]') {
                $strmodulename = $module->name;
            }
            $modulebyname[$module->id] = $strmodulename;
            $defaultmodules[$module->id] = 1;
        }
        asort($modulebyname, SORT_LOCALE_STRING);

        $optionalsubsystems->add(new admin_setting_configmulticheckbox(
                        'enablemodulerpl',
                        new lang_string('enablemodulerpl', 'completion'),
                        new lang_string('configenablemodulerpl', 'completion'),
                        $defaultmodules,
                        $modulebyname
        ));
    }

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableplagiarism', new lang_string('enableplagiarism','plagiarism'), new lang_string('configenableplagiarism','plagiarism'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enablebadges', new lang_string('enablebadges', 'badges'), new lang_string('configenablebadges', 'badges'), 1));

    // Report caching and global restrictions.
    $optionalsubsystems->add(new admin_setting_configcheckbox('enablereportcaching', new lang_string('enablereportcaching','shezar_reportbuilder'), new lang_string('configenablereportcaching','shezar_reportbuilder'), 0));
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableglobalrestrictions', new lang_string('enableglobalrestrictions', 'shezar_reportbuilder'), new lang_string('enableglobalrestrictions_desc', 'shezar_reportbuilder'), 0));

    // Audience visibility.
    $optionalsubsystems->add(new admin_setting_configcheckbox('audiencevisibility', new lang_string('enableaudiencevisibility', 'shezar_cohort'), new lang_string('configenableaudiencevisibility', 'shezar_cohort'), 0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableconnectserver',
        new lang_string('enableconnectserver', 'shezar_connect'),
        new lang_string('enableconnectserver_desc', 'shezar_connect'),
        0));

    // Enchanced catalog.
    // Was upgrade - old catalog by default, otherwise - new catalog.
    $defaultenhanced = 1;
    $setting = new admin_setting_configcheckbox('enhancedcatalog',
            new lang_string('enhancedcatalog', 'shezar_core'),
            new lang_string('configenhancedcatalog', 'shezar_core'), $defaultenhanced);
    $setting->set_updatedcallback('shezar_menu_reset_cache');
    $optionalsubsystems->add($setting);

    // Dynamic Appraisals.
    $optionalsubsystems->add(new admin_setting_configcheckbox('dynamicappraisals',
            new lang_string('dynamicappraisals', 'shezar_core'),
            new lang_string('configdynamicappraisals', 'shezar_core'), 1));

    // Show Hierarchy shortcodes.
    $optionalsubsystems->add(new admin_setting_configcheckbox('showhierarchyshortnames',
            new lang_string('showhierarchyshortnames', 'shezar_hierarchy'),
            new lang_string('configshowhierarchyshortnames', 'shezar_hierarchy'), 0));

    // Program extension request setting.
    $optionalsubsystems->add(new admin_setting_configcheckbox('enableprogramextensionrequests',
        new lang_string('enableprogramextensionrequests', 'shezar_core'),
        new lang_string('enableprogramextensionrequests_help', 'shezar_core'), 1));

    // If adding or removing the settings below, be sure to update the array in
    // shezar_advanced_features_list() in shezar/core/shezar.php.

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablegoals',
        new lang_string('enablegoals', 'shezar_hierarchy'),
        new lang_string('configenablegoals', 'shezar_hierarchy'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablecompetencies',
        new lang_string('enablecompetencies', 'shezar_hierarchy'),
        new lang_string('enablecompetencies_desc', 'shezar_hierarchy'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enableappraisals',
        new lang_string('enableappraisals', 'shezar_appraisal'),
        new lang_string('configenableappraisals', 'shezar_appraisal'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablefeedback360',
        new lang_string('enablefeedback360', 'shezar_feedback360'),
        new lang_string('configenablefeedback360', 'shezar_feedback360'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablelearningplans',
        new lang_string('enablelearningplans', 'shezar_plan'),
        new lang_string('configenablelearningplans', 'shezar_plan'),
        shezar_SHOWFEATURE,
        array('shezar_menu_reset_cache', array('enrol_shezar_learningplan_util', 'feature_setting_updated_callback'))));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enableprograms',
        new lang_string('enableprograms', 'shezar_program'),
        new lang_string('configenableprograms', 'shezar_program'),
        shezar_SHOWFEATURE,
        array('shezar_menu_reset_cache', array('enrol_shezar_program_util', 'feature_setting_updated_callback'))));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablecertifications',
        new lang_string('enablecertifications', 'shezar_program'),
        new lang_string('configenablecertifications', 'shezar_program'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enableshezardashboard',
        new lang_string('enableshezardashboard', 'shezar_dashboard'),
        new lang_string('configenableshezardashboard', 'shezar_dashboard'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablereportgraphs',
        new lang_string('enablereportgraphs', 'shezar_reportbuilder'),
        new lang_string('enablereportgraphsinfo', 'shezar_reportbuilder'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablerecordoflearning',
        new lang_string('enablerecordoflearning', 'shezar_plan'),
        new lang_string('enablerecordoflearninginfo', 'shezar_plan'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablepositions',
        new lang_string('enablepositions', 'shezar_hierarchy'),
        new lang_string('enablepositions_desc', 'shezar_hierarchy'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new shezar_core_admin_setting_feature('enablemyteam',
        new lang_string('enableteam', 'shezar_core'),
        new lang_string('enableteam_desc', 'shezar_core'),
        shezar_SHOWFEATURE));

    $optionalsubsystems->add(new admin_setting_configcheckbox('enableprogramcompletioneditor',
        new lang_string('enableprogramcompletioneditor', 'shezar_program'),
        new lang_string('enableprogramcompletioneditor_desc', 'shezar_program'),
        0));

    $optionalsubsystems->add(new admin_setting_configcheckbox('shezar_job_allowmultiplejobs',
        new lang_string('setting:allowmultiplejobs', 'shezar_job'),
        new lang_string('setting:allowmultiplejobs_description', 'shezar_job'),
        1));
}
