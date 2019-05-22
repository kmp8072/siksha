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

defined('MOODLE_INTERNAL') || die;

define('shezar_SYNC_DBROWS', 10000);
define('FILE_ACCESS_DIRECTORY', 0);
define('FILE_ACCESS_UPLOAD', 1);
define('shezar_SYNC_LOGTYPE_MAX_NOTIFICATIONS', 50);

/**
 * Finds the run id of the latest sync run
 *
 * @return int latest runid
 */
function latest_runid() {
    global $DB;

    $runid = $DB->get_field_sql('SELECT MAX(runid) FROM {shezar_sync_log}');

    if (!empty($runid)) {
        return $runid;
    } else {
        return 0;
    }
}

/**
 * Sync shezar elements with external sources
 *
 * @access public
 * @return bool success
 */
function tool_shezar_sync_run() {
    global $CFG;

    // First run through the sanity checks.
    $configured = true;
    $problemstext = array();

    $fileaccess = get_config('shezar_sync', 'fileaccess');
    if ($fileaccess == FILE_ACCESS_DIRECTORY && !$filesdir = get_config('shezar_sync', 'filesdir')) {
        $configured = false;
        $problemstext[] = get_string('nofilesdir', 'tool_shezar_sync');
    }
    // Check enabled sync element objects
    $elements = shezar_sync_get_elements(true);
    if (empty($elements)) {
        $configured = false;
        $problemstext[] = get_string('noenabledelements', 'tool_shezar_sync');
    } else {
        foreach ($elements as $element) {
            $elname = $element->get_name();
            $elnametext = get_string('displayname:'.$elname, 'tool_shezar_sync');
            //check a source is enabled
            if (!$sourceclass = get_config('shezar_sync', 'source_' . $elname)) {
                $configured = false;
                $problemstext[] = get_string('sourcenotfound', 'tool_shezar_sync', $elnametext);
            }
            //check source has configs - note get_config returns an object
            if ($sourceclass) {
                $configs = get_config($sourceclass);
                $props = get_object_vars($configs);
                if(empty($props)) {
                    $configured = false;
                    $problemstext[] = get_string('nosourceconfig', 'tool_shezar_sync', $elnametext);
                }
            }
        }
    }

    if (!$configured) {
        $problems = implode(", ", $problemstext);
        mtrace(get_string('syncnotconfiguredsummary', 'tool_shezar_sync', $problems));
        return false;
    }

    $status = true;
    foreach ($elements as $element) {
        try {
            if (!method_exists($element, 'sync')) {
                // Skip if no sync() method exists
                continue;
            }

            // Finally, start element syncing
            $status = $status && $element->sync();
        } catch (shezar_sync_exception $e) {
            $msg = $e->getMessage();
            $msg .= !empty($e->debuginfo) ? " - {$e->debuginfo}" : '';
            shezar_sync_log($e->tsync_element, $msg, $e->tsync_logtype, $e->tsync_action);
            $element->get_source()->drop_table();
            continue;
        } catch (Exception $e) {
            shezar_sync_log($element->get_name(), $e->getMessage(), 'error', 'unknown');
            $element->get_source()->drop_table();
            continue;
        }

        $element->get_source()->drop_table();
    }

    \tool_shezar_sync\event\sync_completed::create()->trigger();

    shezar_sync_notify();

    return $status;
}

/**
 * Method for adding sync log messages
 *
 * @param string $element element name
 * @param string $info the log message
 * @param string $type the log message type
 * @param string $action the action which caused the log message
 * @param boolean $showmessage shows error messages on the main page when running sync if it is true
 */
function shezar_sync_log($element, $info, $type='info', $action='', $showmessage=true) {
    global $DB, $OUTPUT;

    // Avoid getting an error from the database trying to save a value longer than length limit (255 characters).
    if (core_text::strlen($info) > 255) {
        $info = trim(core_text::substr($info, 0, 252)) . "...";
    }

    static $sync_runid = null;

    if ($sync_runid == null) {
        $sync_runid = latest_runid() + 1;
    }

    $todb = new stdClass;
    $todb->element = $element;
    $todb->logtype = $type;
    $todb->action = $action;
    $todb->info = $info;
    $todb->time = time();
    $todb->runid = $sync_runid;

    if ($showmessage && ($type == 'warn' || $type == 'error')) {
        $typestr = get_string($type, 'tool_shezar_sync');
        $class = $type == 'warn' ? 'notifynotice' : 'notifyproblem';
        echo $OUTPUT->notification($typestr . ':' . $element . ' - ' . $info, $class);
    }

    return $DB->insert_record('shezar_sync_log', $todb);
}

