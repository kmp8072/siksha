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



$strheading = "Custom Notification";

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/custom_notification/dashboard.php'));

$PAGE->set_title($strheading);

$PAGE->navbar->add($strheading);





require_login();







echo $OUTPUT->header();

global $SESSION;

$query="select * from mdl_course where visible=1";
$courses=$DB->get_records_sql($query);

?>

<h2>Raise or See status of Your Ticket</h2>


<?php


// $servername=$_SERVER['SERVER_NAME'];
// echo $servername;
//print_object($USER);

   $username=$USER->username;
   $useremail=$USER->email;
   $ipaddress=$_SERVER['REMOTE_ADDR'];
   $servername_1=$_SERVER['SERVER_NAME'];
   $servername=servername;


?>

        <!-- <label>Male</label>
        <br> -->
             

                      

<link href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
<!-- <script src="//netdna.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
<script src="//code.jquery.com/jquery-1.11.1.min.js"></script> -->
<!------ Include the above in your HEAD tag ---------->

<link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/jquery.bootstrapvalidator/0.5.2/css/bootstrapValidator.min.css"/>
<!-- <script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/jquery.bootstrapvalidator/0.5.2/js/bootstrapValidator.min.js"></script> -->
<link href="css/custom.css" rel="stylesheet">

<div class="container">
  <div class="row">

    <button style=" margin:auto;
  display:block;" onclick="myFunction()">Click Here</button>
    
  </div>
</div>                 

 <?php
// $table = new html_table(array('id'=>'mytable'));
// $table->set_attribute('class', 'mytable');

// $table->head = array('Rank','FirstName','LastName','Actual Competency Score','Desire Score');

// foreach($alldetails as $record)
//     {    $rid= ++$i;
// 		 $firstname=$record->firstname;
// 		 $lastname=$record->lastname;
// 		 $Competency_score=3150;
// 		 $score=$record->score;
//          $table->data[] = array($rid,$firstname,$lastname,$Competency_score,$score);
//      }


// echo html_writer::table($table);





echo $OUTPUT->footer();




?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<script>
function myFunction() {

//alert("myFunction called");

  // var username=<?php echo $username;  ?>;
  // var useremail=<?php echo $useremail;  ?>;
  // var ipaddress=<?php echo $ipaddress;  ?>;
  // var servername=<?php echo $servername;  ?>;

  // alert(username);
  // alert(useremail);
  // alert(ipaddress);
  // alert(servername);

  // return;
    window.open("http://192.168.1.18/helpdesk/index1.php?username=<?php echo $username; ?>&useremail=<?php echo $useremail; ?>&ipaddress=<?php echo $ipaddress; ?>&servername=<?php echo $servername; ?>");
}
</script>
<script src="js/custom.js"></script>



