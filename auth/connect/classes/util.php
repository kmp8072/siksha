<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package auth_connect
 */

namespace auth_connect;

use \shezar_core\jsend;

/**
 * Class util
 *
 * @package auth_connect
 */
class util {
    /** Client is connected to the server */
    const SERVER_STATUS_OK = 0;

    /** Client delete was requested, but server was not told yet. Record will be deleted later. */
    const SERVER_STATUS_DELETING = 1;

    /** How much time do we give user to login on SSO server in seconds? */
    const REQUEST_LOGIN_TIMEOUT = 600;

    const MIN_API_VERSION = 1;
    const MAX_API_VERSION = 1;

    /**
     * Create unique hash for a field in a db table.
     *
     * @param string $table database table
     * @param string $field table field
     * @return string unique string in the form of a SHA1 hash
     */
    public static function create_unique_hash($table, $field) {
        global $DB;

        do {
            $secret = sha1(microtime(false) . uniqid('', true) . get_site_identifier() . $table . $field);
        } while ($DB->record_exists_select($table, "{$field} = ?", array($secret)));
        // The select allows comparison of text fields, Oracle is not supported!

        return $secret;
    }

    /**
     * Return sep url on server.
     *
     * @param \stdClass $server
     * @return string
     */
    public static function get_sep_url(\stdClass $server) {
        return "{$server->serverurl}/shezar/connect/sep.php";
    }

    /**
     * Return URL of SSO request on server.
     * @param \stdClass $server
     * @return string
     */
    public static function get_sso_request_url(\stdClass $server) {
        return "{$server->serverurl}/shezar/connect/sso_request.php";
    }

    /**
     * Enable new server registration.
     */
    public static function enable_registration() {
        $secret = self::create_unique_hash('config', 'value');
        set_config('setupsecret', $secret, 'auth_connect');
    }

    /**
     * Cancel new server registration.
     */
    public static function cancel_registration() {
        unset_config('setupsecret', 'auth_connect');
    }

    /**
     * Get new server registration info.
     *
     * @return string
     */
    public static function get_setup_secret() {
        return get_config('auth_connect', 'setupsecret');
    }

    /**
     * Is this a valid setup request?
     *
     * @param string $setupsecret
     * @return bool
     */
    public static function verify_setup_secret($setupsecret) {
        if (!is_enabled_auth('connect')) {
            return false;
        }

        if (empty($setupsecret)) {
            return false;
        }

        $secret = self::get_setup_secret();

        return ($secret AND $secret === $setupsecret);
    }

    /**
     * Select shezar Connect API version.
     *
     * @param int $minapiversion
     * @param int $maxapiversion
     * @return int 0 means error, anything else is api version compatible with this auth plugin.
     */
    public static function select_api_version($minapiversion, $maxapiversion) {
        if ($minapiversion > $maxapiversion) {
            return 0;
        }
        if ($maxapiversion < self::MIN_API_VERSION) {
            return 0;
        }
        if ($minapiversion > self::MAX_API_VERSION) {
            return 0;
        }
        if ($maxapiversion >= self::MAX_API_VERSION) {
            return self::MAX_API_VERSION;
        }
        return $maxapiversion;
    }

    /**
     * Edit server.
     *
     * @param \stdClass $data from auth_connect_form_server_edit
     */
    public static function edit_server($data) {
        global $DB;

        $server = new \stdClass();
        $server->id            = $data->id;
        $server->servercomment = $data->servercomment;
        $server->timemodified  = time();

        $DB->update_record('auth_connect_servers', $server);
    }

    /**
     * Delete server.
     *
     * @param \stdClass $data from auth_connect_form_server_delete
     */
    public static function delete_server($data) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        $server = $DB->get_record('auth_connect_servers', array('id' => $data->id), '*', MUST_EXIST);

        // Prevent any new log-ins and requests from server.
        $DB->set_field('auth_connect_servers', 'status', self::SERVER_STATUS_DELETING, array('id' => $server->id));