/**
 * Get the sync file paths for all elements
 *
 * @return array of filepaths
 */
function shezar_sync_get_element_files() {
    global $CFG;

    // Get all available sync element files
    $edir = $CFG->dirroot.'/admin/tool/shezar_sync/elements/';
    $pattern = '/(.*?)\.php$/';
    $files = preg_grep($pattern, scandir($edir));
    $filepaths = array();
    foreach ($files as $key => $val) {
        $filepaths[] = $edir . $val;
    }
    return $filepaths;
}

/**
 * Get sync elements
 *
 * @param boolean $onlyenabled only return enabled elements
 *
 * @return array of element objects
 */
function shezar_sync_get_elements($onlyenabled=false) {
    global $CFG;

    $efiles = shezar_sync_get_element_files();

    $elements = array();
    foreach ($efiles as $filepath) {
        $element = basename($filepath, '.php');

        if ($element == 'pos' && shezar_feature_disabled('positions')) {
            continue;
        }

        if ($onlyenabled) {
            if (!get_config('shezar_sync', 'element_'.$element.'_enabled')) {
                continue;
            }
        }

        require_once($filepath);

        $elementclass = 'shezar_sync_element_'.$element;
        if (!class_exists($elementclass)) {
            // Skip if the class does not exist
            continue;
        }

        $elements[$element] = new $elementclass;
    }

    return $elements;
}

/**
 * Get a specified element object
 *
 * @param string $element the element name
 *
 * @return stdClass the element object
 */
function shezar_sync_get_element($element) {
    $elements = shezar_sync_get_elements();

    if (!in_array($element, array_keys($elements))) {
        return false;
    }

    return $elements[$element];
}

