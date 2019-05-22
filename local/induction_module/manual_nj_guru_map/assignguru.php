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



require_once ('../../../config.php');

global $USER,$DB,$CFG;


$strheading = "Map NJ to GURU";

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/induction_module/manual_nj_guru_map/dashboard.php'));

$PAGE->set_title($strheading);

//$PAGE->navbar->add($strheading);

require_login();


echo $OUTPUT->header();

$njid=$_GET['njid'];
$nj_name=$_GET['nj_name'];

echo "<h4>Assign a guru to ".$nj_name."</h4>";


    if (isset($_POST['submit'])) {
      
      $guruid=$_POST['optradio'];

      if ($guruid!='') {

        //check if entry for nj exists
        
        $map_guru_to_nj_query="UPDATE {guru_nj_mapping} SET guru_id=$guruid,status=0 WHERE nj_id=$njid";

        if($DB->execute($map_guru_to_nj_query)){

          echo "<div class='alert alert-success'>
          <strong>Success!</strong> Guru assigned successfully.
          </div>";

          $urltogo = $CFG->wwwroot.'/local/induction_module/manual_nj_guru_map/dashboard.php';

          sleep(3);

          redirect($urltogo);

          //header("Location: dashboard.php");

        }

      }

    }



     $admins= get_admins();
     $adminkeys=array_keys($admins);
     $adminkeys=implode(',', $adminkeys);


     $find_gurus_query="SELECT u.id FROM mdl_user u 
     JOIN mdl_role_assignments ra ON ra.userid=u.id
     WHERE ra.roleid=4 AND u.id NOT IN($adminkeys)";   
     $getrecords=$DB->get_records_sql($find_gurus_query);

     $guru_data_objs=array();

     foreach ($getrecords as $getrecord) {

     $user_id=$getrecord->id;

     $no_of_nj_mapped_query="SELECT count(id) as nj_mapped_count FROM mdl_guru_nj_mapping WHERE status=1 AND guru_id=$user_id";

     $no_of_nj_mapped_obj=$DB->get_record_sql($no_of_nj_mapped_query);

     $no_of_nj_mapped=$no_of_nj_mapped_obj->nj_mapped_count;

     $guru_data_query="SELECT u.id,u.username,CONCAT(u.firstname,' ',u.lastname) as userfullname, u.address FROM {user} u WHERE u.id=$user_id";

     $guru_data_obj=$DB->get_record_sql($guru_data_query);

     $guru_data_obj->mapped_nj=$no_of_nj_mapped;

     //find extra info about guru

     $guru_extra_info_query="SELECT uif.id,uif.name,uid.data FROM {user} u 
     JOIN {user_info_data} uid ON uid.userid=u.id
     JOIN {user_info_field} uif ON uif.id=uid.fieldid
     WHERE u.id=$user_id";

     $guru_extra_info_obj=$DB->get_records_sql($guru_extra_info_query);

     $guru_data_obj->extra_info=$guru_extra_info_obj;

     $guru_data_objs[]=$guru_data_obj;

     //print_object($guru_data_obj);
       
     }

     
    // print_object($guru_data_objs);
?>

     
     <form method="POST" action="">

      <table class="table table-responsive" id="gurustable">
     <thead>
         <tr>
             <th></th>
             <th>Guru Name</th>
             <th>Address</th>
             <th>No of Students</th>
             <th>Unit</th>
             <th>Region</th>
             <th>Location</th>
             <th>State</th>
             <th>Department</th>
             <th>Designation</th>

             

         </tr>
     </thead>
     <tbody>
      <?php
             foreach ($guru_data_objs as $guru_data_ob) {

              ?>

               <tr>

             <td>
                 <div class="radio">
                     <label><input type="radio" id='<?php echo $guru_data_ob->id;?>' name="optradio" value='<?php echo $guru_data_ob->id;?>'></label>
                 </div>
             </td>
             <td><?php echo $guru_data_ob->userfullname ;?></td>
             <td><?php echo $guru_data_ob->address ;?></td>
             <td><?php echo $guru_data_ob->mapped_nj ;?></td>
             <td><?php echo $guru_data_ob->extra_info[2]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[3]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[5]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[6]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[7]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[8]->data ;?></td>
             
         </tr>
          <?php
               
          }

         ?>   
         </tbody>
</table>
<input type="submit" name="submit" class="btn btn-default">
 </form>
     
  <!-- Load the plugin bundle. -->
  <script src="../../../theme/remui/javascript/excel-bootstrap-table-filter-bundle.js"></script>
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" /> -->
  <link rel="stylesheet" href="../../../theme/remui/style/excel-bootstrap-table-filter-style.css" />





<?php

   echo $OUTPUT->footer();




?>

<style type="text/css">
  
  tfoot input {
        width: 100%;
        padding: 3px;
        box-sizing: border-box;
    }
.table-responsive {
    display: block;
    width: 100%;
    overflow-x: auto;
  }

</style>


<script type="text/javascript">

  $(function () {
      // Apply the plugin example13
      $('#gurustable').excelTableFilter();
      //$('#unassigned').excelTableFilter();
      //$('#example13').excelTableFilter();
      
    });

</script>


<script type="text/javascript">

function assignguru(njid){

var njid=njid;

alert(njid);

//$('#myModalHorizontal').show();
 

}

</script>


<style type="text/css">

.table-responsive {
    display: block;
    width: 90%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.dropdown-filter-content{
  right: 0;
  z-index: 999 !important;
}

/* Styles for the drop-down. Feel free to change the styles to suit your website. :-) */

.glyphicon-arrow-down:before{
  display: none;
}
.arrow-down{
  margin-bottom: 5px;
}
.dropdown-filter-icon{
  border: 1px solid #afafaf !important;
  border-radius: 2px;
}
th {
    text-align: center;
    border: 1px solid #d2d2d2 !important;
    background: #e3e3e3;
}
td{
  text-align: center;
  border: 1px solid #d2d2d2 !important;
}

a.active {
    border: 1px solid #9a9a9a;
    padding-top: 5px;
    padding-right: 5px;
    padding-left: 5px;
    /* line-height: 11px; */
    border-bottom: none;
    border-radius: 2px;
    font-size: 15px !important;
    font-weight: 500 !important;
}
a#nav-assigned-tab,
a#nav-unassigned-tab {
    font-size: 15px !important;
    font-weight: 500 !important;
}

table {
    width: 50%;
    margin-left: auto;
    margin-right: auto;
    max-width: 70%;
    margin-bottom: 20px;
}

.modal-body .form-horizontal .col-sm-2,
.modal-body .form-horizontal .col-sm-10 {
    width: 100%
}

.modal-body .form-horizontal .control-label {
    text-align: left;
}
.modal-body .form-horizontal .col-sm-offset-2 {
    margin-left: 15px;
}

</style>


