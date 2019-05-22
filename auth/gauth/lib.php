<?php
/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2014 onwards shezar Learning Solutions LTD
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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @author Piers Harding
 * @package shezar
 * @subpackage gauth
 */

/**
 * Copied from moodlelib:authenticate_user_login()
 *
 * WHY? because I need to hard code the plugins to auth_gauth, and this user
 * may be set to any number of other types of login method
 *
 * First of all - make sure that they aren't nologin - we don't mess with that!
 *
 *
 * Given a username and password, this function looks them
 * up using the currently selected authentication mechanism,
 * and if the authentication is successful, it returns a
 * valid $user object from the 'user' table.
 *
 * Uses auth_ functions from the currently active auth module
 *
 * After authenticate_user_login() returns success, you will need to
 * log that the user has logged in, and call complete_user_login() to set
 * the session up.
 *
 * @uses $CFG
 * @param string $username  User's username (with system magic quotes)
 * @param string $password  User's password (with system magic quotes)
 * @return user|flase A {@link $USER} object or false if error
 */
function auth_gauth_authenticate_user_login($username, $password) {
    global $CFG, $DB;

    // ensure that only gauth auth module is chosen
    $authsenabled = get_enabled_auth_plugins();

    if ($user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id)) {
        $auth = empty($user->auth) ? 'manual' : $user->auth;  // use manual if auth not set
        if (!empty($user->suspended)) {
            $failurereason = AUTH_LOGIN_SUSPENDED;
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            auth_gauth_err('[client '.getremoteaddr()."]  $CFG->wwwroot  Suspended Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }
        if ($auth=='nologin' or !is_enabled_auth($auth)) {
            $failurereason = AUTH_LOGIN_SUSPENDED;
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            auth_gauth_err('[client '.getremoteaddr()."]  $CFG->wwwroot  Disabled Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }
    } else {
        // check if there's a deleted record (cheaply)
        if ($DB->get_field('user', 'id', array('username' => $username, 'deleted' => 1))) {
            $failurereason = AUTH_LOGIN_NOUSER;
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                    'reason' => $failurereason)));
            $event->trigger();
            auth_gauth_err('[client '.$_SERVER['REMOTE_ADDR']."]  $CFG->wwwroot  Deleted Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        // Do not try to authenticate non-existent accounts when user creation is disabled.
        if (!empty($CFG->authpreventaccountcreation)) {
            $failurereason = AUTH_LOGIN_NOUSER;

            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                    'reason' => $failurereason)));
            $event->trigger();

            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Unknown user, can not create new accounts:  $username  ".
                    $_SERVER['HTTP_USER_AGENT']);
            return false;
        }


        $auths = $authsenabled;
        $user = new stdClass();
        $user->id = 0;     // User does not exist
    }

    // hard code only gauth module
    $auths = array('gauth');
    foreach ($auths as $auth) {
        $authplugin = get_auth_plugin($auth);

        // on auth fail fall through to the next plugin
        auth_gauth_err($auth.' plugin');
        if (!$authplugin->user_login($username, $password)) {
            continue;
        }

        // successful authentication
        if ($user->id) {                          // User already exists in database
            if (empty($user->auth)) {             // For some reason auth isn't set yet
                $DB->set_field('user', 'auth', $auth, array('username' => $username));
                $user->auth = $auth;
            }

            // we don't want to upset the existing authentication schema for the user
            if ($authplugin->is_synchronised_with_external()) { // update user record from external DB
                $user = update_user_record($username);
            }
        } else {
            // if user not found, create him
            $user = create_user_record($username, $password, $auth);
        }

        $authplugin->sync_roles($user);

        foreach ($authsenabled as $hau) {
            $hauth = get_auth_plugin($hau);
            $hauth->user_authenticated_hook($user, $username, $password);
        }

        if (empty($user->id)) {
            $failurereason = AUTH_LOGIN_NOUSER;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                        'reason' => $failurereason)));
            $event->trigger();

            return false;
        }

        if (!empty($user->suspended)) {
            // Just in case some auth plugin suspended account.
            $failurereason = AUTH_LOGIN_SUSPENDED;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $username, 'reason' => $failurereason)));
            $event->trigger();
            auth_gauth_err('[client '.getremoteaddr()."]  $CFG->wwwroot  Suspended Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        return $user;
    }

    // Failed if all the plugins have failed.
    if (debugging('', DEBUG_ALL)) {
        auth_gauth_err('[client '.getremoteaddr()."]  $CFG->wwwroot  Failed Login:  $username  ".$_SERVER['HTTP_USER_AGENT']);
    }

    if ($user->id) {
        login_attempt_failed($user);
        $failurereason = AUTH_LOGIN_FAILED;
        // Trigger login failed event.
        $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                'other' => array('username' => $username, 'reason' => $failurereason)));
        $event->trigger();
    } else {
        $failurereason = AUTH_LOGIN_NOUSER;
        // Trigger login failed event.
        $event = \core\event\user_login_failed::create(array('other' => array('username' => $username,
                'reason' => $failurereason)));
        $event->trigger();
    }

    return false;
}

/**
 * Add slashes for single quotes and backslashes
 * so they can be included in single quoted string
 * (for config.php)
 */
function auth_gauth_addsingleslashes($input){
    return preg_replace("/(['\\\])/", "\\\\$1", $input);
}

/**
 *  error log wrapper
 * @param string $msg
 */
function auth_gauth_err($msg) {
    global $CFG;

    // check if we are debugging
    if (! $CFG->debug == DEBUG_DEVELOPER) {
        return;
    }
    error_log('auth/gauth: '.$msg);
}