        $sql = "SELECT u.*
                  FROM {user} u
                  JOIN {auth_connect_users} cu ON cu.userid = u.id
                 WHERE cu.serverid = :serverid";
        $rs = $DB->get_recordset_sql($sql, array('serverid' => $server->id));
        foreach ($rs as $user) {
            if ($user->deleted != 0) {
                // Nothing to do, user is already deleted.

            } else if ($data->removeuser === 'delete') {
                user_delete_user($user);

            } else {
                $record = new \stdClass();
                $record->id = $user->id;
                $record->timemodified = time();
                $record->auth = $data->newauth;
                if ($user->suspended == 0 and $data->removeuser === 'suspend') {
                    $record->suspended = '1';
                }
                // Do not use user_update_user() here because it is messing with usernames!
                $DB->update_record('user', $record);
                \core\event\user_updated::create_from_userid($user->id)->trigger();
                if (isset($record->suspended)) {
                    $user = $DB->get_record('user', array('id' => $user->id));
                    \shezar_core\event\user_suspended::create_from_user($user)->trigger();
                }
                unset($record);
                \core\session\manager::kill_user_sessions($user->id);
            }
            $DB->delete_records('auth_connect_users', array('userid' => $user->id));
        }
        $rs->close();

        // Unprotect the cohorts, but keep them.
        $ccs = $DB->get_records('auth_connect_user_collections', array('serverid' => $server->id));
        foreach ($ccs as $cc) {
            $DB->set_field('cohort', 'component', '', array('id' => $cc->cohortid));
        }

        $DB->delete_records('auth_connect_user_collections', array('serverid' => $server->id));
        $DB->delete_records('auth_connect_users', array('serverid' => $server->id));
        $DB->delete_records('auth_connect_sso_requests', array('serverid' => $server->id));
        $DB->delete_records('auth_connect_sso_sessions', array('serverid' => $server->id));

