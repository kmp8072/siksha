<?php

defined('MOODLE_INTERNAL') || die();

    $observers = array(
        array(
            'eventname' => 'shezar_program\event\program_completed',
            'callback' => 'local_programcompletionemail_observer::program_modules_completion',
        ),
    );
