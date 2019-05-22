
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

	$csv_data = array();
	$line=array();
	
	if(isset($_POST["submit"]))
	{
	
		$file = $_FILES['file']['tmp_name'];
		$handle = fopen($file, "r");
		$row = 0;
		
			while ($line= fgetcsv($handle))
			{

				//var_dump($line);
			    $num = count($line);
				echo "<p> $num fields in line $row: <br /></p>\n";
				
				
				if($row >= 0)
				{
					
					$csv_data["username"] = $line[0];
					$csv_data["firstname"] = $line[1];
					$csv_data["lastname"] = $line[2];
					$csv_data["email"] = $line[3];
						
						$allResult[] = $csv_data; 
						//print_r($csv_data);
					// 	// put all csv file data into array
						
					$username = $csv_data["username"];
					echo $username;
					echo "</br>";
					$firstname = $csv_data["firstname"];
					echo $firstname;
					echo "</br>";
					$lastname = $csv_data["lastname"];
					echo $lastname;
					echo "</br>";
					$email = $csv_data["email"];
					echo $email;
					echo "</br>";
					$query=$DB->execute("UPDATE `mdl_user` SET email='".$email."' where username= '".$username."'");
									echo"UPDATE `mdl_user` SET email='".$email."' where username= '".$username."'";
									echo "done";
									echo "<br>";
									if($query){
										echo "done";
										echo "<br>";
									}else{
										echo "not done";
									}
								
								
								
								
							
					// 		}
				}
				
				$row++;
			
			}
    }
?>
