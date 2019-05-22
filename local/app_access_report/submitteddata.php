<?php  

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

global $USER, $DB, $CFG;

//require_login();

 if(isset($_POST['page'])){

	$page = $_POST['page'];

	}else {

	$page=1;

	}

  if(isset($_POST['perpage'])){

  $per_page = $_POST['perpage'];

  }else {

  $per_page=10;

  }

  //$per_page=10;

	// print_object($_POST);
	// die();

     $type=$_POST['Type'];

   if ($_POST['Type']!='') {
   	$_SESSION['type_']=$_POST['Type'];
   }
   
   $region=$_POST['region'];

   if ($_POST['region']!='') {
   	$_SESSION['region_']=$_POST['region'];
   }
  
   $plarea=$_POST['plarea'];

   if ($_POST['plarea']!='') {
   	$_SESSION['plarea_']=$_POST['plarea'];
   }
  
   $location=$_POST['location'];

   if ($_POST['location']!='') {
   	$_SESSION['location_']=$_POST['location'];
   }
  
   $state=$_POST['state'];

   if ($_POST['state']!='') {
   	$_SESSION['state_']=$_POST['state'];
   }
  
   $department=$_POST['department'];

   if ($_POST['department']!='') {
   	$_SESSION['department_']=$_POST['department'];
   }
  
   $designation=$_POST['designation'];

   if ($_POST['designation']!='') {
   	$_SESSION['designation_']=$_POST['designation'];
   }

   


 //  $base_sql="SELECT distinct(u.id),u.email,u.username,CONCAT(u.firstname,' ',u.lastname) AS fullname,u.firstaccess,u.lastaccess,u.lastlogin,u.timecreated,gnm.guru_id,gnm.nj_id,CONCAT(g.firstname,' ',g.lastname) AS guruname ,gnm.status,uidunit.data AS unit,uid.data AS region,uidpl.data AS plarea,uids.data AS state,uidl.data AS location,uiddept.data AS depatment,uiddes.data AS designation FROM mdl_user u 
	// JOIN mdl_role_assignments ra ON ra.userid=u.id 
	// LEFT JOIN mdl_guru_nj_mapping gnm ON gnm.nj_id=u.id
	// JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3
	// JOIN mdl_user_info_data uidunit ON uidunit.userid=u.id AND uidunit.fieldid=2 
	// JOIN mdl_user_info_data uidpl ON uidpl.userid=u.id AND uidpl.fieldid=4 
	// JOIN mdl_user_info_data uidl ON uidl.userid=u.id AND uidl.fieldid=5 
	// JOIN mdl_user_info_data uids ON uids.userid=u.id AND uids.fieldid=6
	// JOIN mdl_user_info_data uiddept ON uiddept.userid=u.id AND uiddept.fieldid=7  
	// JOIN mdl_user_info_data uiddes ON uiddes.userid=u.id AND uiddes.fieldid=8
	// LEFT JOIN mdl_user g ON g.id=gnm.guru_id 
	// WHERE ra.roleid=4 OR ra.roleid=5 ";

 //   $base_sql_for_page_count="SELECT count(distinct(u.id)) AS row_count FROM mdl_user u 
	// JOIN mdl_role_assignments ra ON ra.userid=u.id 
	// JOIN mdl_user_info_data uidunit ON uidunit.userid=u.id AND uidunit.fieldid=2 
	// LEFT JOIN mdl_guru_nj_mapping gnm ON gnm.nj_id=u.id ";

	// $wherecondition_for_count="  WHERE ra.roleid=4 OR ra.roleid=5 ";



  $base_sql_conditioned=" SELECT distinct(u.id),u.email,u.username,u.timecreated,CONCAT(u.firstname,' ',u.lastname) AS fullname,u.firstaccess,u.lastaccess,u.lastlogin,gnm.guru_id,gnm.nj_id,CONCAT(g.firstname,' ',g.lastname) AS guruname ,gnm.status,gnm.isactive,uidunit.data AS unit,uid.data AS region,uidpl.data AS plarea,uids.data AS state,uidl.data AS location,uiddept.data AS depatment,uiddes.data AS designation FROM mdl_user u 
	JOIN mdl_role_assignments ra ON ra.userid=u.id 
	JOIN mdl_user_info_data uidunit ON uidunit.userid=u.id AND uidunit.fieldid=2 
	LEFT JOIN mdl_guru_nj_mapping gnm ON gnm.nj_id=u.id ";






  $condition1=" JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 ";
  $condition2=" JOIN mdl_user_info_data uidpl ON uidpl.userid=u.id AND uidpl.fieldid=4 ";
  $condition3=" JOIN mdl_user_info_data uidl ON uidl.userid=u.id AND uidl.fieldid=5 ";
  $condition4=" JOIN mdl_user_info_data uids ON uids.userid=u.id AND uids.fieldid=6 ";
  $condition5=" JOIN mdl_user_info_data uiddept ON uiddept.userid=u.id AND uiddept.fieldid=7 ";
  $condition6=" JOIN mdl_user_info_data uiddes ON uiddes.userid=u.id AND uiddes.fieldid=8 ";
  $wherecondition=" LEFT JOIN mdl_user g ON g.id=gnm.guru_id WHERE ra.roleid=5 ";


  if ($region!='' || $_SESSION['region_']) {

  	$condition1=" JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 AND uid.data='".$_SESSION['region_']."' ";
  	
  }

  if ($plarea!='' || $_SESSION['plarea_']!='') {

  	$condition2=" JOIN mdl_user_info_data uidpl ON uidpl.userid=u.id AND uidpl.fieldid=4 AND uidpl.data='".$_SESSION['plarea_']."' ";
  	
  }

  if ($location!='' || $_SESSION['location_']!='') {

  	$condition3=" JOIN mdl_user_info_data uidl ON uidl.userid=u.id AND uidl.fieldid=5 AND uidl.data='".$_SESSION['location_']."' ";
  	
  }

  if ($state!='' || $_SESSION['state_']!='') {

  	$condition4=" JOIN mdl_user_info_data uids ON uids.userid=u.id AND uids.fieldid=6 AND uids.data='".$_SESSION['state_']."' ";
  	
  }


  if ($department!='' || $_SESSION['department_']!='') {

  	$condition5=" JOIN mdl_user_info_data uiddept ON uiddept.userid=u.id AND uiddept.fieldid=7 AND uiddept.data='".$_SESSION['department_']."' ";
  	
  }


  if ($designation!='' || $_SESSION['designation_']!='') {

  	$condition6=" JOIN mdl_user_info_data uiddes ON uiddes.userid=u.id AND uiddes.fieldid=8 AND uiddes.data='".$_SESSION['designation_']."' ";

  }


  // conditions based on type selected

  // for all there is no condition

  // for app accessed

   if ($type==2 || $_SESSION['type_']==2) {
     $type_where_condition=" AND u.firstaccess!=0 ";
   }

   if ($type==3 || $_SESSION['type_']==3) {
     $type_where_condition=" AND u.firstaccess=0 ";
   }

   if ($type==4 || $_SESSION['type_']==4) {
     $wherecondition=" LEFT JOIN mdl_user g ON g.id=gnm.guru_id WHERE ra.roleid=5 AND (gnm.status=1 OR gnm.status=0) ";
   }

   if ($type==5 || $_SESSION['type_']==5) {
     $wherecondition=" LEFT JOIN mdl_user g ON g.id=gnm.guru_id WHERE ra.roleid=5 AND (gnm.status!=1 OR gnm.status!=0 OR gnm.status IS NULL) ";
   }

   if ($type==6 || $_SESSION['type_']==6) {
     $wherecondition=" LEFT JOIN mdl_user g ON g.id=gnm.guru_id WHERE ra.roleid=5 AND (gnm.status=1 OR gnm.status=0 OR gnm.status IS NULL) AND u.firstaccess!=0 ";
   }

   $wherecondition.=$type_where_condition;




      if (!isset($_SESSION['total_row_count'])) {

  	   $final_base_sql_for_page_count="SELECT COUNT(id) AS total_rows FROM (".$base_sql_conditioned.$condition1.$condition2.$condition3.$condition4.$condition5.$condition6.$wherecondition. ") d1";
  		
  	  $total_row_count_obj=$DB->get_record_sql($final_base_sql_for_page_count);

  	  //$total_row_count = $total_row_count_obj->total_rows;

      $_SESSION['total_row_count']=$total_row_count_obj->total_rows;

        }

      $total_row_count=$_SESSION['total_row_count'];

   
     $total_pages = ceil($total_row_count / $per_page);

	   $start_from = ($page-1) * $per_page ;

     $sLimit = " order by 1 DESC LIMIT $start_from,$per_page ";

     $wherecondition.=$sLimit;

       $base_sql_final=$base_sql_conditioned.$condition1.$condition2.$condition3.$condition4.$condition5.$condition6.$wherecondition;
       

     $user_data_objects=$DB->get_records_sql($base_sql_final);

