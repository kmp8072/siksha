<?php

// This file is part of Moodle - http://moodle.org/

//

// Moodle is free software: you can redistribute it and/or modify

// it under the terms of the GNU General Public License as published by

// the Free Software Foundation, either version 3 of the License, or

// (at your option) any later version.

//

// Moodle is distributed in the hope that it will be useful,

// but WITHOUT ANY WARRANTY; without even the implied warranty of

// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

// GNU General Public License for more details.

//

// You should have received a copy of the GNU General Public License

// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.



/**

 * Register Execution Smart Klass Server Connection

 *

 * @package    local_virtual_class

 * @copyright  KlassData <kttp://www.klassdata.com>

 * @author     Oscar Ruesga <oscar@klassdata.com>

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

 */

//error_reporting(0);

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');

$strheading = "BE Live Report";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($strheading);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/bereport/dashboard.php'));
$PAGE->set_title($strheading);

$PAGE->navbar->add($strheading);

?>
<html>
<head>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

</head>

<?php
require_login();
echo $OUTPUT->header();
global $SESSION;
global $USER, $DB,$CFG;
//$sql = $DB->get_records_sql("Select * from mdl_problemstatement");
//print_r($sql);

?>
<body>
	<h2>Reports</h2>

	<a href = "report_user_post_likes.php"><input type="button" name="statement" value="User Post Likes"></a><br>
	<a href = "report_user_post_comments.php"><input type="button" name="comp" value="User Post Comments"></a><br>
	<a href = "report_app_users.php"><input type="button" name="drafthistory" value="App User"></a><br>
	
	<a href = "users_attempted_E-Modules.php"><input type="button" name="drafthistory" value="Users Attempted E-Modules"></a><br>
</body>	
<?php

echo $OUTPUT->footer();

?>