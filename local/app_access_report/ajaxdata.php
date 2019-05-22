<?php   

	require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

    global $USER, $DB, $CFG;

//require_login();

	if (isset($_POST['page'])) {
	
	include 'submitteddata.php';

	//print_object($user_data_objects);

	?>

	<table class="table table-responsive" id="usertable">
     <thead>
         <tr>
             <th>Employee ID</th>
             <th>Fullname</th>
             <th>Email</th>
             <th>Region</th>
             <th>P & L Area</th>
             <th>Location</th>
             <th>State</th>
             <th>Department</th>
             <th>Designation</th>
             <th>Guru ID</th>
             <th>Guru name</th>
             <th>LMS registration date</th>
             <th>LMS first access time</th>
             <th>LMS last login</th>
             <th>Action</th>
         </tr>
     </thead>
     <tbody>
      <?php
             foreach ($user_data_objects as $user_data_object) {

              ?>

             <tr>

             <td><?php echo $user_data_object->username ;?></td>
             <td><?php echo $user_data_object->fullname ;?></td>
             <td><?php echo $user_data_object->email ;?></td>
             <td><?php echo $user_data_object->region ;?></td>
             <td><?php echo $user_data_object->plarea ;?></td>
             <td><?php echo $user_data_object->location ;?></td>
             <td><?php echo $user_data_object->state ;?></td>
             <td><?php echo $user_data_object->depatment ;?></td>
             <td><?php echo $user_data_object->designation ;?></td>
             <td><?php echo $user_data_object->guru_id ;?></td>
             <td><?php echo $user_data_object->guruname ;?></td>
             <td><?php echo $user_data_object->timecreated ;?></td>
             <td><?php echo $user_data_object->firstaccess ;?></td>
             <td><?php echo $user_data_object->lastaccess ;?></td>
             <td><?php echo $user_data_object->lastlogin ;?></td>
             
             
             </tr>
          <?php
               
          }

         ?>   
         </tbody>
</table>

	<?php

}

?>

