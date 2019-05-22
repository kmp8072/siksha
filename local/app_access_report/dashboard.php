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

include 'filters.php';
include 'constants.php';




$strheading = "App access report";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/app_access_report/dashboard.php'));
$PAGE->set_title($strheading);


?>
<html>
<head>

<link rel="stylesheet" href="style/style.css">
<!-- <script src="//maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script> -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />


</head>



<?php
require_login();
echo $OUTPUT->header();
global $USER, $DB, $CFG;


?>
<body>
  <h2>App access report</h2>



<div class="container">
	<form action="" method="POST">
	<div class="form-group row">
	    	<div class="col-md-2">
	        <label>Type</label>
	        <select class="form-control select2" name="Type">
	           <option value="">Select</option> 
	           <option value="<?php echo ALL;?>" selected='true'>All</option> 
	           <option value="<?php echo APP_ACCESSED;?>">App accessed</option> 
	           <option value="<?php echo APP_NOT_ACCESSED_YET;?>">App not accessed yet</option> 
	           <option value="<?php echo GURU_MAPPED;?>">Guru mapped</option> 
	           <option value="<?php echo GURU_NOT_MAPPED;?>">Guru not mapped</option>
	           <option value="<?php echo APP_ACCESSED_BUT_GURU_NOT_MAPPED;?>">App accessed but guru not mapped</option> 
	        </select>
	    </div>
	    <div class="col-md-2">
	        <label>Region</label>
	        <select class="form-control select2" name="region">
	           <option value="">Select</option> 
	           <?php 

	           // $distinct_regions_obj is in filters.php file

	           foreach ($distinct_regions_obj as $distinct_region_obj) {
	           	
	           	echo "<option value='$distinct_region_obj->data'".(($_POST['region']=="$distinct_region_obj->data")?'selected="selected"':"").">".$distinct_region_obj->data."</option> ";

	           }

	           ?>
	           
	           
	        </select>
	    </div>
	    <div class="col-md-2">
	        <label>P & L Area</label>
	        <select class="form-control select2" name="plarea">
	           <option value="">Select</option> 
	           <?php 

	           // $distinct_pl_obj is in filters.php file

	           foreach ($distinct_pl_obj as $distinct_pl_object) {
	           	
	           	echo "<option value='$distinct_pl_object->data'".(($_POST['plarea']=="$distinct_pl_object->data")?'selected="selected"':"").">".$distinct_pl_object->data."</option> ";

	           }

	           ?> 
	        </select>
	    </div>
	    <div class="col-md-2">
	        <label>Location</label>
	        <select class="form-control select2" name="location">
	           <option value="">Select</option> 
	           <?php 

	           // $distinct_location_obj is in filters.php file

	           foreach ($distinct_location_obj as $distinct_location_object) {
	           	
	           	echo "<option value='$distinct_location_object->data'".(($_POST['location']=="$distinct_location_object->data")?'selected="selected"':"").">".$distinct_location_object->data."</option> ";

	           }

	           ?> 
	        </select>
	    </div>
	    <div class="col-md-2">
	        <label>State</label>
	        <select class="form-control select2" name="state">
	           <option value="">Select</option> 
	           <?php 

	           // $distinct_state_obj is in filters.php file

	           foreach ($distinct_state_obj as $distinct_state_object) {
	           	
	           	echo "<option value='$distinct_state_object->data'".(($_POST['state']=="$distinct_state_object->data")?'selected="selected"':"").">".$distinct_state_object->data."</option> ";

	           }

	           ?>
	        </select>
	    </div>
	</div>
	<div class="form-group row">
	    <div class="col-md-2">
	        <label>Department</label>
	        <select class="form-control select2" name="department">
	           <option value="">Select</option> 
	           <?php 

	           // $distinct_regions_obj is in filters.php file

	           foreach ($distinct_department_obj as $distinct_department_object) {
	           	
	           	echo "<option value='$distinct_department_object->data'".(($_POST['department']=="$distinct_department_object->data")?'selected="selected"':"").">".$distinct_department_object->data."</option> ";

	           }

	           ?>
	        </select>
	    </div>
	    <div class="col-md-2">
	        <label>Designation</label>
	        <select class="form-control select2" name="designation">
	           <option value="">Select</option> 
	           <?php 

	           // $distinct_regions_obj is in filters.php file

	           foreach ($distinct_designation_obj as $distinct_designation_object) {
	           	
	           	echo "<option value='$distinct_designation_object->data'".(($_POST['designation']=="$distinct_designation_object->data")?'selected="selected"':"").">".$distinct_designation_object->data."</option> ";

	           }

	           ?> 
	        </select>
	    </div>
 	</div>
 	<div class="row">
 		<div class="col-md-8">
 			<input type="submit" name="submit" value="Submit">
 		</div>
 		
 	</div>
 	
 	</form>
</div>


<!--              ------------------->

<div class="container">
	<div class="row">

<!--========== Modal ==========-->
<article class="col-md-4 well">  
    <!-- Modal -->
    <div class="modal fade" id="warningModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog" style="width: 80%;">
            <div class="modal-content">
                <div class="modal-header modal-header-warning">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true" id="closewarningModal">×</button>
                    <h2><i class="glyphicon glyphicon-user"></i>Assign Guru</h2>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 radio-choices">
                        <div class="col-md-12 col-xs-12 radio-left" >
                            <div class="table-responsive overflow-height" id="gurus">
                            </div>
                        </div>
                    </div><!-- ends col-12 -->

                </div><div class="clearfix"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary pull-right" id="checksubmission" disabled="true">Submit</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</article>

	</div>
</div>


<!---         ---------------------->

<div id="divMessage" style="display: none;">

            Please wait ....

</div>

<div id="divwelcomeMessage" style="display: none;">

            Sending Message Please wait ....

</div>



<div id="wait" style="display:none;width:69px;height:89px;border:1px solid black;position:absolute;top:50%;left:50%;padding:2px;"><img src='../../theme/remui/pix/loading.gif' width="64" height="64" /><br>Loading..</div>

<div id="assignwait" style="display:none;width:69px;height:89px;border:1px solid black;position:absolute;top:50%;left:50%;padding:2px;"><img src='../../theme/remui/pix/loading.gif' width="64" height="64" /><br>Assigning..</div>

 <div class="col-md-3 custom-inline">
      <label>Show</label>
      <select name="no_of_entries" id="no_of_entries">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="100">100</option>
        <option value="999999999">All</option>
      </select>
      <label>Entries</label>
    </div>

<div id="ajax_data">
<?php 

if (isset($_POST['submit'])) {

	unset($_SESSION['type_']);
	unset($_SESSION['region_']);
	unset($_SESSION['plarea_']);
	unset($_SESSION['location_']);
	unset($_SESSION['state_']);
	unset($_SESSION['department_']);
	unset($_SESSION['designation_']);
	unset($_SESSION['total_row_count']);
	include 'submitteddata.php';
		

}

?>

</div>


<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/js/select2.min.js"></script>
<script src="../../theme/remui/javascript/excel-bootstrap-table-filter-bundle.js"></script>
<link rel="stylesheet" href="../../theme/remui/style/excel-bootstrap-table-filter-style.css" />




<script type="text/javascript" src="js/custom.js"></script>
<script>
    $('.select2').select2();
    $(function () {
      $('#usertable').excelTableFilter();           
    });



</script>


</body> 
<?php


echo $OUTPUT->footer();



?>

