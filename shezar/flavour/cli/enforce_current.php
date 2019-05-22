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
 * @package shezar_flavour
 */

use \shezar_flavour\helper;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir. '/clilib.php');

// CLI options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
    ),
    array(
        'h' => 'help'
    )
);

// Display help.
if (!empty($options['help'])) {
    echo "
Utility for enforcing of current flavour.

Options:
-h, --help              Print out this help

Example from shezar root directory:
\$ sudo -u www-data php shezar/flavour/cli/enforce_current.php
";
    // Exit with error unless we're showing this because they asked for it.
    exit(empty($options['help']) ? 1 : 0);
}

$admin = get_admin();
\core\session\manager::set_user($admin);

$flavour = helper::get_active_flavour_definition();

if ($flavour) {
    $name = $flavour->get_name();
    $dirname = preg_replace('/^flavour_/', '', $flavour->get_component());
    echo "Enforcing flavour $name ($dirname)...";
    helper::set_active_flavour($flavour->get_component());
    echo " done\n";

} else {
    $current = get_config('shezar_flavour', 'currentflavour');
    if ($current) {
        helper::set_active_flavour('');
    }
    echo "No flavour is active\n";
}

exit(0);