?>

<div class="table-resposnsive" id="user_data">

<table class="table" id="usertable">
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
             <td><?php echo date('d-m-Y',$user_data_object->timecreated) ;?></td>
             <td><?php echo date('d-m-Y',$user_data_object->firstaccess) ;?></td>
             <td><?php echo date('d-m-Y',$user_data_object->lastaccess) ;?></td>
             <td>
               
              <?php  

              // check if user has not accessed the app  

              if ($user_data_object->firstaccess==0) {
                  
        // now check if a mail has been sent within 24 hours

        $check_mail_sent_query="SELECT UNIX_TIMESTAMP(updatedate) AS updatedate FROM mdl_welcome_msg_time WHERE userid=$user_data_object->id";

        $check_mail_sent_obj=$DB->get_record_sql($check_mail_sent_query);

        $timesent=$check_mail_sent_obj->updatedate;

        $current_time=time();

        if ($current_time-$timesent>=86400) {
          ?>
          <button type="button" class="btn btn-primary" onclick="sendwelcomemessage('<?php echo $user_data_object->id ;?>',this)"><i class="glyphicon glyphicon-envelope"></i> Send Welcome Message</button>
          <?php
        }

              }

              // now check if isactive and guru is mapped or not if not map manually 

              // if(!$user_data_object->guru_id && $user_data_object->isactive==4) {
                if(!$user_data_object->guru_id) {
                ?>

                <!-- <button type="button" class="btn btn-primary" onclick="mapaguru('<?php echo $user_data_object->id ;?>')">Assign Guru</button> -->

              <a class="btn btn-warning open-AssignGuru" href="#warningModal" data-toggle="modal" data-id="<?php echo $user_data_object->id;?>" id="<?php echo $user_data_object->id;?>"><i class="glyphicon glyphicon-user"></i> Assign Guru</a>


                <?php
              }


              ?>

             </td>
             
             
             </tr>
          <?php
               
          }

         ?>   
         </tbody>
</table>


              
<div style="display: inline-flex;">
  <label>Go to Page</label>
              <select onChange="paginate(this)" id="pageno">
             
              <?php 

              for ($i=1; $i<=$total_pages; $i++){

           ?>
                
             <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
      
           <?php

             //echo "<input type='button' value='$i' onclick='paginate(this)' />";

               }

              ?>
              
              </select>   
   
</div>
</div>