        $DB->set_field('auth_connect_servers', 'timemodified', time(), array('id' => $server->id));

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'delete_client',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        // Keep the record until it is deleted properly on the server,
        // this prevents repeated registration problems.
        if ($result['status'] === 'success') {
            $DB->delete_records('auth_connect_servers', array('id' => $server->id));
        }
    }

    /**
     * Logout from the SSO session on master and all clients.
     *
     * @param \stdClass $ssosession
     */
    public static function force_sso_logout(\stdClass $ssosession) {
        global $DB;

        $server = $DB->get_record('auth_connect_servers', array('id' => $ssosession->serverid));
        if ($server) {
            $data = array(
                'serveridnumber' => $server->serveridnumber,
                'serversecret' => $server->serversecret,
                'service' => 'force_sso_logout',
                'ssotoken' => $ssosession->ssotoken,
            );

            // Ignore result, this function cannot fail.
            jsend::request(self::get_sep_url($server), $data);
        }

        // Just in case the web service request from master did not get back to this client.
        $DB->delete_records('auth_connect_sso_sessions', array('id' => $ssosession->id));
    }

    /**
     * Sync all users with connect server.
     *
     * @param \stdClass $server
     * @return bool success
     */
    public static function sync_users(\stdClass $server) {
        if ($server->status != self::SERVER_STATUS_OK) {
            return false;
        }
        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_users',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        if ($result['status'] !== 'success' or !isset($result['data']['users'])) {
            return false;
        }

        self::update_local_users($server, $result['data']['users']);

        return true;
    }

    /**
     * Update local users to match the list of server users.
     *
     * Note: Users fully deleted on Connect server are unconditionally
     *       deleted on clients too. Users may also disappear as a result of
     *       changed cohort membership - this case is controlled via removeuser
     *       setting.
     *
     * @param \stdClass $server
     * @param array $serverusers list of user records on TC server
     * @return void
     */
    public static function update_local_users(\stdClass $server, array $serverusers) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/authlib.php');

        // Fetch the complete list of current users into memory.
        $sql = "SELECT cu.serveruserid, cu.userid, u.deleted, u.suspended, u.id AS knownuser
                  FROM {auth_connect_users} cu
             LEFT JOIN {user} u ON u.id = cu.userid
                 WHERE cu.serverid = ?";
        $userinfos = $DB->get_records_sql($sql, array($server->id));

        foreach ($serverusers as $k => $serveruser) {
            if (isset($userinfos[$serveruser['id']])) {
                // Updating existing.
                $userinfo = $userinfos[$serveruser['id']];
                unset($userinfos[$serveruser['id']]); // This allows us to find users that disappeared due to cohort restriction.
            } else{
                // Adding or migrating.
                $userinfo = false;
            }

            if ($serveruser['deleted'] != 0) {
                if (!$userinfo) {
                    // User does not exist locally and never did, nothing to delete.
                    continue;
                }
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on this client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
                if ($userinfo->deleted == 0) {
                    // All server user deletes are propagated to clients.
                    $user = $DB->get_record('user', array('id' => $userinfo->userid));
                    delete_user($user);
                    continue;
                }
                // If we got here it means local user account is already deleted,
                // we want to keep the auth_connect_users record just in case they
                // somehow undelete the server account.
                continue;
            }

            // Create, update or undelete local user account.
            self::update_local_user($server, $serveruser);
        }

        // Deal with users that this client is not allowed to see any more,
        // this is the result of removing users from a cohort that restricts a client.
        $removeaction = get_config('auth_connect', 'removeuser');
        if ($removeaction == AUTH_REMOVEUSER_SUSPEND) {
            foreach ($userinfos as $userinfo) {
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on TC client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
                if ($userinfo->deleted == 0 and $userinfo->suspended == 0) {
                    $DB->set_field('user', 'suspended', '1', array('id' => $userinfo->userid));
                    $user = $DB->get_record('user', array('id' => $userinfo->userid));
                    \core\event\user_updated::create_from_userid($user->id)->trigger();
                    \shezar_core\event\user_suspended::create_from_user($user)->trigger();
                    continue;
                }
            }

        } else if ($removeaction == AUTH_REMOVEUSER_FULLDELETE) {
            foreach ($userinfos as $userinfo) {
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on TC client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
                if ($userinfo->deleted == 0) {
                    $user = $DB->get_record('user', array('id' => $userinfo->userid));
                    delete_user($user);
                    continue;
                }
            }

        } else {
            // This is for $removeaction == AUTH_REMOVEUSER_KEEP, we keep accounts unchanged.
            foreach ($userinfos as $userinfo) {
                if ($userinfo->knownuser === null) {
                    // Somebody deleted user record on TC client, this should not happen!
                    $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                    continue;
                }
            }
        }
    }

    /**
     * Create or update local user record.
     *
     * @param \stdClass $server
     * @param array $serveruser
     * @param bool $sso true during sso login (performance trick)
     * @return \stdClass local user record, null on error
     */
    public static function update_local_user(\stdClass $server, array $serveruser, $sso = false) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/user/lib.php");

        if ($serveruser['deleted'] != 0) {
            // Cannot sync deleted users, sorry.
            return null;
        }

        if ($serveruser['username'] === 'guest') {
            // Cannot sync guest accounts, sorry.
            return null;
        }

        $user = (object)$serveruser;
        $serveruser = (object)$serveruser;

        // Set local values.
        $user->mnethostid = $CFG->mnet_localhost_id;
        $user->auth       = 'connect';
        $user->confirmed  = '1'; // There is no way to confirm server account, luckily they cannot SSO without it.

        // Unset all site specific fields.
        unset($user->id);
        unset($user->timecreated);
        unset($user->timemodified);
        unset($user->firstaccess);
        unset($user->lastaccess);
        unset($user->lastlogin);
        unset($user->currentlogin);
        unset($user->lastip);
        unset($user->secret);
        unset($user->policyagreed);
        unset($user->shezarsync); // shezar sync flag does not get migrated to clients, it is for local sync only!

        // If server does not want to give us the password, keep whatever was there before.
        if (!isset($user->password)) {
            unset($user->password);
        }

        // Did we see this server user before?
        $sql = "SELECT cu.*, u.id AS knownuser
                  FROM {auth_connect_users} cu
             LEFT JOIN {user} u ON u.id = cu.userid
                 WHERE cu.serverid = :serverid AND cu.serveruserid = :serveruserid";
        $userinfo = $DB->get_record_sql($sql, array('serverid' => $server->id, 'serveruserid' => $serveruser->id));
        if ($userinfo) {
            if ($userinfo->knownuser === null) {
                // Weird somebody deleted client user record from DB,
                // let's pretend we did not see the user yet..
                $DB->delete_records('auth_connect_users', array('id' => $userinfo->id));
                $userinfo = false;
            } else {
                unset($userinfo->knownuser);
            }
        }

        if (!$userinfo and get_config('auth_connect', 'migrateusers')) {
            // Let's try to migrate the server user to existing local account..
            $mapfield = get_config('auth_connect', 'migratemap');
            $candidates = array();
            if ($mapfield === 'uniqueid') {
                $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.username = :username
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                $params = array('username' => self::create_local_username($server, $serveruser), 'mnethostid' => $CFG->mnet_localhost_id);
                $candidates = $DB->get_records_sql($sql, $params);

            } else if ($mapfield === 'email') {
                if (validate_email($user->email)) {
                    $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.email = :email
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                    $params = array('email' => $serveruser->email, 'mnethostid' => $CFG->mnet_localhost_id);
                    $candidates = $DB->get_records_sql($sql, $params);
                }

            } else if ($mapfield === 'idnumber') {
                if (!empty($serveruser->idnumber)) {
                    $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.idnumber = :idnumber
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                    $params = array('idnumber' => $serveruser->idnumber, 'mnethostid' => $CFG->mnet_localhost_id);
                    $candidates = $DB->get_records_sql($sql, $params);
                }

            } else if ($mapfield === 'username') {
                if (!empty($serveruser->username)) {
                    $sql = "SELECT u.id
                              FROM {user} u
                         LEFT JOIN {auth_connect_users} cu ON cu.userid = u.id
                             WHERE cu.id IS NULL AND u.deleted = 0 AND u.username = :username
                                   AND u.auth <> 'connect' AND u.mnethostid = :mnethostid
                          ORDER BY u.id ASC";
                    $params = array('username' => $serveruser->username, 'mnethostid' => $CFG->mnet_localhost_id);
                    $candidates = $DB->get_records_sql($sql, $params);
                }
            }

            if ($candidates) {
                $candidate = reset($candidates);
                $userinfo = new \stdClass();
                $userinfo->serverid     = $server->id;
                $userinfo->serveruserid = $serveruser->id;
                $userinfo->userid       = $candidate->id;
                $userinfo->timecreated  = time();
                $userinfo->id = $DB->insert_record('auth_connect_users', $userinfo);
            }
            unset($candidates);
        }

        $userupdated = false;
        $usercreated = false;
        $userundeleted = false;
        $usersuspended = false;
        $olduser = false;

        if ($userinfo) {
            $olduser = $DB->get_record('user', array('id' => $userinfo->userid), '*', MUST_EXIST);

            if ($olduser->deleted != 0) {
                if (preg_match('/^[0-9a-f]{32}$/i', $olduser->email)) {
                    // Undeleting regularly deleted user, we need to get some valid username and email.
                    $user->username = self::create_local_username($server, $serveruser);
                } else {
                    // Legacy hacky shezar delete by flipping the delete flag only.
                }
                $userundeleted = true;
            } else {
                // Regular user update - do not sync these fields, admin or user sync controls them.
                unset($user->username);
                unset($user->susppended);
                unset($user->deleted);
            }

            if (!empty($user->idnumber)) {
                if ($olduser->idnumber !== $user->idnumber and shezar_idnumber_exists('user', $user->idnumber, $olduser->id)) {
                    // No idnumber duplicates, sorry.
                    unset($user->idnumber);
                }
            }

            // Did the user info change?
            $columns = $DB->get_columns('user', true);
            $record = array();
            foreach ($columns as $k => $ignored) {
                if (!property_exists($user, $k)) {
                    // Missing info from server.
                    continue;
                }
                if ((string)$user->$k === $olduser->$k) {
                    continue;
                }
                $record[$k] = $user->$k;
            }

            $user->id = $olduser->id;
            if ($record) {
                if (isset($serveruser->suspended) and $serveruser->suspended != 0 and $olduser->suspended == 0) {
                    $usersuspended = true;
                }
                // NOTE: do NOT use update_user() because it is messing with usernames and other things!
                $record['id'] = $user->id;
                $record['timemodified'] = time();
                $DB->update_record('user', $record);
                $userupdated = true;
            }
            unset($record);

        } else {
            // Make sure there are no bogus fields, use the guest account as a template.
            $record = array();
            $columns = $DB->get_columns('user', true);
            foreach ($columns as $k => $ignored) {
                if (!property_exists($user, $k)) {
                    // Missing info from server.
                    continue;
                }
                $record[$k] = $user->$k;
            }

            $record['username'] = self::create_local_username($server, $serveruser);
            if (!isset($record['password'])) {
                $record['password'] = AUTH_PASSWORD_NOT_CACHED;
            }
            if (!empty($record['idnumber']) and shezar_idnumber_exists('user', $record['idnumber'])) {
                // No idnumber duplicates, sorry.
                $record['idnumber'] = '';
            }

            // Make sure the username is not taken, if yes skip this user completely.
            if ($DB->record_exists('user', array('username' => $record['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
                return null;
            }

            $transaction = $DB->start_delegated_transaction();
            $user->id = user_create_user($record, false, false);
            $usercreated = true;
            unset($record);

            $userinfo = new \stdClass();
            $userinfo->serverid     = $server->id;
            $userinfo->serveruserid = $serveruser->id;
            $userinfo->userid       = $user->id;
            $userinfo->timecreated  = time();
            $DB->insert_record('auth_connect_users', $userinfo);

            $transaction->allow_commit();
        }

        $userrecord = $DB->get_record('user', array('id' => $user->id), '*', MUST_EXIST);

        if ($sso) {
            // For performance reasons update extra user data only during login.
            $updatepictures = false;
            if ($usercreated and $userrecord->picture > 0) {
                $updatepictures = true;
            }
            if ($olduser and $userrecord->picture != $olduser->picture) {
                $updatepictures = true;
            }
            if ($updatepictures) {
                $usercontext = \context_user::instance($userrecord->id);
                $fs = get_file_storage();
                $fs->delete_area_files($usercontext->id, 'user', 'icon', 0);
                if ($userrecord->picture > 0) {
                    if (!empty($serveruser->pictures)) {
                        foreach ($serveruser->pictures as $filename => $data) {
                            $record = new \stdClass();
                            $record->contextid = $usercontext->id;
                            $record->component = 'user';
                            $record->filearea = 'icon';
                            $record->itemid = 0;
                            $record->filepath = '/';
                            $record->filename = $filename;
                            $fs->create_file_from_string($record, base64_decode($data));
                        }
                    }
                }
            }
        }

        // NOTE TL-7410: add code for user preference and custom profile fields sync here.

        // Trigger events after all changes are stored in DB.
        if ($userundeleted) {
            // Do not use standard undelete_user() because it has extra validation.
            \shezar_core\event\user_undeleted::create_from_user($userrecord)->trigger();

        } else if ($usercreated) {
            // Newly created user.
            \core\event\user_created::create_from_userid($userrecord->id)->trigger();
            if ($userrecord->suspended != 0) {
                \shezar_core\event\user_suspended::create_from_user($userrecord)->trigger();
            }

        } else if ($userupdated) {
            // Just an update.
            \core\event\user_updated::create_from_userid($userrecord->id)->trigger();
            if ($usersuspended) {
                \shezar_core\event\user_suspended::create_from_user($userrecord)->trigger();
            }
        }

        return $userrecord;
    }

    /**
     * Sync all users with connect server.
     *
     * @param \stdClass $server
     * @return bool success
     */
    public static function sync_user_collections(\stdClass $server) {
        if ($server->status != self::SERVER_STATUS_OK) {
            return false;
        }
        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_user_collections',
        );

        $result = jsend::request(self::get_sep_url($server), $data);

        if ($result['status'] !== 'success' or !is_array($result['data'])) {
            return false;
        }

        self::update_local_user_collections($server, $result['data']);

        return true;
    }

    /**
     * Sync local cohorts and other collection types if they are implemented.
     *
     * @param \stdClass $server
     * @param array $servercollections
     */
    public static function update_local_user_collections(\stdClass $server, array $servercollections) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $records = $DB->get_records('auth_connect_user_collections', array('serverid' => $server->id));
        $existing = array();
        foreach ($records as $record) {
            $existing[$record->serverid . '-' . $record->collectiontype . '-' . $record->collectionid] = $record;
        }

        foreach ($servercollections as $type => $servercollection) {
            foreach ($servercollection as $serveritem) {
                unset($existing[$server->id . '-' . $type . '-' . $serveritem['id']]);
                self::update_local_user_collection($server, $type, $serveritem);
            }
            foreach ($existing as $collection) {
                $DB->delete_records('auth_connect_user_collections', array('id' => $collection->id));
                if ($cohort = $DB->get_record('cohort', array('id' => $collection->cohortid))) {
                    cohort_delete_cohort($cohort);
                }
            }
        }
    }

    /**
     * Update one local cohort to match user collection item on server.
     * (Map one server cohort or server course to one local cohort.)
     *
     * @param \stdClass $server
     * @param string $type 'cohort' or 'course'
     * @param array $serveritem
     */
    public static function update_local_user_collection(\stdClass $server, $type, array $serveritem) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/cohort/lib.php");

        $serveritem = (object)$serveritem;

        $cohort = false;
        $map = $DB->get_record('auth_connect_user_collections',
            array('serverid' => $server->id, 'collectiontype' => $type, 'collectionid' => $serveritem->id));
        if ($map) {
            $cohort = $DB->get_record('cohort', array('id' => $map->cohortid));
            if (!$cohort) {
                // Somebody was messing with the cohort in DB.
                $DB->delete_records('auth_connect_user_collections', array('id' => $map));
            }
        }
        unset($map);

        $idnumber = 'tc_' . $type . '_' . $serveritem->id . '_' . $server->serveridnumber;  // Something unique.
        $name = ($type === 'course') ? $serveritem->fullname :  $serveritem->name;
        $description = ($type === 'course') ? $serveritem->summary : $serveritem->description;
        $descriptionformat = ($type === 'course') ? $serveritem->summaryformat : $serveritem->descriptionformat;

        if ($cohort) {
            $update = false;

            if ($cohort->name !== $name) {
                $cohort->name = $name;
                $update = true;
            }
            if ($cohort->description !== $description) {
                $cohort->description = $description;
                $update = true;
            }
            if ($cohort->descriptionformat !== $descriptionformat) {
                $cohort->descriptionformat = $descriptionformat;
                $update = true;
            }
            if ($cohort->idnumber != $idnumber) {
                $cohort->idnumber = $idnumber;
                $update = true;
            }
            if ($cohort->component !== 'auth_connect') {
                // Hands off, this is our cohort!
                $cohort->component = 'auth_connect';
                $update = true;
            }

            if ($update) {
                cohort_update_cohort($cohort);
                $cohort = $DB->get_record('cohort', array('id' => $cohort->id));
            }

        } else {
            $trans = $DB->start_delegated_transaction();

            $cohort = new \stdClass();
            $cohort->name              = $name;
            $cohort->contextid         = \context_system::instance()->id;
            $cohort->idnumber          = $idnumber;
            $cohort->description       = $description;
            $cohort->descriptionformat = $descriptionformat;
            $cohort->component         = 'auth_connect';
            $cohort->id = cohort_add_cohort($cohort, false);
            $cohort = $DB->get_record('cohort', array('id' => $cohort->id));

            $record = new \stdClass();
            $record->serverid       = $server->id;
            $record->collectiontype = $type;
            $record->collectionid   = $serveritem->id;
            $record->cohortid       = $cohort->id;
            $record->timecreated    = time();
            $DB->insert_record('auth_connect_user_collections', $record);

            $trans->allow_commit();
        }

        // Now sync the cohort members.

        $sql = "SELECT cu.serveruserid, cu.userid
                  FROM {user} u
                  JOIN {auth_connect_users} cu ON cu.userid = u.id
                  JOIN {cohort_members} cm ON cm.userid = u.id
                 WHERE cu.serverid = :serverid AND cm.cohortid = :cohortid";
        $current = $DB->get_records_sql_menu($sql, array('serverid' => $server->id, 'cohortid' => $cohort->id));

        foreach ($serveritem->members as $serveruser) {
            $serveruserid = $serveruser['id'];
            if (isset($current[$serveruserid])) {
                unset($current[$serveruserid]);
                continue;
            }
            $sql = "SELECT u.id
                      FROM {user} u
                      JOIN {auth_connect_users} cu ON cu.userid = u.id
                     WHERE cu.serverid = :serverid AND cu.serveruserid = :serveruserid AND u.deleted = 0";
            $user = $DB->get_record_sql($sql, array('serverid' => $server->id, 'serveruserid' => $serveruserid));
            if ($user) {
                cohort_add_member($cohort->id, $user->id);
            }
        }

        foreach ($current as $serveruserid => $userid) {
            cohort_remove_member($cohort->id, $userid);
        }
    }

    /**
     * Finish SSO request by setting up $USER and adding new user if necessary.
     *
     * @param \stdClass $server
     * @param string $ssotoken
     */
    public static function finish_sso(\stdClass $server, $ssotoken) {
        global $SESSION, $CFG, $DB;

        if (isloggedin() and !isguestuser()) {
            throw new \coding_exception('user must not be logged in yet');
        }

        // Fetch user info for given token.

        $data = array(
            'serveridnumber' => $server->serveridnumber,
            'serversecret' => $server->serversecret,
            'service' => 'get_sso_user',
            'ssotoken' => $ssotoken,
        );

        $url = self::get_sep_url($server);

        $result = jsend::request($url, $data);

        if ($result['status'] !== 'success') {
            $SESSION->loginerrormsg = get_string('ssologinfailed', 'auth_connect');
            $SESSION->authconnectssofailed = 1;
            redirect(get_login_url());
        }

        $serveruser = $result['data'];
        $user = self::update_local_user($server, $serveruser, true);

        if (!$user or $user->deleted != 0 or $user->suspended != 0) {
            // Cannot login on this client, sorry.
            $SESSION->loginerrormsg = get_string('ssologinfailed', 'auth_connect');
            $SESSION->authconnectssofailed = 1;
            redirect(get_login_url());
        }

        \core\session\manager::login_user($user);

        $ssosession = new \stdClass();
        $ssosession->sid          = session_id();
        $ssosession->ssotoken     = $ssotoken;
        $ssosession->serverid     = $server->id;
        $ssosession->serveruserid = $serveruser['id'];
        $ssosession->userid       = $user->id;
        $ssosession->timecreated  = time();

        $DB->insert_record('auth_connect_sso_sessions', $ssosession);

        if (isset($SESSION->wantsurl)) {
            $urltogo = $SESSION->wantsurl;
        } else {
            $urltogo = $CFG->wwwroot . '/';
        }

        // Clear all session flags.
        unset($SESSION->wantsurl);
        unset($SESSION->loginerrormsg);
        unset($SESSION->authconnectssofailed);

        redirect($urltogo);
    }

    /**
     * Validates the SSO may start, redirects if user already
     * logged in.
     */
    public static function validate_sso_possible() {
        global $CFG, $SESSION;

        if (!is_enabled_auth('connect')) {
            redirect($CFG->wwwroot . '/');
        }

        if (isloggedin() and !isguestuser()) {
            if (isset($SESSION->wantsurl)) {
                $urltogo = $SESSION->wantsurl;
            } else {
                $urltogo = $CFG->wwwroot . '/';
            }
            unset($SESSION->wantsurl);
            redirect($urltogo);
        }
    }

    /**
     * Request SSO session.
     *
     * @param \stdClass $server record from auth_connect_servers table
     * @return \moodle_url SSO request passed via web browser
     */
    public static function create_sso_request(\stdClass $server) {
        global $DB;

        if (!is_enabled_auth('connect')) {
            return null;
        }

        if (isloggedin() and !isguestuser()) {
            return null;
        }

        if (!session_id()) {
            // This should not happen, every normal web request must have sid.
            return null;
        }

        if ($server->status != self::SERVER_STATUS_OK) {
            return null;
        }

        $request = $DB->get_record('auth_connect_sso_requests', array('sid' => session_id()));
        if ($request and time() - $request->timecreated > self::REQUEST_LOGIN_TIMEOUT) {
            // Delete previous timed-out attempt and try again with different request id.
            $DB->delete_records('auth_connect_sso_requests', array('sid' => session_id()));
            $request = null;
        }

        if (!$request) {
            $request = new \stdClass();
            $request->serverid     = $server->id;
            $request->requesttoken = self::create_unique_hash('auth_connect_sso_requests', 'requesttoken');
            $request->sid          = session_id();
            $request->timecreated  = time();
            $request->id = $DB->insert_record('auth_connect_sso_requests', $request);
        }

        $requestparams = array('clientidnumber' => $server->clientidnumber, 'requesttoken' => $request->requesttoken);
        return new \moodle_url(self::get_sso_request_url($server), $requestparams);
    }

    /**
     * Get local user name.
     *
     * Note: username duplicates are not verified here intentionally.
     *
     * @param \stdClass $server
     * @param \stdClass $serveruser
     * @return string username for local user table
     */
    protected static function create_local_username(\stdClass $server, \stdClass $serveruser) {
        // This should be unique enough because serveridnnumber is complex and unique.
        return 'tc_' . $serveruser->id . '_' . $server->serveridnumber;
    }

    /**
     * Return notice if site not https.
     * @return string html fragment
     */
    public static function warn_if_not_https() {
        global $CFG, $OUTPUT;
        if (strpos($CFG->wwwroot, 'https://') !== 0) {
            return $OUTPUT->notification(get_string('errorhttp', 'auth_connect'), 'notifyproblem');
        }
        return '';
    }
}
