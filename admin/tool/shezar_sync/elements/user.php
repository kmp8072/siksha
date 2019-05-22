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

require_once($CFG->dirroot.'/admin/tool/shezar_sync/elements/classes/element.class.php');
require_once($CFG->dirroot.'/shezar/customfield/fieldlib.php');
require_once($CFG->dirroot.'/shezar/hierarchy/prefix/position/lib.php');

class shezar_sync_element_user extends shezar_sync_element {
    const KEEP_USERS = 0;
    const DELETE_USERS = 1;
    const SUSPEND_USERS = 2;

    protected $customfieldsdb = array();

    function get_name() {
        return 'user';
    }

    function has_config() {
        return true;
    }

    /**
     * Set customfieldsdb property with menu of choices options
     */
    function set_customfieldsdb() {
        global $DB;

        $rs = $DB->get_recordset('user_info_field', array(), '', 'id,shortname,datatype,required,defaultdata,locked,forceunique,param1');
        if ($rs->valid()) {
            foreach ($rs as $r) {
                $this->customfieldsdb['customfield_'.$r->shortname]['id'] = $r->id;
                $this->customfieldsdb['customfield_'.$r->shortname]['required'] = $r->required;
                $this->customfieldsdb['customfield_'.$r->shortname]['forceunique'] = $r->forceunique;
                $this->customfieldsdb['customfield_'.$r->shortname]['default'] = $r->defaultdata;

                if ($r->datatype == 'menu') {
                    // Set all options to lower case to match values to options without case sensitivity.
                    $options = explode("\n", core_text::strtolower($r->param1));
                    $this->customfieldsdb['customfield_'.$r->shortname]['menu_options'] = $options;
                }
            }
        }
        $rs->close();
    }

    function config_form(&$mform) {
        $mform->addElement('selectyesno', 'sourceallrecords', get_string('sourceallrecords', 'tool_shezar_sync'));
        $mform->addElement('static', 'sourceallrecordsdesc', '', get_string('sourceallrecordsdesc', 'tool_shezar_sync'));

        // Empty CSV field setting.
        $emptyfieldopt = array(
            false => get_string('emptyfieldskeepdata', 'tool_shezar_sync'),
            true => get_string('emptyfieldsremovedata', 'tool_shezar_sync')
        );
        $mform->addElement('select', 'csvsaveemptyfields', get_string('emptyfieldsbehaviouruser', 'tool_shezar_sync'), $emptyfieldopt);
        $mform->disabledIf('csvsaveemptyfields', 'source_user', 'eq', '');
        $mform->disabledIf('csvsaveemptyfields', 'source_user', 'eq', 'shezar_sync_source_user_database');
        $default = !empty($this->config->csvsaveemptyfields);
        $mform->setDefault('csvsaveemptyfields', $default);
        $mform->addHelpButton('csvsaveemptyfields', 'emptyfieldsbehaviouruser', 'tool_shezar_sync');

        // User email settings.
        $mform->addElement('selectyesno', 'allowduplicatedemails', get_string('allowduplicatedemails', 'tool_shezar_sync'));
        $mform->addElement('text', 'defaultsyncemail', get_string('defaultemailaddress', 'tool_shezar_sync'), array('size' => 50));
        $mform->addElement('static', 'emailsettingsdesc', '', get_string('emailsettingsdesc', 'tool_shezar_sync'));
        $mform->setType('defaultsyncemail', PARAM_TEXT);
        $mform->disabledIf('defaultsyncemail', 'allowduplicatedemails', 'eq', 0);
        $mform->setDefault('defaultsyncemail', '');


        // User password settings.
        $mform->addElement('selectyesno', 'ignoreexistingpass', get_string('ignoreexistingpass', 'tool_shezar_sync'));
        $mform->addElement('static', 'ignoreexistingpassdesc', '', get_string('ignoreexistingpassdesc', 'tool_shezar_sync'));
        $mform->addElement('selectyesno', 'forcepwchange', get_string('forcepwchange', 'tool_shezar_sync'));
        $mform->addElement('static', 'forcepwchangedesc', '', get_string('forcepwchangedesc', 'tool_shezar_sync'));
        $mform->addElement('selectyesno', 'undeletepwreset', get_string('undeletepwreset', 'tool_shezar_sync'));
        $mform->addElement('static', 'undeletepwresetdesc', '', get_string('undeletepwresetdesc', 'tool_shezar_sync'));

        $linkjobassignmentidnumber = get_config('shezar_sync', 'linkjobassignmentidnumber');
        if (empty($linkjobassignmentidnumber)) {
            $linkopt = array();
            $linkopt[0] = get_string('linkjobassignmentidnumberfalse', 'tool_shezar_sync');
            $linkopt[1] = get_string('linkjobassignmentidnumbertrue', 'tool_shezar_sync');
            $mform->addElement('select', 'linkjobassignmentidnumber', get_string('linkjobassignmentidnumber', 'tool_shezar_sync'), $linkopt);
            $mform->addElement('static', 'linkjobassignmentidnumberdesc', '', get_string('linkjobassignmentidnumberdesc', 'tool_shezar_sync'));
            $mform->setDefault('linkjobassignmentidnumber', 0);
        }

        $mform->addElement('header', 'crudheading', get_string('allowedactions', 'tool_shezar_sync'));

        $mform->addElement('checkbox', 'allow_create', get_string('create', 'tool_shezar_sync'));
        $mform->setDefault('allow_create', 1);
        $mform->addElement('checkbox', 'allow_update', get_string('update', 'tool_shezar_sync'));
        $mform->setDefault('allow_update', 1);

        $deleteopt = array();
        $deleteopt[self::KEEP_USERS] = get_string('auth_remove_keep','auth');
        $deleteopt[self::SUSPEND_USERS] = get_string('auth_remove_suspend','auth');
        $deleteopt[self::DELETE_USERS] = get_string('auth_remove_delete','auth');
        $mform->addElement('select', 'allow_delete', get_string('delete', 'tool_shezar_sync'), $deleteopt);
        $mform->setDefault('allow_delete', self::KEEP_USERS);
        $mform->setExpanded('crudheading');
    }

    function validation($data, $files) {
        $errors = array();
        if ($data['allowduplicatedemails'] && !empty($data['defaultsyncemail']) && !validate_email($data['defaultsyncemail'])) {
            $errors['defaultsyncemail'] = get_string('invalidemail');
        }
        return $errors;
    }

    function config_save($data) {
        $this->set_config('sourceallrecords', $data->sourceallrecords);
        $this->set_config('csvsaveemptyfields', !empty($data->csvsaveemptyfields));
        $this->set_config('allowduplicatedemails', $data->allowduplicatedemails);
        if (!empty($data->allow_create)) {
            // When user creation is allowed, force change the first name and last name settings on.
            set_config('import_firstname', "1", 'shezar_sync_source_user_csv');
            set_config('import_firstname', "1", 'shezar_sync_source_user_database');
            set_config('import_lastname', "1", 'shezar_sync_source_user_csv');
            set_config('import_lastname', "1", 'shezar_sync_source_user_database');
            if (empty($data->allowduplicatedemails)) {
                // When user creation is allowed and duplicate emails are not allowed, force change the email settings on.
                set_config('import_email', "1", 'shezar_sync_source_user_csv');
                set_config('import_email', "1", 'shezar_sync_source_user_database');
            }
        }
        $this->set_config('defaultsyncemail', $data->defaultsyncemail);
        $this->set_config('ignoreexistingpass', $data->ignoreexistingpass);
        $this->set_config('forcepwchange', $data->forcepwchange);
        $this->set_config('undeletepwreset', $data->undeletepwreset);
        $this->set_config('allow_create', !empty($data->allow_create));
        $this->set_config('allow_update', !empty($data->allow_update));
        $this->set_config('allow_delete', $data->allow_delete);
        $this->set_config('linkjobassignmentidnumber', !empty($data->linkjobassignmentidnumber));
        if (!empty($data->linkjobassignmentidnumber)) {
            if (get_config('shezar_sync_source_user_csv', 'import_manageridnumber')) {
                set_config('import_managerjobassignmentidnumber', "1", 'shezar_sync_source_user_csv');
            }
        }

        if (!empty($data->source_user)) {
            $source = $this->get_source($data->source_user);
            // Build link to source config.
            $url = new moodle_url('/admin/tool/shezar_sync/admin/sourcesettings.php', array('element' => $this->get_name(), 'source' => $source->get_name()));
            if ($source->has_config()) {
                // Set import_deleted and warn if necessary.
                $import_deleted_new = ($data->sourceallrecords == 0) ? '1' : '0';
                $import_deleted_old = $source->get_config('import_deleted');
                if ($import_deleted_new != $import_deleted_old) {
                    $source->set_config('import_deleted', $import_deleted_new);
                    shezar_set_notification(get_string('checkuserconfig', 'tool_shezar_sync', $url->out()), null, array('class'=>'notifynotice alert alert-warning'));
                }
            }
        }
    }

