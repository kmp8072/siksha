<?php


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

// find different inductions from induction logs table

$induction_query="SELECT DISTINCT(induction_id),guru_id,nj_id FROM {induction_logs}";

$induction_objs=$DB->get_records_sql($induction_query);

$induction_logs_objs=array();

//find details of new joinee and guru

foreach ($induction_objs as $induction_obj) {
  $induction_id=$induction_obj->induction_id;
  $nj_id=$induction_obj->nj_id;
  $guru_id=$induction_obj->guru_id;

  // new joinee details

  $nj_details_query="SELECT u.id,CONCAT(u.firstname,' ',u.lastname) AS userfullname,uid.data AS unit, uid1.data AS region, uid2.data AS department, uid3.data AS designation FROM mdl_user u 
    LEFT JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=2
    LEFT JOIN mdl_user_info_data uid1 ON uid1.userid=u.id AND uid1.fieldid=3
    LEFT JOIN mdl_user_info_data uid2 ON uid2.userid=u.id AND uid2.fieldid=7
    LEFT JOIN mdl_user_info_data uid3 ON uid3.userid=u.id AND uid3.fieldid=8
    WHERE u.id=$nj_id";

    $nj_details_obj=$DB->get_record_sql($nj_details_query);

    //guru fullname

  $guru_details_query="SELECT u.id,CONCAT(u.firstname,' ',u.lastname) AS gurufullname FROM mdl_user u WHERE u.id=$guru_id";

  $guru_details_obj=$DB->get_record_sql($guru_details_query);

  $nj_details_obj->gurufullname=$guru_details_obj->gurufullname;

  $nj_details_obj->guru_id=$guru_id;

  $nj_details_obj->induction_id=$induction_id;

  $induction_logs_objs[]=$nj_details_obj;


}

$welmsg_logs_query="SELECT wml.id,wml.wecome_msd_id,wml.no_of_times,wml.createddate,wml.updatedate,wml.senderid,wml.userid,CONCAT(u.firstname,' ',u.lastname) AS sendername,CONCAT(u1.firstname,' ',u1.lastname) AS userfullname FROM mdl_welcome_msg_logs wml
LEFT JOIN mdl_user u ON u.id=wml.senderid
LEFT JOIN mdl_user u1 ON u1.id=wml.userid
ORDER BY wml.wecome_msd_id ASC,wml.updatedate DESC";

$welmsg_logs_objs=$DB->get_records_sql($welmsg_logs_query);


//  print_object($induction_logs_objs);

// die();
?>

<!----                  ------------------->


<script type="text/javascript" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.5.6/js/buttons.flash.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.32/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.blockUI/2.70/jquery.blockUI.min.js"></script>
<script type="text/javascript">
  $.blockUI({ message: $('#divMessage') });
</script>



<!-----------             ----------------->




<body>
  <div id="divMessage" style="display: none;">

            Please wait ....

        </div>

  

  <h2>Induction Logs</h2>

  <table id="example" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Induction ID</th>
                <th>New Joinee Name</th>
                <th>Unit</th>
                <th>Region</th>
                <th>Department</th>
                <th>Designation</th>
                <th>Guru's Name</th>
                <th>Detailed Logs</th>
                
            </tr>
        </thead>
        <tbody>

          <?php  

          //print_object($induction_logs_objs);

          foreach ($induction_logs_objs as $induction_logs_obj) {
           ?>

            <tr>
                <td><?php echo $induction_logs_obj->induction_id;?></td>
                <td><?php echo $induction_logs_obj->userfullname;?></td>
                <td><?php echo $induction_logs_obj->unit;?></td>
                <td><?php echo $induction_logs_obj->region;?></td>
                <td><?php echo $induction_logs_obj->department;?></td>
                <td><?php echo $induction_logs_obj->designation;?></td>
                <td><?php echo $induction_logs_obj->gurufullname;?></td>
                <td><a class="btn btn-warning open-logs" href="#warningModal" data-toggle="modal" data-id="<?php echo $induction_logs_obj->induction_id;?>" id="<?php echo $induction_logs_obj->induction_id;?>"><i class="glyphicon glyphicon-list-alt"></i> View Logs</a></td> 
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
                    <h2><i class="glyphicon glyphicon-list-alt"></i>Induction Logs</h2>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 radio-choices">
                        <div class="col-md-12 col-xs-12 radio-left" >
                            <div class="table-responsive overflow-height" id="logs">
                            </div>
                        </div>
                    </div><!-- ends col-12 -->

                </div><div class="clearfix"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger pull-left" data-dismiss="modal">Close</button>
                    <!-- <button type="submit" class="btn btn-primary pull-right" id="checksubmission" disabled="true">Submit</button> -->
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
</article>

  </div>
</div>


<!---         ---------------------->

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
            if ( table2.column(i).search() !== this.value ) {
                table2
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


     var table2 = $('#example1').DataTable( {

   
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

<script type="text/javascript" src="js/custom.js"></script>

<script src="../../theme/remui/javascript/excel-bootstrap-table-filter-bundle.js"></script>
<link rel="stylesheet" href="../../theme/remui/style/excel-bootstrap-table-filter-style.css" />

<style type="text/css">
 thead input {
        width: 100%;
    }
    button.dt-button.buttons-excel.buttons-html5 {
    margin: 21px;
}
</style>