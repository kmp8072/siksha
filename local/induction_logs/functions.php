<?php

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

global $USER, $DB, $CFG;

require_login();

 $induction_id=$_POST['induction_id'];
 $key=$_POST['key'];


 switch ($key) {
 	case 'findlogs':
 		$result=findlogs($induction_id);
 		echo $result;
 		break;
 	
 	default:
 		
 		break;
 }

 




function findlogs($induction_id)
 {
 	global $USER, $DB, $CFG;

     $induction_logs_query="SELECT il.id,il.induction_id,il.guru_id,il.nj_id,il.status,il.createddate,il.upadatedate,il.induction_start_date,il.induction_status,il.induction_status_updatedby,il.successchamp_id,il.rejectionreason,CONCAT(u.firstname,' ',u.lastname) AS gurufullname,CONCAT(u1.firstname,' ',u1.lastname) AS njfullnmae,CONCAT(u2.firstname,' ',u2.lastname) AS succeschampname,CONCAT(u3.firstname,' ',u3.lastname) AS updatername,statusname FROM mdl_induction_logs il
LEFT JOIN mdl_user u ON u.id=il.guru_id
LEFT JOIN mdl_user u1 ON u1.id=il.nj_id
LEFT JOIN mdl_user u2 ON u2.id=il.successchamp_id
LEFT JOIN mdl_user u3 ON u3.id=il.induction_status_updatedby
JOIN mdl_nj_guru_mapping_status njms ON njms.status=il.status
WHERE il.induction_id=$induction_id
ORDER BY il.upadatedate DESC";


    
$induction_logs_objs=$DB->get_records_sql($induction_logs_query);
   
?>
	
 	<table id="logstable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Induction ID</th>
                <th>New Joinee Name</th>
                <th>Guru's Name</th>
                <th>Success Champ Name</th>
                <th>Created Date</th>
                <th>Updated Date</th>
                <th>Induction Start Date</th>
                <th>Induction Status</th>
                <th>Induction status updated By</th>
                <th>Induction status by Guru</th>
                <th>Reason of Rejection</th>
            </tr>
        </thead>
        <tbody>

          <?php  
          //print_object($induction_logs_objs);
          foreach ($induction_logs_objs as $induction_logs_obj) {
           ?>

            <tr>
                <td><?php echo $induction_logs_obj->induction_id;?></td>
                <td><?php echo $induction_logs_obj->njfullnmae;?></td>
                <td><?php echo $induction_logs_obj->gurufullname;?></td>
                <td><?php echo $induction_logs_obj->succeschampname;?></td>
                <td><?php echo $induction_logs_obj->createddate;?></td>
                <td><?php echo $induction_logs_obj->upadatedate;?></td>
                <td><?php echo $induction_logs_obj->induction_start_date;?></td>
                <td><?php echo $induction_logs_obj->induction_status;?></td>
                <td><?php echo $induction_logs_obj->induction_status_updatedby;?></td>
                <td><?php echo $induction_logs_obj->statusname;?></td>
                <td><?php echo $induction_logs_obj->rejectionreason;?></td> 
            </tr>


           <?php
          }
          ?>

            
        </tbody>
        
    </table>

  <?php       

 }


?>