    function sync() {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $this->addlog(get_string('syncstarted', 'tool_shezar_sync'), 'info', 'usersync');
        // Array to store the users we create or update that
        // will need to have their assignments synced.
        $assign_sync_users = array();

        try {
            // This can go wrong in many different ways - catch as a generic exception.
            $synctable = $this->get_source_sync_table();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (debugging()) {
                $msg .= !empty($e->debuginfo) ? " - {$e->debuginfo}" : '';
            }
            shezar_sync_log($this->get_name(), $msg, 'error', 'unknown');
            return false;
        }

        try {
            // This can go wrong in many different ways - catch as a generic exception.
            $synctable_clone = $this->get_source_sync_table_clone($synctable);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (debugging()) {
                $msg .= !empty($e->debuginfo) ? " - {$e->debuginfo}" : '';
            }
            shezar_sync_log($this->get_name(), $msg, 'error', 'unknown');
            return false;
        }

        $this->set_customfieldsdb();

        $invalidids = $this->check_sanity($synctable, $synctable_clone);
        $issane = (empty($invalidids) ? true : false);
        $problemswhileapplying = false;

        // Initialise to safe defaults if settings not present.
        if (!isset($this->config->sourceallrecords)) {
            $this->config->sourceallrecords = 0;
        }
        if (!isset($this->config->allow_create)) {
            $this->config->allow_create = 0;
        }
        if (!isset($this->config->allow_update)) {
            $this->config->allow_update = 0;
        }
        if (!isset($this->config->allow_delete)) {
            $this->config->allow_delete = self::KEEP_USERS;
        }

        // May sure the required deleted column is present if necessary.
        $synctablecolumns = $DB->get_columns($synctable);
        $deletedcolumnpresent = isset($synctablecolumns['deleted']);

        if ($this->config->allow_delete == self::DELETE_USERS) {
            $sql = null;
            if ($this->config->sourceallrecords == 0) {
                if ($deletedcolumnpresent) {
                    // Get records with "deleted" flag set.
                    // Do not use DISTINCT here, idnumber may not be unique - we want errors for all duplicates.
                    // If there are repeated rows in external table we will just delete twice.
                    $sql = "SELECT u.id, u.idnumber, u.auth
                              FROM {{$synctable}} s
                              JOIN {user} u ON (s.idnumber = u.idnumber AND u.idnumber != '')
                             WHERE u.shezarsync = 1 AND u.deleted = 0 AND s.deleted = 1";
                }
            } else if ($this->config->sourceallrecords == 1) {
                // All records provided by source - get missing user records.
                // Also consider the deleted flag if present.
                if ($deletedcolumnpresent) {
                    $sql = "SELECT u.id, u.idnumber, u.auth
                              FROM {user} u
                         LEFT JOIN {{$synctable}} s ON (u.idnumber = s.idnumber AND u.idnumber != '')
                             WHERE u.shezarsync = 1 AND u.deleted = 0 AND (s.idnumber IS NULL OR s.deleted = 1)";
                } else {
                    $sql = "SELECT u.id, u.idnumber, u.auth
                              FROM {user} u
                         LEFT JOIN {{$synctable}} s ON (u.idnumber = s.idnumber AND u.idnumber != '')
                             WHERE u.shezarsync = 1 AND u.deleted = 0 AND s.idnumber IS NULL";
                }
            }
            if ($sql) {
                $rs = $DB->get_recordset_sql($sql);
                foreach ($rs as $user) {
                    // Remove user.
                    try {
                        // Do not delete the records which have invalid values(e.g. spelling mistake).
                        if (array_search($user->idnumber, $invalidids) === false) {
                            $usr = $DB->get_record('user', array('id' => $user->id));
                            // Check for guest account record.
                            if ($usr->username === 'guest' || isguestuser($usr)) {
                                $this->addlog(get_string('cannotdeleteuserguest', 'tool_shezar_sync', $user->idnumber), 'warn', 'deleteuser');
                                $problemswhileapplying = true;
                                continue;
                            }
                            // Check for admin account record.
                            if ($usr->auth === 'manual' && is_siteadmin($usr)) {
                                $this->addlog(get_string('cannotdeleteuseradmin', 'tool_shezar_sync', $user->idnumber), 'warn', 'deleteuser');
                                $problemswhileapplying = true;
                                continue;
                            }
                            if (delete_user($usr)) {
                                $this->addlog(get_string('deleteduserx', 'tool_shezar_sync', $user->idnumber), 'info', 'deleteuser');
                            } else {
                                $this->addlog(get_string('cannotdeleteuserx', 'tool_shezar_sync', $user->idnumber), 'warn', 'deleteuser');
                                $problemswhileapplying = true;
                            }
                        }
                    } catch (Exception $e) {
                        // We don't want this exception to stop processing so we will continue.
                        // The code may have started a transaction. If it did then roll back the transaction.
                        if ($DB->is_transaction_started()) {
                            $DB->force_transaction_rollback();
                        }
                        $this->addlog(get_string('cannotdeleteuserx', 'tool_shezar_sync', $user->idnumber) . ': ' .
                            $e->getMessage(), 'warn', 'deleteuser');
                        $problemswhileapplying = true;
                        continue; // Continue processing users.
                    }
                }
                $rs->close();
            }

        } else if ($this->config->allow_delete == self::SUSPEND_USERS) {
            $sql = null;
            if ($this->config->sourceallrecords == 0) {
                if ($deletedcolumnpresent) {
                    // Get records with "deleted" flag set.
                    // Do not use DISTINCT here, idnumber may not be unique - we want errors for all duplicates.
                    // If there are repeated rows in external table we will just delete twice.
                    $sql = "SELECT u.id, u.idnumber, u.auth
                              FROM {{$synctable}} s
                              JOIN {user} u ON (s.idnumber = u.idnumber AND u.idnumber != '')
                             WHERE u.shezarsync = 1 AND u.deleted = 0 AND u.suspended = 0 AND s.deleted = 1";
                }
            } else if ($this->config->sourceallrecords == 1) {
                // All records provided by source - get missing user records.
                // Also consider the deleted flag if present.
                if ($deletedcolumnpresent) {
                    $sql = "SELECT u.id, u.idnumber, u.auth
                              FROM {user} u
                         LEFT JOIN {{$synctable}} s ON (u.idnumber = s.idnumber AND u.idnumber != '')
                             WHERE u.shezarsync = 1 AND u.deleted = 0 AND u.suspended = 0 AND (s.idnumber IS NULL OR s.deleted = 1)";
                } else {
                    $sql = "SELECT u.id, u.idnumber, u.auth
                              FROM {user} u
                         LEFT JOIN {{$synctable}} s ON (u.idnumber = s.idnumber AND u.idnumber != '')
                             WHERE u.shezarsync = 1  AND u.deleted = 0 AND u.suspended = 0 AND s.idnumber IS NULL";
                }
            }
            if ($sql) {
                $rs = $DB->get_recordset_sql($sql);
                foreach ($rs as $user) {
                    // Do not suspend the records which have invalid values(e.g. spelling mistake).
                    if (array_search($user->idnumber, $invalidids) === false) {
                        $user = $DB->get_record('user', array('id' => $user->id));
                        $user->suspended = 1;
                        \core\session\manager::kill_user_sessions($user->id);
                        user_update_user($user, false);
                        \shezar_core\event\user_suspended::create_from_user($user)->trigger();
                        $this->addlog(get_string('suspendeduserx', 'tool_shezar_sync', $user->idnumber), 'info', 'suspenduser');
                    }
                }
                $rs->close();
            }
        }

        if ($deletedcolumnpresent) {
            // Remove the deleted records from the sync table.
            // This ensures that our create/update queries runs smoothly.
            $DB->execute("DELETE FROM {{$synctable}} WHERE deleted <> 0");
            $DB->execute("DELETE FROM {{$synctable_clone}} WHERE deleted <> 0");
        }

        if (!empty($this->config->allow_update)) {
            // This must be done before creating new accounts because once the accounts are created this query would return them as well,
            // even when they do not need to be updated.
            $sql = "SELECT s.*, u.id AS uid
                      FROM {user} u
                INNER JOIN {{$synctable}} s ON (u.idnumber = s.idnumber AND u.idnumber != '')
                     WHERE u.shezarsync=1
                       AND (s.timemodified = 0 OR u.timemodified != s.timemodified)";  // If no timemodified, always update.
            $rsupdateaccounts = $DB->get_recordset_sql($sql);
        }

        $iscsvimport = substr(get_class($this->get_source()), -4) === '_csv';
        $saveemptyfields = !$iscsvimport || !empty($this->config->csvsaveemptyfields);

        if (!empty($this->config->allow_create)) {
            // Get accounts that must be created.
            $sql = "SELECT s.*
                      FROM {{$synctable}} s
           LEFT OUTER JOIN {user} u ON (s.idnumber=u.idnumber)
                     WHERE u.idnumber IS NULL AND s.idnumber IS NOT NULL AND s.idnumber != ''
                     ORDER BY s.idnumber";
            $rscreateaccounts = $DB->get_recordset_sql($sql);

            // The idea of doing this is to get the accounts that need to be created. Since users are created first and then user assignments,
            // it is not possible (after creating users) to know which accounts need to be created.
            $DB->execute("DELETE FROM {{$synctable_clone}}
                           WHERE idnumber IN (
                          SELECT s.idnumber
                            FROM {user} u
                      INNER JOIN {{$synctable}} s ON (u.idnumber = s.idnumber AND u.idnumber != ''))");

            // Create missing accounts.
            $previousidnumber = false;
            foreach ($rscreateaccounts as $suser) {
                if ($suser->idnumber === $previousidnumber) {
                    // Duplicates can only occur at this point if it is due to multiple job assignments.
                    continue;
                }
                try {
                    $this->create_user($suser, $saveemptyfields);
                    $this->addlog(get_string('createduserx', 'tool_shezar_sync', $suser->idnumber), 'info', 'createuser');
                } catch (Exception $e) {
                    // We can't do anything here, we have to trust that create user has tided up any transactions it opened.
                    // If we rollback all transactions here the clone table which was created within a transaction will be removed.
                    $this->addlog(get_string('cannotcreateuserx', 'tool_shezar_sync', $suser->idnumber) . ': ' .
                            $e->getMessage(), 'error', 'createuser');
                    $problemswhileapplying = true;
                    continue; // Continue processing users.
                }
                $previousidnumber = $suser->idnumber;
            }
            $rscreateaccounts->close(); // Free memory.

            // Get data for user assignments for assignment sync later.
            $sql = "SELECT sc.*, u.id as uid
                      FROM {{$synctable_clone}} sc
                INNER JOIN {user} u ON (sc.idnumber = u.idnumber AND u.idnumber != '')";
            $rscreateassignments = $DB->get_recordset_sql($sql);
            foreach ($rscreateassignments as $suser) {
                $assign_sync_users[$suser->id] = $suser;
            }
            $rscreateassignments->close(); // Free memory.
        }

        if (!empty($this->config->allow_update)) {
            foreach ($rsupdateaccounts as $suser) {
                $user = $DB->get_record('user', array('id' => $suser->uid));

                // Decide now if we'll try to update the password later.
                $updatepassword = empty($this->config->ignoreexistingpass) &&
                                  isset($suser->password) &&
                                  trim($suser->password) !== '';

                if (!empty($this->config->allow_create) && !empty($user->deleted)) {
                    // Revive previously-deleted user.
                    if (undelete_user($user)) {
                        $user->deleted = 0;

                        if (!$updatepassword && !empty($this->config->undeletepwreset)) {
                            // If the password wasn't supplied in the sync and reset is enabled then tag the revived
                            // user for new password generation (if applicable).
                            $userauth = get_auth_plugin(strtolower($user->auth));
                            if ($userauth->can_change_password()) {
                                set_user_preference('auth_forcepasswordchange', 1, $user->id);
                                set_user_preference('create_password',          1, $user->id);
                            }
                            unset($userauth);
                        }

                        $this->addlog(get_string('reviveduserx', 'tool_shezar_sync', $suser->idnumber), 'info', 'updateusers');
                    } else {
                        $this->addlog(get_string('cannotreviveuserx', 'tool_shezar_sync', $suser->idnumber), 'warn', 'updateusers');
                        $problemswhileapplying = true;
                        // Try to continue with other operations to this user.
                    }
                }

                $suspenduser = false;
                if (isset($suser->suspended)) {
                    // Check if the user is going to be suspended before updating the $user object.
                    if ($user->suspended == 0 and $suser->suspended == 1) {
                        $suspenduser = true;
                    }
                } else {
                    if ($user->suspended == 1 and $this->config->allow_delete == self::SUSPEND_USERS) {
                        // User was previously deleted which resulted in suspension of account, enable the account now.
                        $suser->suspended = '0';
                    }
                }

                // Update user.
                $this->set_sync_user_fields($user, $suser, $saveemptyfields);

                try {
                    $DB->update_record('user', $user);
                } catch (Exception $e) {
                    $this->addlog(get_string('cannotupdateuserx', 'tool_shezar_sync', $suser->idnumber) . ': ' .
                            $e->getMessage(), 'warn', 'updateusers');
                    $problemswhileapplying = true;
                    // Try to continue with other operations to this user.
                }

                // Update user password.
                if ($updatepassword) {
                    $userauth = get_auth_plugin(strtolower($user->auth));
                    if ($userauth->can_change_password()) {
                        if (!$userauth->user_update_password($user, $suser->password)) {
                            $this->addlog(get_string('cannotsetuserpassword', 'tool_shezar_sync', $user->idnumber),
                                    'warn', 'updateusers');
                            $problemswhileapplying = true;
                            // Try to continue with other operations to this user.
                        }
                    } else {
                        $this->addlog(get_string('cannotsetuserpasswordnoauthsupport', 'tool_shezar_sync', $user->idnumber),
                                'warn', 'updateusers');
                        $problemswhileapplying = true;
                        // Try to continue with other operations to this user.
                    }
                    unset($userauth);
                }

                // Using auth plugin that does not allow password changes, lets clear auth_forcepasswordchange setting.
                $userauth = get_auth_plugin(strtolower($user->auth));
                if (!$userauth->can_change_password()) {
                    set_user_preference('auth_forcepasswordchange', 0, $user->id);
                    set_user_preference('create_password', 0, $user->id);
                }
                unset($userauth);

                // Store user data for assignment sync later.
                $assign_sync_users[$suser->id] = $suser;
                // Update custom field data.
                $user = $this->put_custom_field_data($user, $suser, $saveemptyfields);

                $this->addlog(get_string('updateduserx', 'tool_shezar_sync', $suser->idnumber), 'info', 'updateusers');

                \core\event\user_updated::create_from_userid($user->id)->trigger();

                if ($suspenduser) {
                    \core\session\manager::kill_user_sessions($user->id);
                    \shezar_core\event\user_suspended::create_from_user($user)->trigger();
                }
            }
            $rsupdateaccounts->close();
            unset($user, $posdata); // Free memory.
        }

        // Pre-process the job assignment data, removing records that are empty and removing fields that won't be updated.
        $jafields = array(
            'jobassignmentidnumber',
            'jobassignmentfullname',
            'jobassignmentstartdate',
            'jobassignmentenddate',
            'posidnumber',
            'orgidnumber',
            'manageridnumber',
            'managerjobassignmentidnumber',
            'appraiseridnumber',
        );
        foreach ($assign_sync_users as $id => $suser) {
            $hasdatatoimport = false;
            foreach ($jafields as $jafield) {
                if (!isset($suser->$jafield)) {
                    // The column isn't in the import or it contains null. Either way, skip it.
                    unset($suser->$jafield);
                } else if ($suser->$jafield === "" && !$saveemptyfields) {
                    // It contains an empty string, but these should be ignored.
                    unset($suser->$jafield);
                } else {
                    // There is data to save (which might be empty string, but only if empty string isn't ignored).
                    $hasdatatoimport = true;
                }
            }
            if (!$hasdatatoimport) {
                unset($assign_sync_users[$id]);
                continue;
            }
        }

        // Process the job assignments after all the user records have been
        // created and updated so we know they're in the right state.
        foreach ($assign_sync_users as $id => $suser) {
            try {
                $result = $this->sync_user_job_assignments($suser->uid, $suser);
                if (!$result) {
                    unset($assign_sync_users[$id]);
                }
            } catch (Exception $e) {
                // We don't want this exception to stop processing so we will continue.
                // The code may have started a transaction. If it did then roll back the transaction.
                if ($DB->is_transaction_started()) {
                    $DB->force_transaction_rollback();
                }
                $this->addlog(get_string('cannotimportjobassignments', 'tool_shezar_sync', $suser->idnumber) . ': ' .
                    $e->getMessage(), 'warn', 'updateusers');
                $problemswhileapplying = true;
                unset($assign_sync_users[$id]); // Don't try to sync manager.
                continue; // Continue processing users.
            }
        }
        // Process dependant fields after processing all job assignments, so the job assignments (should) already exist.
        foreach ($assign_sync_users as $suser) {
            try {
                $this->sync_user_dependant_job_assignment_fields($suser->uid, $suser);
            } catch (Exception $e) {
                // We don't want this exception to stop processing so we will continue.
                // The code may have started a transaction. If it did then roll back the transaction.
                if ($DB->is_transaction_started()) {
                    $DB->force_transaction_rollback();
                }
                $this->addlog(get_string('cannotimportjobassignments', 'tool_shezar_sync', $suser->idnumber) . ': ' .
                    $e->getMessage(), 'warn', 'updateusers');
                $problemswhileapplying = true;
                continue; // Continue processing users.
            }
        }
        // Free memory used by user assignment array.
        unset($assign_sync_users);

        $this->get_source()->drop_table();
        $this->addlog(get_string('syncfinished', 'tool_shezar_sync'), 'info', 'usersync');

        if (!empty($this->config->linkjobassignmentidnumber)) {
            // A sync finished and it was set to link job assignments using idnumber. Never again link by first record.
            set_config('linkjobassignmentidnumber', true, 'shezar_sync');
        }

        return $issane && !$problemswhileapplying;
    }

    /**
     * Create a user
     *
     * @param stdClass $suser escaped sync user object
     * @param bool $saveemptyfields true if empty strings should erase data, false if the field should be ignored
     * @return boolean true if successful
     * @throws shezar_sync_exception
     */
    function create_user($suser, $saveemptyfields) {
        global $CFG, $DB;

        $transaction = $DB->start_delegated_transaction();

        // Prep a few params.
        $user = new stdClass;
        $user->username = core_text::strtolower($suser->username);  // Usernames always lowercase in moodle.
        $user->idnumber = $suser->idnumber;
        $user->confirmed = 1;
        $user->shezarsync = 1;
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->lang = $CFG->lang;
        $user->timecreated = time();
        $user->auth = isset($suser->auth) ? strtolower($suser->auth) : 'manual';
        $this->set_sync_user_fields($user, $suser, $saveemptyfields);

        try {
            $user->id = $DB->insert_record('user', $user);  // Insert user.
        } catch (Exception $e) {
            // Throws exception which will be captured by caller.
            $transaction->rollback(new shezar_sync_exception('user', 'createusers', 'cannotcreateuserx', $user->idnumber));
        }

        try {
            $userauth = get_auth_plugin(strtolower($user->auth));
        } catch (Exception $e) {
            // Throws exception which will be captured by caller.
            $transaction->rollback(new shezar_sync_exception('user', 'createusers', 'invalidauthforuserx', $user->auth));
        }

        if ($userauth->can_change_password()) {
            if (!isset($suser->password) || trim($suser->password) === '') {
                // Tag for password generation.
                set_user_preference('auth_forcepasswordchange', 1, $user->id);
                set_user_preference('create_password',          1, $user->id);
            } else {
                // Set user password.
                if (!$userauth->user_update_password($user, $suser->password)) {
                    $this->addlog(get_string('cannotsetuserpassword', 'tool_shezar_sync', $user->idnumber), 'warn', 'createusers');
                } else if (!empty($this->config->forcepwchange)) {
                    set_user_preference('auth_forcepasswordchange', 1, $user->id);
                }
            }
        }
        unset($userauth);
        // Update custom field data.
        $user = $this->put_custom_field_data($user, $suser, $saveemptyfields);

        $transaction->allow_commit();

        $event = \core\event\user_created::create(
            array(
                'objectid' => $user->id,
                'context' => context_user::instance($user->id),
            )
        );
        $event->trigger();

        return true;
    }

    /**
     * Store the custom field data for the given user.
     *
     * @param stdClass $user existing user object
     * @param stdClass $suser escaped sync user object
     * @param bool $saveemptyfields true if empty strings should erase data, false if the field should be ignored
     * @return stdClass
     */
    public function put_custom_field_data($user, $suser, $saveemptyfields) {
        global $CFG;

        $customfields = json_decode($suser->customfields);

        if ($customfields) {
            require_once($CFG->dirroot.'/user/profile/lib.php');

            foreach ($customfields as $name => $value) {
                if ($value === null) {
                    continue; // Null means "don't update the existing data", so skip this field.
                }

                if ($value === "" && !$saveemptyfields) {
                    continue; // CSV import and empty fields are not saved, so skip this field.
                }

                $profile = str_replace('customfield_', 'profile_field_', $name);
                // If the custom field is a menu, the option index will be set by function shezar_sync_data_preprocess.
                $user->{$profile} = $value;
            }
            profile_save_data($user, true);
        }

        return $user;
    }

    /**
     * Sync a user's position assignments
     *
     * @deprecated since 9.0.
     * @param userid
     * @param $suser
     * @return boolean true on success
     */
    function sync_user_assignments($userid, $suser) {
        throw new coding_exception('sync_user_assignments has been deprecated since 9.0. See deprecated function for more information.');
        // This function has been split into two parts, sync_user_job_assignments and
        // sync_user_dependant_job_assignment_fields. sync_user_job_assignments must be called for all imported records
        // before calling sync_user_dependant_job_assignment_fields.
    }

    /**
     * Sync a user's job assignments
     *
     * @param int $userid
     * @param stdClass $suser
     * @return boolean false if there was a problem and the job assignment could not be imported
     */
    public function sync_user_job_assignments($userid, $suser) {
        global $CFG, $DB;

        // If we have no job assignment info at all then we do not need to set a job assignment.
        // Note that manager data is saved in sync_user_dependant_job_assignment_fields.
        // Also note that job assignment idnumber is included here.
        if (!isset($suser->jobassignmentidnumber) &&
            !isset($suser->jobassignmentfullname) &&
            !isset($suser->jobassignmentstartdate) &&
            !isset($suser->jobassignmentenddate) &&
            !isset($suser->posidnumber) &&
            !isset($suser->orgidnumber) &&
            !isset($suser->appraiseridnumber)) {
            return true;
        }

        // At this point, we know we've got a record with some data that needs to be imported, even
        // if all fields are empty strings.

        $newjobdata = array();
        if (isset($suser->jobassignmentfullname)) {
            if ($suser->jobassignmentfullname === "") { // Don't check empty because "0" is a valid string.
                $newjobdata['fullname'] = null;
            } else {
                $newjobdata['fullname'] = $suser->jobassignmentfullname;
            }
        }

        if (isset($suser->jobassignmentstartdate)) {
            if (empty($suser->jobassignmentstartdate)) { // Empty string and 0.
                $newjobdata['startdate'] = null;
            } else {
                $newjobdata['startdate'] = $suser->jobassignmentstartdate;
            }
        }

        if (isset($suser->jobassignmentenddate)) {
            if (empty($suser->jobassignmentenddate)) { // Empty string or 0.
                $newjobdata['enddate'] = null;
            } else {
                $newjobdata['enddate'] = $suser->jobassignmentenddate;
            }
        }

        if (isset($suser->orgidnumber)) {
            if ($suser->orgidnumber === "") { // Don't check empty because "0" is a valid idnumber.
                $newjobdata['organisationid'] = null;
            } else {
                $newjobdata['organisationid'] = $DB->get_field('org', 'id', array('idnumber' => $suser->orgidnumber));
            }
        }

        if (isset($suser->posidnumber)) {
            if ($suser->posidnumber === "") { // Don't check empty because "0" is a valid idnumber.
                $newjobdata['positionid'] = null;
            } else {
                $newjobdata['positionid'] = $DB->get_field('pos', 'id', array('idnumber' => $suser->posidnumber));
            }
        }

        if (isset($suser->appraiseridnumber)) {
            if ($suser->appraiseridnumber === "") { // Don't check empty because "0" is a valid idnumber.
                $newjobdata['appraiserid'] = null;
            } else {
                $newjobdata['appraiserid'] = $DB->get_field('user', 'id', array('idnumber' => $suser->appraiseridnumber, 'deleted' => 0));
            }
        }

        // At this point, $newjobdata only contains job assignment data that we want to write to the database (except idnumber).

        if (!empty($this->config->linkjobassignmentidnumber)) {
            // Update or create job assignment with matching job assignment id number.

            if (!isset($suser->jobassignmentidnumber)) {
                // There is something to import, but it can't be done without a jaid.
                $this->addlog(get_string('jobassignmentidnumberemptyx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                return false;

            } else if ($suser->jobassignmentidnumber === "") {
                // It's an empty string, and empty strings are supposed to be processed. Can't use it as a job
                // assignment idnumber.
                $this->addlog(get_string('jobassignmentidnumberemptyx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                return false;

            } // Else there is a job assignment idnumber, so we can continue.

            // Create or update matching job assignment record for the user.
            $jobassignment = \shezar_job\job_assignment::get_with_idnumber($userid, $suser->jobassignmentidnumber, false);
            if (empty($jobassignment)) {
                // The specified job assignment record doesn't already exist.

                // Make sure creating a new record is allowed.
                if (empty($CFG->shezar_job_allowmultiplejobs)) {
                    $existingja = \shezar_job\job_assignment::get_first($userid, false);
                    if (!empty($existingja)) {
                        // Only one job assignment record can exist for each user.
                        $this->addlog(get_string('multiplejobassignmentsdisabledx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                        return false;
                    }
                }

                // Create a new job assignment with the given idnumber.
                $newjobdata['userid'] = $userid;
                $newjobdata['idnumber'] = $suser->jobassignmentidnumber;
                \shezar_job\job_assignment::create($newjobdata);

            } else {
                // The job assignment record already exists, so update it.
                if (!empty($newjobdata)) {
                    $jobassignment->update($newjobdata);
                } // Else the only job assignment data was the idnumber, but the record already contains it, so do nothing.
            }

        } else {
            // Update or create first job assignment for the user.

            // Job assignment id number is just another field to record.
            if (isset($suser->jobassignmentidnumber)) {
                if ($suser->jobassignmentidnumber === "") {
                    $this->addlog(get_string('jobassignmentidnumberemptyx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                    return false;
                } else {
                    $newjobdata['idnumber'] = $suser->jobassignmentidnumber;
                }
            }

            // Create or update first job assignment record for the user.
            $jobassignment = \shezar_job\job_assignment::get_first($userid, false);
            if (empty($jobassignment)) {
                if (isset($newjobdata['idnumber'])) {
                    $newjobdata['userid'] = $userid;
                    \shezar_job\job_assignment::create($newjobdata);
                } else {
                    // All job assignments must have a idnumber, so create a default idnumber for it.
                    \shezar_job\job_assignment::create_default($userid, $newjobdata);
                }
            } else {
                $jobassignment->update($newjobdata);
            }
        }

        return true;
    }

    /**
     * Sync a user's dependant job assignment fields, such as manager, which depend on other job assignment
     * records already existing.
     *
     * @param int $userid
     * @param stdClass $suser
     * @return boolean false if there was a problem and the data could not be imported
     */
    public function sync_user_dependant_job_assignment_fields($userid, $suser) {
        global $DB;

        // If we have no job assignment info at all we do not need to set a job assignment.
        // Note that job assignment idnumber is not included here, because it has been processed already.
        if (!isset($suser->manageridnumber) &&
            !isset($suser->managerjobassignmentidnumber)) {
            return true;
        }

        // At this point, we know we've got a record with some data that needs to be imported, even
        // if all fields are empty strings.

        $newjobdata = array();

        if (isset($suser->manageridnumber)) {
            // When manager is assigned, it must be referred by manager idnumber, because different managers can have the same
            // job assignment idnumber. Whereas manager's job assignment idnumber is optional.

            // Pre-calculate the managerid - if it is provided then it is used.
            if ($suser->manageridnumber !== "") { // Don't use empty check because "0" is a valid idnumber.
                $managerid = $DB->get_field('user', 'id', array('idnumber' => $suser->manageridnumber, 'deleted' => 0));
                // Shouldn't be possible, but lets check anyway.
                if (empty($managerid)) {
                    $this->addlog(get_string('managerassignmanagerxnotexist', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                    return false;
                }
            }

            if (empty($this->config->linkjobassignmentidnumber)) {
                // Manager jaid can only be provided if linking by idnumber (invalid config).
                if (isset($suser->managerjobassignmentidnumber)) {
                    $this->addlog(get_string('managerassigncanthavejaid', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                    return false;
                }
            } else {
                // Manager field is provided, we should be linking by jaid, but could be empty.

                // Manager jaid must be provided if linking by idnumber and manager is provided.
                if (!isset($suser->managerjobassignmentidnumber)) {
                    $this->addlog(get_string('managerassignwojaidx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                    return false;
                }

                // Manager and manager jaid must both be either "" or not "".
                if ($suser->manageridnumber === "" && $suser->managerjobassignmentidnumber !== "") {
                    $this->addlog(get_string('managerassignwoidnumberx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                    return false;
                }
                if ($suser->manageridnumber !== "" && $suser->managerjobassignmentidnumber === "") {
                    $this->addlog(get_string('managerassignwojaidx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                    return false;
                }

                // Managerjaid is provided, but could be empty, but only when manager is also empty.
                $managerjaid = $suser->managerjobassignmentidnumber;
            }

            // By now, we know we've got valid manager data (although we're not yet sure what that is, and could be empty).

            if (!empty($managerid) && !empty($managerjaid)) {
                // Both are provided.
                $managerja = \shezar_job\job_assignment::get_with_idnumber($managerid, $managerjaid, false);
                if (empty($managerja)) {
                    // Manager's job assignment needs to already exist, either before import or created during previous step.
                    $this->addlog(get_string('managerassignmissingmanagerjobx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                    return false;
                } else {
                    $newjobdata['managerjaid'] = $managerja->id;
                }

            } else if (!empty($managerid)) {
                // Only the manageridnumber field was provided. We know we're linking to first job assignment (due to previous tests).
                $managerja = \shezar_job\job_assignment::get_first($managerid, false);
                if (empty($managerja)) {
                    // The manager has no job assignments at all, so make one.
                    $managerja = \shezar_job\job_assignment::create_default($managerid);
                }
                $newjobdata['managerjaid'] = $managerja->id;

            } else {
                // Manager and jaid must both be empty. But we know managerid is set and not null. So erase the existing manager.
                // Doesn't matter if manager jaid is provided or not, we know it must be empty.
                $newjobdata['managerjaid'] = null;
            }
        } else if (isset($suser->managerjaidnumber)) {
            $this->addlog(get_string('managerassignwoidnumberx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
            return false;
        }

        // Only need to continue if there is data to import.
        if (empty($newjobdata)) {
            return true;
        }

        if (!empty($this->config->linkjobassignmentidnumber)) {
            // Update or create job assignment with matching job assignment id number.

            // Check that we've got a valid job assignment id. Should already have been logged if there's a problem, so just return.
            if (!isset($suser->jobassignmentidnumber)) {
                return false;
            } else if ($suser->jobassignmentidnumber === "") {
                return false;
            }

            // Create or update matching job assignment record for the user.
            $jobassignment = \shezar_job\job_assignment::get_with_idnumber($userid, $suser->jobassignmentidnumber, false);
            if (empty($jobassignment)) {
                // The specified job assignment record doesn't already exist.
                // This shouldn't happen, because it should have been created earlier.
                $this->addlog(get_string('managerassignmissingjobx', 'tool_shezar_sync', $suser), 'warn', 'updateusers');
                return false;

            } else {
                // The job assignment record already exists, so update it.
                $jobassignment->update($newjobdata);
            }

        } else {
            // Create or update first job assignment record for the user.
            // No need to look at job assignment idnumber, because it would have been processed already.
            $jobassignment = \shezar_job\job_assignment::get_first($userid, false);
            if (empty($jobassignment)) {
                // All job assignments must have a idnumber, so create a default idnumber for it.
                \shezar_job\job_assignment::create_default($userid, $newjobdata);
            } else {
                $jobassignment->update($newjobdata);
            }
        }

        return true;
    }

    /**
     * @param stdClass $user existing user object
     * @param stdClass $suser escaped sync user object
     * @param bool $saveemptyfields true if empty strings should erase data, false if the field should be ignored
     */
    function set_sync_user_fields(&$user, $suser, $saveemptyfields) {
        global $CFG;

        $fields = array('address', 'city', 'country', 'department', 'description',
            'email', 'firstname', 'institution', 'lang', 'lastname', 'firstnamephonetic',
            'lastnamephonetic', 'middlename', 'alternatename', 'phone1', 'phone2',
            'timemodified', 'timezone', 'url', 'username', 'suspended', 'emailstop', 'auth');

        $requiredfields = array('username', 'firstname', 'lastname', 'email');

        foreach ($fields as $field) {
            if (!isset($suser->$field)) {
                continue; // Null means "don't update the existing data", so skip this field.
            }

            if ($suser->$field === "" && !$saveemptyfields) {
                continue; // CSV import and empty fields are not saved, so skip this field.
            }

            if (in_array($field, $requiredfields) && trim($suser->$field) === "") {
                continue; // Required fields can't be empty, so skip this field.
            }

            // Handle exceptions first.
            switch ($field) {
                case 'username':
                    // Must be lower case.
                    $user->$field = core_text::strtolower($suser->$field);
                    break;
                case 'country':
                    if (!empty($suser->$field)) {
                        // Must be upper case.
                        $user->$field = core_text::strtoupper($suser->$field);
                    } else if (empty($user->$field) && isset($CFG->country) && !empty($CFG->country)) {
                        // Sync and target are both empty - so use the default country if set.
                        $user->$field = $CFG->country;
                    } else {
                        // No default set, replace the current data with an empty value.
                        $user->$field = "";
                    }
                    break;
                case 'city':
                    if (!empty($suser->$field)) {
                        $user->$field = $suser->$field;
                    } else if (empty($user->$field) && isset($CFG->defaultcity) && !empty($CFG->defaultcity)) {
                        // Sync and target are both empty - So use the default city.
                        $user->$field = $CFG->defaultcity;
                    } else {
                        // No default set, replace the current data with an empty value.
                        $user->$field = "";
                    }
                    break;
                case 'timemodified':
                    // Default to now.
                    $user->$field = empty($suser->$field) ? time() : $suser->$field;
                    break;
                case 'lang':
                    // Sanity check will check for validity and add log but we will still
                    // store invalid lang and it will default to $CFG->lang internally.
                    if (!empty($suser->$field)) {
                        $user->$field = $suser->$field;
                    }
                    break;
                default:
                    $user->$field = $suser->$field;
            }
        }

        // If there is no email, check the default email.
        $usedefaultemail = !empty($this->config->allowduplicatedemails) && !empty($this->config->defaultsyncemail);
        if (empty($suser->email) && empty($user->email) && $usedefaultemail) {
            $user->email = $this->config->defaultsyncemail;
        }

        $user->suspended = empty($suser->suspended) ? 0 : $suser->suspended;
    }

    /**
     * Check if the data contains invalid values
     *
     * @param string $synctable sync table name
     * @param string $synctable_clone sync clone table name
     *
     * @return boolean true if the data is valid, false otherwise
     */
    function check_sanity($synctable, $synctable_clone) {
        global $DB;

        // Get a row from the sync table, so we can check field existence.
        if (!$syncfields = $DB->get_record_sql("SELECT * FROM {{$synctable}}", null, IGNORE_MULTIPLE)) {
            return true; // Nothing to check.
        }

        $issane = array();
        $invalidids = array();
        // Get duplicated idnumbers.
        $badids = $this->get_duplicated_values($synctable, $synctable_clone, 'idnumber', 'duplicateuserswithidnumberx');
        $invalidids = array_merge($invalidids, $badids);
        // Get empty idnumbers.
        $badids = $this->check_empty_values($synctable, 'idnumber', 'emptyvalueidnumberx');
        $invalidids = array_merge($invalidids, $badids);

        // Get duplicated usernames.
        $badids = $this->get_duplicated_values($synctable, $synctable_clone, 'username', 'duplicateuserswithusernamex');
        $invalidids = array_merge($invalidids, $badids);
        // Get empty usernames.
        $badids = $this->check_empty_values($synctable, 'username', 'emptyvalueusernamex');
        $invalidids = array_merge($invalidids, $badids);
        // Check usernames against the DB to avoid saving repeated values.
        $badids = $this->check_values_in_db($synctable, 'username', 'duplicateusernamexdb');
        $invalidids = array_merge($invalidids, $badids);

        // Get empty firstnames. If it is provided then it must have a non-empty value.
        if (property_exists($syncfields, 'firstname')) {
            $badids = $this->check_empty_values($synctable, 'firstname', 'emptyvaluefirstnamex');
            $invalidids = array_merge($invalidids, $badids);
        }

        // Get empty lastnames. If it is provided then it must have a non-empty value.
        if (property_exists($syncfields, 'lastname')) {
            $badids = $this->check_empty_values($synctable, 'lastname', 'emptyvaluelastnamex');
            $invalidids = array_merge($invalidids, $badids);
        }

        // Check position start date is not larger than position end date.
        if (property_exists($syncfields, 'posstartdate') && property_exists($syncfields, 'posenddate')) {
            $badids = $this->get_invalid_start_end_dates($synctable, 'posstartdate', 'posenddate', 'posstartdateafterenddate');
            $invalidids = array_merge($invalidids, $badids);
        }

        // Check invalid language set.
        if (property_exists($syncfields, 'lang')) {
            $badids = $this->get_invalid_lang($synctable);
            $invalidids = array_merge($invalidids, $badids);
        }

        if (empty($this->config->allow_create)) {
            $badids = $this->check_users_unable_to_revive($synctable);
            $invalidids = array_merge($invalidids, $badids);
        }

        if (!isset($this->config->allowduplicatedemails)) {
            $this->config->allowduplicatedemails = 0;
        }
        if (!isset($this->config->ignoreexistingpass)) {
            $this->config->ignoreexistingpass = 0;
        }
        if (property_exists($syncfields, 'email') && !$this->config->allowduplicatedemails) {
            // Get duplicated emails.
            $badids = $this->get_duplicated_values($synctable, $synctable_clone, 'email', 'duplicateuserswithemailx');
            $invalidids = array_merge($invalidids, $badids);
            // Get empty emails.
            $badids = $this->check_empty_values($synctable, 'email', 'emptyvalueemailx');
            $invalidids = array_merge($invalidids, $badids);
            // Check emails against the DB to avoid saving repeated values.
            $badids = $this->check_values_in_db($synctable, 'email', 'duplicateusersemailxdb');
            $invalidids = array_merge($invalidids, $badids);
            // Get invalid emails.
            $badids = $this->get_invalid_emails($synctable);
            $invalidids = array_merge($invalidids, $badids);
        }

        // Get invalid options (in case of menu of choices).
        if (property_exists($syncfields, 'customfields')) {
            $badids = $this->validate_custom_fields($synctable);
            $invalidids = array_merge($invalidids, $badids);
        }

        // Get invalid positions.
        if (property_exists($syncfields, 'posidnumber')) {
            $badids = $this->get_invalid_org_pos($synctable, 'pos', 'posidnumber', 'posxnotexist');
            $invalidids = array_merge($invalidids, $badids);
        }

        // Get invalid orgs.
        if (property_exists($syncfields, 'orgidnumber')) {
            $badids = $this->get_invalid_org_pos($synctable, 'org', 'orgidnumber', 'orgxnotexist');
            $invalidids = array_merge($invalidids, $badids);
        }

        // The idea of this loop is to make sure that all users in the synctable are valid regardless of the order they are created.
        // Example: user1 is valid but his manager is not and his manager is checked later, so user1 will be marked as valid when he is not.
        // This loop avoids that behaviour by checking in each iteration if there are still invalid users.
        while (1) {
            // Get invalid managers and self-assigned users.
            if (property_exists($syncfields, 'manageridnumber')) {
                $badids = $this->get_invalid_roles($synctable, $synctable_clone, 'manager');
                $invalidids = array_merge($invalidids, $badids);
                $badids = $this->check_self_assignment($synctable, 'manageridnumber', 'selfassignedmanagerx');
                $invalidids = array_merge($invalidids, $badids);
            }

            // Get invalid appraisers and self-assigned users.
            if (property_exists($syncfields, 'appraiseridnumber')) {
                $badids = $this->get_invalid_roles($synctable, $synctable_clone, 'appraiser');
                $invalidids = array_merge($invalidids, $badids);
                $badids = $this->check_self_assignment($synctable, 'appraiseridnumber', 'selfassignedappraiserx');
                $invalidids = array_merge($invalidids, $badids);
            }

            if ($invalidids) {
                // Split $invalidids array into chunks as there are varying limits on the amount of parameters.
                $invalidids_multi = array_chunk($invalidids, $DB->get_max_in_params());
                foreach ($invalidids_multi as $invalidids) {
                    list($badids, $params) = $DB->get_in_or_equal($invalidids);
                    // Collect idnumber for records which are invalid.
                    $rs = $DB->get_records_sql("SELECT id, idnumber FROM {{$synctable}} WHERE id $badids", $params);
                    foreach ($rs as $id => $record) {
                        $issane[] = $record->idnumber;
                    }
                    $DB->delete_records_select($synctable, "id $badids", $params);
                    $DB->delete_records_select($synctable_clone, "id $badids", $params);
                    $invalidids = array();
                }
                unset($invalidids_multi);
            } else {
                break;
            }
        }

        return $issane;
    }

    /**
     * Get duplicated values for a specific field.
     *
     * If multiple job assignments is enabled then there can be duplicates for each user.
     * The field param accepts 'idnumber', 'username' or 'email' - if you want to provide
     * some other field then it should probably be added to the DISTINCT below.
     *
     * @param string $synctable sync table name
     * @param string $synctable_clone sync clone table name
     * @param string $field field name
     * @param string $identifier for logging messages
     *
     * @return array with invalid ids from synctable for duplicated values
     */
    function get_duplicated_values($synctable, $synctable_clone, $field, $identifier) {
        global $CFG, $DB;

        $params = array();
        $invalidids = array();
        $extracondition = '';
        if (empty($this->config->sourceallrecords)) {
            $extracondition = "WHERE deleted = ?";
            $params[0] = 0;
        }
        if (!empty($CFG->shezar_job_allowmultiplejobs) && !empty($this->config->linkjobassignmentidnumber)) {
            // These three fields must be unique. By doing a DISTINCT on these columns, we can find any records
            // that are genuinely not unique, rather than duplicates caused by multiple job assignments.
            $duplicatessubquery =
                "(SELECT DISTINCT idnumber, username, email FROM {{$synctable_clone}}) dis";
        } else {
            $duplicatessubquery = "{{$synctable_clone}}";
        }
        $sql = "SELECT id, idnumber, $field
                  FROM {{$synctable}}
                 WHERE $field IN (SELECT $field FROM $duplicatessubquery $extracondition GROUP BY $field HAVING count($field) > 1)";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            $this->addlog(get_string($identifier, 'tool_shezar_sync', $r), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Get invalid organisations or positions
     *
     * @param string $synctable sync table name
     * @param string $table table name (org or pos)
     * @param string $field field name
     * @param string $identifier for logging messages
     *
     * @return array with invalid ids from synctable for organisations or positions that do not exist in the database
     */
    function get_invalid_org_pos($synctable, $table, $field, $identifier) {
        global $DB;

        $params = array();
        $invalidids = array();
        $sql = "SELECT s.id, s.idnumber, s.$field
                  FROM {{$synctable}} s
       LEFT OUTER JOIN {{$table}} t ON s.$field = t.idnumber
                 WHERE s.$field IS NOT NULL
                   AND s.$field != ''
                   AND t.idnumber IS NULL";
        if (empty($this->config->sourceallrecords)) {
            $sql .= ' AND s.deleted = ?'; // Avoid users that will be deleted.
            $params[0] = 0;
        }
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            $this->addlog(get_string($identifier, 'tool_shezar_sync', $r), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Get invalid ids from synctable where start date is greater than the end date
     *
     * @param string $synctable sync table name
     * @param string $datefield1 column name for start date
     * @param string $datefield2 column name for end date
     * @param string $identifier for logging messages
     *
     * @return array with invalid ids from synctable where start date is greater than the end date
     */
    function get_invalid_start_end_dates($synctable, $datefield1, $datefield2, $identifier) {
        global $DB;

        $invalidids = array();
        $sql = "SELECT s.id, s.idnumber
                FROM {{$synctable}} s
                WHERE s.$datefield1 > s.$datefield2
                AND s.$datefield2 != 0";
        if (empty($this->config->sourceallrecords)) {
            $sql .= ' AND s.deleted = 0'; // Avoid users that will be deleted.
        }
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $r) {
            $this->addlog(get_string($identifier, 'tool_shezar_sync', $r), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Get invalid roles (such as managers or appraisers)
     *
     * @param string $synctable sync table name
     * @param string $synctable_clone sync clone table name
     * @param string $role Name of role to check e.g. 'manager' or 'appraiser'
     *                     There must be a {$role}idnumber field in the sync db table and '{$role}notexist'
     *                     language string in lang/en/tool_shezar_sync.php
     *
     * @return array with invalid ids from synctable for roles that do not exist in synctable nor in the database
     */
    function get_invalid_roles($synctable, $synctable_clone, $role) {
        global $DB;

        $idnumberfield = "{$role}idnumber";
        $params = array();
        $invalidids = array();
        $sql = "SELECT s.id, s.idnumber, s.{$idnumberfield}
                  FROM {{$synctable}} s
       LEFT OUTER JOIN {user} u
                    ON s.{$idnumberfield} = u.idnumber
                 WHERE s.{$idnumberfield} IS NOT NULL
                   AND s.{$idnumberfield} != ''
                   AND u.idnumber IS NULL
                   AND s.{$idnumberfield} NOT IN
                       (SELECT idnumber FROM {{$synctable_clone}})";
        if (empty($this->config->sourceallrecords)) {
            $sql .= ' AND s.deleted = ?'; // Avoid users that will be deleted.
            $params[0] = 0;
        }
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            $this->addlog(get_string($role.'xnotexist', 'tool_shezar_sync', $r), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Ensure options from menu of choices are valid
     *
     * @param string $synctable sync table name
     *
     * @return array with invalid ids from synctable for options that do not exist in the database
     */
    public function validate_custom_fields($synctable) {
        global $DB;

        $params = empty($this->config->sourceallrecords) ? array('deleted' => 0) : array();
        $invalidids = array();
        $rs = $DB->get_recordset($synctable, $params, '', 'id, idnumber, customfields');

        // Used to force a warning on the sync completion message without skipping users.
        $forcewarning = false;

        // Keep track of the fields that need to be tested for having unique values.
        $unique_fields = array ();

        foreach ($rs as $r) {
            $customfields = json_decode($r->customfields, true);
            if (!empty($customfields)) {
                foreach ($customfields as $name => $value) {
                    // Check each of the fields that have attributes that may affect
                    // whether the sync data will be accepted or not.
                    if ($this->customfieldsdb[$name]['required'] && trim($value) == '' && empty($this->customfieldsdb[$name]['default'])) {
                        $this->addlog(get_string('fieldrequired', 'tool_shezar_sync', (object)array('idnumber' => $r->idnumber, 'fieldname' => $name)), 'warn', 'checksanity');
                        $forcewarning = true;
                    }

                    if (isset($this->customfieldsdb[$name]['menu_options'])) {
                        if (trim($value) != '' && !in_array(core_text::strtolower($value), $this->customfieldsdb[$name]['menu_options'])) {
                            // Check menu value matches one of the available options, add an warning to the log if not.
                            $this->addlog(get_string('optionxnotexist', 'tool_shezar_sync', (object)array('idnumber' => $r->idnumber, 'option' => $value, 'fieldname' => $name)), 'warn', 'checksanity');
                            $forcewarning = true;
                        }
                    } else if ($this->customfieldsdb[$name]['forceunique']) {
                        // Note: Skipping this for menu custom fields as the UI does not enforce uniqueness for them.

                        $sql = "SELECT uid.data
                                  FROM {user} usr
                                  JOIN {user_info_data} uid ON usr.id = uid.userid
                                 WHERE usr.idnumber != :idnumber
                                   AND uid.fieldid = :fieldid
                                   AND uid.data = :data";
                        // Check that the sync value does not exist in the user info data.
                        $params = array ('idnumber' => $r->idnumber, 'fieldid' => $this->customfieldsdb[$name]['id'], 'data' => $value);
                        $cfdata = $DB->get_records_sql($sql, $params);
                        // If the value already exists in the database then flag an error. If not, record
                        // it in unique_fields to later verify that it's not duplicated in the sync data.
                        if ($cfdata) {
                            $this->addlog(get_string('fieldduplicated', 'tool_shezar_sync', (object)array('idnumber' => $r->idnumber, 'fieldname' => $name, 'value' => $value)), 'error', 'checksanity');
                            $invalidids[] = intval($r->id);
                            break;
                        } else {
                            $unique_fields[$name][intval($r->id)] = array ( 'idnumber' => $r->idnumber, 'value' => $value);
                        }
                    }
                }
            }
        }
        $rs->close();

        // Process any data that must have unique values.
        foreach ($unique_fields as $fieldname => $fielddata) {

            // We need to get all the field values into
            // an array so we can extract the duplicate values.
            $field_values = array ();
            foreach ($fielddata as $id => $values) {
                $field_values[$id] = $values['value'];
            }

            // Build up an array from the field values
            // where there are duplicates.
            $error_ids = array ();
            foreach ($field_values as $id => $value) {
                // Get a list of elements that match the current value.
                $matches = array_keys($field_values, $value);
                // If we've got more than one then we've got duplicates.
                if (count($matches) >  1) {
                    $error_ids = array_merge($error_ids, $matches);
                }
            }

            // The above process will create multiple occurences
            // for each problem value so remove the duplicates.
            $error_ids = array_unique ($error_ids);
            natsort($error_ids);

            // Loop through the error ids and produce a sync log entry.
            foreach ($error_ids as $id) {
                $log_data = (object) array('idnumber' => $fielddata[$id]['idnumber'], 'fieldname' => $fieldname, 'value' => $fielddata[$id]['value']);
                $this->addlog(get_string('fieldmustbeunique', 'tool_shezar_sync', $log_data), 'error', 'checksanity');
            }
            $invalidids = array_merge ($invalidids, $error_ids);
        }

        if ($forcewarning) {
            // Put a dummy record in here to flag a problem without skipping the user.
            $invalidids[] = 0;
        }

        $invalidids = array_unique($invalidids);

        return $invalidids;
    }

    /**
     * Avoid saving values from synctable that already exist in the database
     *
     * @param string $synctable sync table name
     * @param string $field field name
     * @param string $identifier for logging messages
     *
     * @return array with invalid ids from synctable for usernames or emails that are already registered in the database
     */
    function check_values_in_db($synctable, $field, $identifier) {
        global $DB;

        $params = array();
        $invalidids = array();
        $sql = "SELECT s.id, s.idnumber, s.$field
                  FROM {{$synctable}} s
            INNER JOIN {user} u ON s.idnumber <> u.idnumber
                   AND s.$field = u.$field";
        if (empty($this->config->sourceallrecords)) {
            $sql .= ' AND s.deleted = ?'; // Avoid users that will be deleted.
            $params[0] = 0;
        }
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            $this->addlog(get_string($identifier, 'tool_shezar_sync', $r), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Get users who are their own superior
     *
     * @param string $synctable sync table name
     * @param string $role that will be checked
     * @param string $identifier for logging messages
     *
     * @return array with invalid ids from synctable for users who are their own superior
     */
    function check_self_assignment($synctable, $role, $identifier) {
        global $DB;

        $params = array();
        $invalidids = array();
        $sql = "SELECT id, idnumber
                  FROM {{$synctable}}
                 WHERE idnumber = $role";
        if (empty($this->config->sourceallrecords)) {
            $sql .= ' AND deleted = ?'; // Avoid users that will be deleted.
            $params[0] = 0;
        }
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            $this->addlog(get_string($identifier, 'tool_shezar_sync', $r), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Check empty values for fields that are required
     *
     * @param string $synctable sync table name
     * @param string $field that will be checked
     * @param string $identifier for logging messages
     *
     * @return array with invalid ids from synctable for empty fields that are required
     */
    function check_empty_values($synctable, $field, $identifier) {
        global $DB;

        $params = array();
        $invalidids = array();
        $sql = "SELECT id, idnumber
                  FROM {{$synctable}}
                 WHERE $field = ''";
        if (empty($this->config->sourceallrecords) && $field != 'idnumber') {
            $sql .= ' AND deleted = ?'; // Avoid users that will be deleted.
            $params[0] = 0;
        }
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            $this->addlog(get_string($identifier, 'tool_shezar_sync', $r), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Check for users that will be revived where allowcreate is off
     *
     * @param string $synctable sync table name
     *
     * @return array with invalid ids from synctable for users who are marked not deleted in the file but deleted in the db
     */
    function check_users_unable_to_revive($synctable) {
        global $DB;

        $invalidids = array();
        $sql = "SELECT s.id, s.idnumber
                  FROM {{$synctable}} s
                  INNER JOIN {user} u ON s.idnumber = u.idnumber
                 WHERE u.deleted = 1";
        if (empty($this->config->sourceallrecords)) {
            // With sourceallrecords on we also need to check the deleted column in the sync table.
            $sql .= ' AND s.deleted = 0';
        }
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $r) {
            $this->addlog(get_string('cannotupdatedeleteduserx', 'tool_shezar_sync', $r->idnumber), 'error', 'checksanity');
            $invalidids[] = $r->id;
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Get invalid email addresses in the email field
     *
     * @param string $synctable sync table name
     *
     * @return array with invalid ids from synctable for invalid emails
     */
    protected function get_invalid_emails($synctable) {
        global $DB;

        $params = array();
        $invalidids = array();
        $extracondition = '';
        if (empty($this->config->sourceallrecords)) {
            $extracondition = "AND deleted = ?";
            $params[0] = 0;
        }
        $sql = "SELECT id, idnumber, email
                  FROM {{$synctable}}
                 WHERE email IS NOT NULL {$extracondition}";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            if (!validate_email($r->email)) {
                $this->addlog(get_string('invalidemailx', 'tool_shezar_sync', $r), 'error', 'checksanity');
                $invalidids[] = $r->id;
            }
            unset($r);
        }
        $rs->close();

        return $invalidids;
    }

    /**
     * Get invalid langauge in the lang field
     *
     * @param string $synctable sync table name
     *
     * @return array with a dummy invalid id record if there is a row with an invalid language
     */
    protected function get_invalid_lang($synctable) {
        global $DB;

        $forcewarning = false;
        $params = array();
        $invalidids = array();
        $extracondition = '';
        if (empty($this->config->sourceallrecords)) {
            $extracondition = "AND deleted = ?";
            $params[0] = 0;
        }
        $sql = "SELECT id, idnumber, lang
                  FROM {{$synctable}}
                WHERE lang != '' AND lang IS NOT NULL {$extracondition}";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $r) {
            if (!get_string_manager()->translation_exists($r->lang)) {
                // Add log entry for invalid language but don't skip user.
                $this->addlog(get_string('invalidlangx', 'tool_shezar_sync', $r), 'error', 'checksanity');
                $forcewarning = true;
            }
            unset($r);
        }
        $rs->close();

        if ($forcewarning) {
            // Put a dummy record in here to flag a problem without skipping the user.
            $invalidids[] = 0;
        }

        return $invalidids;
    }
}
