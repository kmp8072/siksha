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

// require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');

// require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');

$strheading = "Induction Logs";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/induction_logs/dashboard.php'));
$PAGE->set_title($strheading);
//$PAGE->navbar->add($strheading);
//$user = $USER->id;
?>
<html>
<head>


<!-- <script src="http://code.jquery.com/jquery-2.2.4.min.js"></script> -->

<link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">

</head>

<?php
require_login();
echo $OUTPUT->header();
global $_SESSION;
global $USER, $DB;

// find the records from induction_logs table

$induction_logs_query="SELECT il.id,il.induction_id,il.guru_id,il.nj_id,il.status,il.createddate,il.upadatedate,il.induction_start_date,il.induction_status,il.induction_status_updatedby,il.successchamp_id,il.rejectionreason,CONCAT(u.firstname,' ',u.lastname) AS gurufullname,CONCAT(u1.firstname,' ',u1.lastname) AS njfullnmae,CONCAT(u2.firstname,' ',u2.lastname) AS succeschampname,CONCAT(u3.firstname,' ',u3.lastname) AS updatername,statusname FROM mdl_induction_logs il
LEFT JOIN mdl_user u ON u.id=il.guru_id
LEFT JOIN mdl_user u1 ON u1.id=il.nj_id
LEFT JOIN mdl_user u2 ON u2.id=il.successchamp_id
LEFT JOIN mdl_user u3 ON u3.id=il.induction_status_updatedby
JOIN mdl_nj_guru_mapping_status njms ON njms.status=il.status
ORDER BY il.induction_id ASC,il.upadatedate DESC";

$induction_logs_objs=$DB->get_records_sql($induction_logs_query);

// find welcome message logs from mdl_welcome_msg_logs table

$welmsg_logs_query="SELECT wml.id,wml.wecome_msd_id,wml.no_of_times,wml.createddate,wml.updatedate,wml.senderid,wml.userid,CONCAT(u.firstname,' ',u.lastname) AS sendername,CONCAT(u1.firstname,' ',u1.lastname) AS userfullname FROM mdl_welcome_msg_logs wml
LEFT JOIN mdl_user u ON u.id=wml.senderid
LEFT JOIN mdl_user u1 ON u1.id=wml.userid
ORDER BY wml.wecome_msd_id ASC,wml.updatedate DESC";

$welmsg_logs_objs=$DB->get_records_sql($welmsg_logs_query);


?>

<!----                  ------------------->


<script type="text/javascript" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/vfs_fonts.js"></script>



<!-----------             ----------------->




<body>
  <h2>Induction Logs</h2>

  <table id="example" class="display" style="width:100%">
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


     <h2>Welcome Msg Logs</h2>

  <table id="example1" class="display" style="width:100%">
        <thead>
            <tr>
                <th>New Joinee Name</th>
                <th>Sender Name</th>
                <th>Sent Time</th>
                <th>No of Times Sent</th>
            </tr>
        </thead>
        <tbody>

          <?php  

          //print_object($welmsg_logs_objs);

          foreach ($welmsg_logs_objs as $welmsg_logs_obj) {
           ?>

            <tr>
                <td><?php echo $welmsg_logs_obj->userfullname;?></td>
                <td><?php echo $welmsg_logs_obj->sendername;?></td>
                <td><?php echo $welmsg_logs_obj->updatedate;?></td>
                <td><?php echo $welmsg_logs_obj->no_of_times;?></td>
            </tr>


           <?php
          }

          ?>

            
        </tbody>
        
    </table>



</body> 
<?php

echo $OUTPUT->footer();
?>


<script type="text/javascript">
  
$(document).ready(function() {
    // Setup - add a text input to each footer cell
    $('#example thead tr').clone(true).appendTo( '#example thead' );
    $('#example thead tr:eq(1) th').each( function (i) {
        var title = $(this).text();
        $(this).html( '<input type="text" placeholder="Search '+title+'" />' );
 
        $( 'input', this ).on( 'keyup change', function () {
            if ( table.column(i).search() !== this.value ) {
                table
                    .column(i)
                    .search( this.value )
                    .draw();
            }
        } );
    } );
 
    // var table = $('#example').DataTable( {
    //     orderCellsTop: true,
    //     fixedHeader: true
    // } );


     var table = $('#example').DataTable( {

   
      dom: 'lBfrtip',
        buttons: [
             
             {
                 extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Export To Excel',
              filename: 'User_Post_Comments_Report'
            }

        ],
    } );


     // Setup - add a text input to each footer cell
    $('#example1 thead tr').clone(true).appendTo( '#example1 thead' );
    $('#example1 thead tr:eq(1) th').each( function (i) {
        var title = $(this).text();
        $(this).html( '<input type="text" placeholder="Search '+title+'" />' );
 
        $( 'input', this ).on( 'keyup change', function () {
            if ( table.column(i).search() !== this.value ) {
                table
                    .column(i)
                    .search( this.value )
                    .draw();
            }
        } );
    } );
 
    // var table = $('#example').DataTable( {
    //     orderCellsTop: true,
    //     fixedHeader: true
    // } );


     var table = $('#example1').DataTable( {

   
      dom: 'lBfrtip',
        buttons: [
             
             {
                 extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Export To Excel',
              filename: 'User_Post_Comments_Report'
            }

        ],
    } );



    

} );

</script>

<style type="text/css">
 thead input {
        width: 100%;
    }
    button.dt-button.buttons-excel.buttons-html5 {
    margin: 21px;
}
</style>