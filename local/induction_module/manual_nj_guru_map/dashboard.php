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

//find out details of nj_guru mapping table of assigned nj

$assigned_records_query="SELECT * FROM {guru_nj_mapping} WHERE status=1";

$assigned_records=$DB->get_records_sql($assigned_records_query);

//find details of unassigned nj_guru from mapping table

$unassigned_records_query="SELECT gnm.* , gnms.statusname FROM {guru_nj_mapping} gnm
    join {nj_guru_mapping_status} gnms ON gnm.status=gnms.status
    WHERE gnm.status=2 OR gnm.status=3";

$unassigned_records=$DB->get_records_sql($unassigned_records_query);

?>

<h4>NJ Guru Mapping</h4>

<script src="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>

<!--  first make two tabs and keep unassigned tab with active class ----------------->

<section id="tabs" class="project-tab">
            <div class="container">
                <div class="row">
                    <div class="col-md-12">
                        <nav>
                            <div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">
                                <a class="nav-item nav-link active show" id="nav-assigned-tab" data-toggle="tab" href="#nav-assigned" role="tab" aria-controls="nav-assigned" aria-selected="true">Assigned</a>
                                <a class="nav-item nav-link" id="nav-unassigned-tab" data-toggle="tab" href="#nav-unassigned" role="tab" aria-controls="nav-unassigned" aria-selected="false">Unassigned</a>
                            </div>
                        </nav>
                        <div class="tab-content" id="nav-tabContent">
                            <div class="tab-pane fade active" id="nav-assigned" role="tabpanel" aria-labelledby="nav-assigned-tab">
                              <div class="table-responsive">
                                <table class="table" cellspacing="0" id="assigned">
                                    <thead>
                                      <tr>
                                            <th colspan="2">User Details</th>
                                            <th colspan="3">Guru Details</th>
                                            
                                        </tr>
                                        <tr>
                                            <th>User FullName</th>
                                            <th>Created Date</th>
                                            
                                            <th>Guru FullName</th>
                                            <th>Guru's Action</th>
                                            <th>Guru's Action Date</th>
                                        </tr>

                                    </thead>
                                    <tbody>


                    <?php 

                    //find users details and guru details

                    foreach ($assigned_records as $assigned_record) {
                      
                      $nj_id=$assigned_record->nj_id;
                      $guru_id=$assigned_record->guru_id;

                      //find nj details

    $nj_details_query="SELECT CONCAT(u.firstname,' ',u.lastname) AS userfullname FROM {user} u WHERE id=$nj_id";

    $nj_details=$DB->get_record_sql($nj_details_query);

    $guru_details_query="SELECT CONCAT(u.firstname,' ',u.lastname) AS userfullname FROM {user} u WHERE id=$guru_id";

    $guru_details=$DB->get_record_sql($guru_details_query);

    echo "<tr>
          <td>".$nj_details->userfullname."</td>
          <td>".$assigned_record->createddate."</td>
          <td>".$guru_details->userfullname."</td>
          <td>".'Accepted'."</td>
          <td>".$assigned_record->upadatedate."</td>
          </tr>";

                    }

                    ?>

                                    </tbody>
                                </table>
                              </div>
                            </div>
                            <div class="tab-pane fade" id="nav-unassigned" role="tabpanel" aria-labelledby="nav-unassigned-tab">
                              <div class="table-responsive">

                                <table class="table" cellspacing="0" id="unassigned">
                                    <thead>
                                        <tr>
                                            <th colspan="2">User Details</th>
                                            <th colspan="3">Guru Details</th>
                                            
                                        </tr>
                                        <tr>
                                            <th>User FullName</th>
                                            <th>Created Date</th>
                                            
                                            <th>Guru FullName</th>
                                            <th>Guru's Action</th>
                                            <th>Guru's Action Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                         <?php 

                    //find users details and guru details

                    foreach ($unassigned_records as $unassigned_record) {
                      
                      $nj_id=$unassigned_record->nj_id;
                      $guru_id=$unassigned_record->guru_id;

                      //find nj details

    $nj_details_query="SELECT CONCAT(u.firstname,' ',u.lastname) AS userfullname FROM {user} u WHERE id=$nj_id";

    $nj_details=$DB->get_record_sql($nj_details_query);

    $guru_details_query="SELECT CONCAT(u.firstname,' ',u.lastname) AS userfullname FROM {user} u WHERE id=$guru_id";

    $guru_details=$DB->get_record_sql($guru_details_query);

    echo "<tr>
          <td>".$nj_details->userfullname."</td>
          <td>".$assigned_record->createddate."</td>
          <td>".$guru_details->userfullname."</td>
          <td>".$unassigned_record->statusname."</td>";

          ?>
          <td>

          <?php 
          if ($unassigned_record->statusname!='Pending') {
            echo $unassigned_record->upadatedate;
          }  
          ?>
            
          </td>
          <td><a href="assignguru.php?njid=<?php echo $nj_id;?> & nj_name=<?php echo $nj_details->userfullname;?>" role="button" class="btn btn-primary" style="color:#fff !important;">Assign Guru</a></td>
          </tr>;

          <?php

                    }
                    ?>
                                    </tbody>
                                </table>
                              </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>


  <!-- Load the plugin bundle. -->
  <script src="../../../theme/remui/javascript/excel-bootstrap-table-filter-bundle.js"></script>
  <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.min.css" /> -->
  <link rel="stylesheet" href="../../../theme/remui/style/excel-bootstrap-table-filter-style.css" />





<?php

   echo $OUTPUT->footer();




?>




<script type="text/javascript">

  $(function () {
      // Apply the plugin example13
      $('#assigned').excelTableFilter();
      $('#unassigned').excelTableFilter();
      //$('#example13').excelTableFilter();
      
    });

</script>

<style type="text/css">

  div#nav-tab {
    display: inline-flex;
}


 tfoot input {
        width: 100%;
        padding: 3px;
        box-sizing: border-box;
    }


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


.project-tab {
    padding: 10% 0;
    margin-top: -8%;
}
.project-tab #tabs{
    background: #007b5e;
    color: #eee;
}
.project-tab #tabs h6.section-title{
    color: #eee;
}
.project-tab #tabs .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
    color: #0062cc;
    background-color: transparent;
    border-color: transparent transparent #f3f3f3;
    border-bottom: 3px solid !important;
    font-size: 16px;
    font-weight: bold;
}
.project-tab .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: .25rem;
    border-top-right-radius: .25rem;
    color: #0062cc;
    font-size: 16px;
    font-weight: 600;
    padding: 10px;
}
.project-tab .nav-link:hover {
    border: none;
}
.project-tab thead{
    background: #f3f3f3;
    color: #333;
}
.project-tab a{
    text-decoration: none;
    color: #333;
    font-weight: 600;
}

</style>


