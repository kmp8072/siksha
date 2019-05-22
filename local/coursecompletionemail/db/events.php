<?php

defined('MOODLE_INTERNAL') || die();

    $observers = array(
        array(
            'eventname' => 'core\event\course_completed',
            'callback' => 'local_coursecompletionemail_observer::course_modules_completion',
        ),
    );
