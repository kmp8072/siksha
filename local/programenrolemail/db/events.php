<?php

defined('MOODLE_INTERNAL') || die();

    $observers = array(
        array(
            'eventname' => 'shezar_program\event\program_assigned',
            'callback' => 'local_programenrolemail_observer::program_enrol_completion',
        ),
    );