function shezar_sync_make_dirs($dirpath) {
    global $CFG;

    $dirarray = explode('/', $dirpath);
    $currdir = '';
    foreach ($dirarray as $dir) {
        $currdir = $currdir.$dir.'/';
        if (!file_exists($currdir)) {
            if (!mkdir($currdir, $CFG->directorypermissions)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Cleans the values and returns as an array
 *
 * @param array $fields
 * @param string $encoding the encoding type that string is being converted from to utf-8 (deprecated, use shezar_sync_clean_csvfile)
 * @return array $fields
 */
function shezar_sync_clean_fields($fields, $encoding = 'UTF-8') {
    foreach ($fields as $key => $value) {
        $format = ($key == 'password') ? PARAM_RAW : PARAM_TEXT;
        if ($encoding !== 'UTF-8') {
            $value = core_text::convert($value, $encoding, 'UTF-8');
        }
        $fields[$key] = clean_param(trim($value), $format);
    }
    return $fields;
}

/**
 * Convert and cleans content
 *
 * @param string $storefilepath original file to clean up
 * @param string $encoding content encoding
 * @param integer $fileaccess is FILE_ACCESS_DIRECTORY or FILE_ACCESS_UPLOAD
 * @param string $elementname the name of the element this source applies to
 *
 * @return string temporary file with clean content
 */
function shezar_sync_clean_csvfile($storefilepath, $encoding, $fileaccess, $elementname) {

    if (!is_readable($storefilepath)) {
        throw new shezar_sync_exception($elementname, 'populatesynctablecsv', 'storedfilecannotread', $storefilepath);
    }

    $content = file_get_contents($storefilepath);

    if (strtoupper($encoding) === 'UTF-8') {
        // Remove Unicode BOM from first line.
        $content = core_text::trim_utf8_bom($content);
    }
    $content = core_text::convert($content, $encoding, 'utf-8');

    // Create a temporary file and store the csv file there and delete original filename or
    // overwrite original filename.
    if ($fileaccess == FILE_ACCESS_UPLOAD) {
        unlink($storefilepath);
        $file = tempnam(make_temp_directory('/csvimport'), 'tmp');
    } else {
        @unlink($storefilepath);
        $file = $storefilepath;
    }

    $result = file_put_contents($file, $content);
    if ($result === false) {
        if ($fileaccess == FILE_ACCESS_UPLOAD) {
            @unlink($file);
        }
        throw new shezar_sync_exception($elementname, 'populatesynctablecsv', 'cannotsavedata', $file);
    }

    // Use permissions form parent dir.
    @chmod($file, (fileperms(dirname($file)) & 0666));
    return $file;
}

/**
 * Perform bulk inserts into specified table
 *
 * @param string $table table name
 * @param array $datarows an array of row arrays
 *
 * @return boolean
 */
function shezar_sync_bulk_insert($table, $datarows) {
    global $CFG, $DB;

    if (empty($datarows)) {
        return true;
    }

    $DB->insert_records($table, $datarows);
    return true;
}

/**
 * Notify admin users or admin user of any sync failures since last notification.
 *
 * Note that this function must be only executed from the cron script
 *
 * @return bool true if executed, false if not
 */
function shezar_sync_notify() {
    global $CFG, $DB;

    $now = time();
    $dateformat = get_string('strftimedateseconds', 'langconfig');
    $notifyemails = get_config('shezar_sync', 'notifymailto');
    $notifyemails = !empty($notifyemails) ? explode(',', $notifyemails) : array();
    $notifytypes = get_config('shezar_sync', 'notifytypes');
    $notifytypes = !empty($notifytypes) ? explode(',', $notifytypes) : array();

    if (empty($notifyemails) || empty($notifytypes)) {
        set_config('lastnotify', $now, 'shezar_sync');
        return false;
    }

    // The same users as login failures.
    if (!$lastnotify = get_config('shezar_sync', 'lastnotify')) {
        $lastnotify = 0;
    }

    // Get most recent log messages of type.
    list($sqlin, $params) = $DB->get_in_or_equal($notifytypes);
    $params = array_merge($params, array($lastnotify));
    $logitems = $DB->get_records_select('shezar_sync_log', "logtype {$sqlin} AND time > ?", $params,
                                        'time DESC', '*', 0, shezar_SYNC_LOGTYPE_MAX_NOTIFICATIONS);
    if (!$logitems) {
        // Nothing to report.
        return true;
    }

    // Build email message.
    $logcount = count($logitems);
    $sitename = get_site();
    $sitename = format_string($sitename->fullname);
    $notifytypes_str = array_map(create_function('$type', "return get_string(\$type.'plural', 'tool_shezar_sync');"), $notifytypes);
    $subject = get_string('notifysubject', 'tool_shezar_sync', $sitename);

    $a = new stdClass();
    $a->logtypes = implode(', ', $notifytypes_str);
    $a->count = $logcount;
    $a->since = userdate($lastnotify, $dateformat);
    $message = get_string('notifymessagestart', 'tool_shezar_sync', $a);
    $message .= "\n\n";
    foreach ($logitems as $logentry) {
        $logentry->time = userdate($logentry->time, $dateformat);
        $logentry->logtype = get_string($logentry->logtype, 'tool_shezar_sync');
        $message .= get_string('notifymessage', 'tool_shezar_sync', $logentry) . "\n\n";
    }
    $message .= "\n" . get_string('viewsyncloghere', 'tool_shezar_sync',
            $CFG->wwwroot . '/admin/tool/shezar_sync/admin/synclog.php');

    // Send emails.
    mtrace("\n{$logcount} relevant shezar sync log messages since " .
        userdate($lastnotify, $dateformat)) . ". Sending notifications...";
    $supportuser = core_user::get_support_user();
    foreach ($notifyemails as $emailaddress) {
        $userto = \shezar_core\shezar_user::get_external_user(trim($emailaddress));
        email_to_user($userto, $supportuser, $subject, $message);
    }

    // Update lastnotify with current time.
    set_config('lastnotify', $now, 'shezar_sync');

    return true;
}

class shezar_sync_exception extends moodle_exception {
    public $tsync_element;
    public $tsync_action;
    public $tsync_logtype;

    /**
     * Constructor
     * @param string $errorcode The name of the string from error.php to print
     * @param object $a Extra words and phrases that might be required in the error string
     * @param string $debuginfo optional debugging information
     * @param string $logtype optional shezar sync log type
     */
    public function __construct($element, $action, $errorcode, $a = null, $debuginfo = null, $logtype = 'error') {
        $this->tsync_element = $element;
        $this->tsync_action = $action;
        $this->tsync_logtype = $logtype;

        parent::__construct($errorcode, 'tool_shezar_sync', $link='', $a, $debuginfo);
    }
}
