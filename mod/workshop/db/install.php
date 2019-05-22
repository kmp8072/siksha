<?php

function xmldb_workshop_install() {
    global $DB;

    // shezar: we disable this during install.
    $DB->set_field('modules', 'visible', 0, array('name'=>'workshop'));
}

