
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
 * Moodle frontpage.
 *
 * @package    core
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once('config.php');
require_once('lib/filelib.php');
?>
<html>
<body>

<form method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="file" id="fileToUpload">
    <input type="submit" value="Upload Image" name="submit">
</form>


<?php
	echo "hi21";
	global $DB;

	$csv_data = array();
	
	if(isset($_POST["submit"]))
	{
	
		$file = $_FILES['file']['tmp_name'];
		$handle = fopen($file, "r");
		$row = 0;
		
			while ($line = fgetcsv($handle))
			{
			    $num = count($line);
				echo "<p> $num fields in line $row: <br /></p>\n";
				
				
				if($row >= 0)
				{
					
					$csv_data["idnumber"] = $line[0];
					$csv_data["timemodified"] = $line[1];
					$csv_data["deleted"] = $line[2];
					$csv_data["username"] = $line[3];
					$csv_data["firstname"] = $line[4];
					$csv_data["lastname"] = $line[5];
					$csv_data["email"] = $line[6];
					$csv_data["password"] = $line[7];
					$csv_data["customfield_Unit"] = $line[8];
					$csv_data["customfield_Region"] = $line[9];
					$csv_data["customfield_PLArea"] = $line[10];
					$csv_data["customfield_Location"] = $line[11];
					$csv_data["customfield_State"] = $line[12];
					$csv_data["customfield_Department"] = $line[13];
					$csv_data["customfield_Designation"] = $line[14];
					$csv_data["customfield_FAEmpID"] = $line[15];
					$csv_data["customfield_FAName"] = $line[16];
					$csv_data["jobassignmentidnumber"] = $line[17];
					$csv_data["manageridnumber"] = $line[18];
					$csv_data["customfield_Mobile"] = $line[19];
					$csv_data["phone1"] = $line[20];
						
						$allResult[] = $csv_data; 
						print_r($csv_data);
						// put all csv file data into array

						$idnum = $csv_data["idnumber"];
						$timemodified = $csv_data["timemodified"];
						$deleted = $csv_data["deleted"];
						$username =	$csv_data["username"];
						$firstname = $csv_data["firstname"];
						$lastname =	$csv_data["lastname"];
						$email = $csv_data["email"];
						$password =	$csv_data["password"];
						$customfield_Unit =	$csv_data["customfield_Unit"];
						$customfield_Region = $csv_data["customfield_Region"];
						$customfield_PLArea = $csv_data["customfield_PLArea"];
						$customfield_Location =	$csv_data["customfield_Location"];
						$customfield_State = $csv_data["customfield_State"];
						$customfield_Department = $csv_data["customfield_Department"];
						$customfield_Designation =	$csv_data["customfield_Designation"];
						$customfield_FAEmpID =  $csv_data["customfield_FAEmpID"];
						$customfield_FAName = $csv_data["customfield_FAName"];
						$jobassignmentidnumber = $csv_data["jobassignmentidnumber"];
						$manageridnumber = $csv_data["manageridnumber"];
						$customfield_Mobile = $csv_data["customfield_Mobile"];
						$phone1 = $csv_data["phone1"];


						 // for insert record code.....

						$record = new stdClass();
						$record->id = '';
						$record->auth = 'manual';
						$record->username = $username;
						$record->password = $password;
						$record->idnumber = $idnum;
						$record->firstname = $firstname;
						$record->lastname = $lastname;
						$record->email = $email;
						$record->phone1 = $phone1;
						//$record->timemodified = $timemodified;
						$lastinsertid = $DB->insert_record('user', $record, false);

						if($lastinsertid){

							//print_r($lastinsertid);

							echo "yes";
							echo "</br>";

							$reuserid=$DB->get_record_sql("SELECT `id` FROM `mdl_user` WHERE `idnumber` = ".$idnum." ");
							$use=$reuserid->id;

							print_r($reuserid);

     
							$record2 = new stdClass();
							$record2->id = '';
							$record2->userid = $use;
							$record2->fieldid = 1;
							$record2->data = $username;
							$record2->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid1 = $DB->insert_record('user_info_data', $record2, false);

							$record3 = new stdClass();
							$record3->id = '';
							$record3->userid = $use;
							$record3->fieldid = 2;
							$record3->data = $customfield_Unit;
							$record3->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid2 = $DB->insert_record('user_info_data', $record3, false);

							$record4 = new stdClass();
							$record4->id = '';
							$record4->userid = $use;
							$record4->fieldid = 3;
							$record4->data = $customfield_Region;
							$record4->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid3 = $DB->insert_record('user_info_data', $record4, false);

							$record5 = new stdClass();
							$record5->id = '';
							$record5->userid = $use;
							$record5->fieldid = 4;
							$record5->data = $customfield_PLArea;
							$record5->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid4 = $DB->insert_record('user_info_data', $record5, false);


							$customfield_Location
							$record6 = new stdClass();
							$record6->id = '';
							$record6->userid = $use;
							$record6->fieldid = 5;
							$record6->data = $customfield_Location;
							$record6->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid5 = $DB->insert_record('user_info_data', $record6, false);


							$record7 = new stdClass();
							$record7->id = '';
							$record7->userid = $use;
							$record7->fieldid = 6;
							$record7->data = $customfield_State;
							$record7->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid6 = $DB->insert_record('user_info_data', $record7, false);

							$record8 = new stdClass();
							$record8->id = '';
							$record8->userid = $use;
							$record8->fieldid = 7;
							$record8->data = $customfield_Department;
							$record8->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid7 = $DB->insert_record('user_info_data', $record8, false);

							$record9 = new stdClass();
							$record9->id = '';
							$record9->userid = $use;
							$record9->fieldid = 8;
							$record9->data = $customfield_Designation;
							$record9->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid8 = $DB->insert_record('user_info_data', $record9, false);

							$record10 = new stdClass();
							$record10->id = '';
							$record10->userid = $use;
							$record10->fieldid = 9;
							$record10->data = $customfield_FAEmpID;
							$record10->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid9 = $DB->insert_record('user_info_data', $record10, false);

							$record11 = new stdClass();
							$record11->id = '';
							$record11->userid = $use;
							$record11->fieldid = 10;
							$record11->data = $customfield_FAName;
							$record11->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid10 = $DB->insert_record('user_info_data', $record11, false);

							$record12 = new stdClass();
							$record12->id = '';
							$record12->userid = $use;
							$record12->fieldid = 11;
							$record12->data = $phone1;;
							$record12->dataformat = 0;
							//$record->timemodified = $timemodified;
							$lastinsertid10 = $DB->insert_record('user_info_data', $record12, false);

						}

						//for update record also write code here...


					
				}
				
				$row++;
			
			}
    }
?>
