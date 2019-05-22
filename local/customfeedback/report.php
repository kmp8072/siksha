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

$strheading = "Feedback Report";

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/customfeedback/report.php'));

$PAGE->set_title($strheading);

$PAGE->navbar->add($strheading);

require_login();


echo $OUTPUT->header();

?>

<h2>Feedback Report</h2>





<?php


global $SESSION,$USER;

/*$limit = 2;  
if (isset($_GET["page"])) { $page  = $_GET["page"]; } else { $page=1; };  
$start_from = ($page-1) * $limit;  
LIMIT $start_from, $limit
*/

 echo  "<div id='content'>
 		<center><table id= 'mytable' border=1>
        <tr>
        <th>Sr No.</th>
        <th>Username</th>
        <th>Firstname</th>
        <th>Lastname</th>
		<th>Course</th>
        <th>Filename</th>
        </tr>";

$userpdfdetails = $DB->get_records_sql("SELECT * FROM {usercompletion_pdf}");


$i=1;
echo "<tr>";
foreach($userpdfdetails as $userpdfdetail){
	
								echo "<td>$i</td>";
								echo "<td>$userpdfdetail->username</td>";
								echo "<td>$userpdfdetail->firstname</td>";
								echo "<td>$userpdfdetail->lastname</td>";
								echo "<td>$userpdfdetail->course</td>";
							
								
								echo "<td><a href='".$CFG->wwwroot."/pix/pdfimages/$userpdfdetail->filename' target='_blank'>Download Certificate</a></td>";
								echo "</tr>";
$i++;
}

echo "</table></center></div>";

/*$total_records  = count($userpdfdetails);
$total_pages = ceil($total_records / $limit);  
echo "<ul class='pagination'>";
echo "<li><a href='http://localhost/nucleusremui/local/report/report.php?page=".($page-1)."' class='button'>Previous</a></li>"; 

for ($i=1; $i<=$total_pages; $i++) {  
    echo "<li><a href='http://localhost/nucleusremui/local/report/report.php?page=".$i."'>".$i."</a></li>";
};  

echo "<li><a href='http://localhost/nucleusremui/local/report/report.php?page=".($page+1)."' class='button'>Next</a></li>";
echo "</ul>";  */
echo $OUTPUT->footer();
?>
<style>
#mytable {
    font-family: "Trebuchet MS", Arial, Helvetica, sans-serif;
    border-collapse: collapse;
    width: 100%;
}

#mytable td, #mytable th {
    border: 1px solid #ddd;
    padding: 8px;
}

#mytable tr:nth-child(even){background-color: #f2f2f2;}

#mytable tr:hover {background-color: #ddd;}

#mytable th {
    padding-top: 12px;
    padding-bottom: 12px;
    text-align: left;
    background-color: #625e5e;
    color: white;
}
</style>

