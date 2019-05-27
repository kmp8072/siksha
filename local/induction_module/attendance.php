<?php
//error_reporting(0);

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');

$strheading = "Induction Attendance"; //heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/induction_module/attendance.php'));
$PAGE->set_title($strheading);
?>
<html>
    <head>

        <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.2.0/sweetalert2.min.css">

    </head>

    <?php
    require_login();
    echo $OUTPUT->header();
    global $_SESSION;
    global $USER, $DB;
    ?>
    <body>
        <h2><?php echo $strheading; ?></h2>

        <button id="success" style="display: none;">Success</button>
        <button id="error" style="display: none;">Error</button>

        <table id="example" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>New Joinee Name</th>
                    <th>Guru's Name</th>
                    <th>Region</th>
                    <th>Unit</th>
                    <th>Department</th>
                    <th>Designation</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $SQL = " SELECT u.id, u.username, u.firstname, u.lastname, u.phone1, d.data as unit, d1.data as region, d2.data as department, 
                        d3.data as designation, d4.data as plarea, CONCAT(g.firstname, ' ', g.lastname) AS guruname FROM {user} u
                      
                        INNER JOIN {guru_nj_mapping} m ON m.nj_id = u.id 
                        INNER JOIN {user} g ON g.id = m.guru_id
                        INNER JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = 2
                        INNER JOIN {user_info_data} d1 ON d1.userid = u.id AND d1.fieldid = 3
                        INNER JOIN {user_info_data} d2 ON d2.userid = u.id AND d2.fieldid = 7
                        INNER JOIN {user_info_data} d3 ON d3.userid = u.id AND d3.fieldid = 8
                        INNER JOIN {user_info_data} d4 ON d4.userid = u.id AND d4.fieldid = 4
                        WHERE m.guru_id > 0 ";
                $records = $DB->get_records_sql($SQL);
                foreach ($records as $record) {
                    ?>
                    <tr>
                        <td><?php echo $record->username; ?></td>
                        <td><?php echo $record->firstname . ' ' . $record->lastname; ?></td>
                        <td><?php echo $record->guruname; ?></td>
                        <td><?php echo $record->region; ?></td>
                        <td><?php echo $record->unit; ?></td>
                        <td><?php echo $record->department; ?></td>
                        <td><?php echo $record->designation; ?></td>
                        <td><button type="button" data-toggle="modal" data-target="#attendanceModal" class="viewAttendance" value="<?php echo $record->id ?>">View</button></td>

                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </body> 

    <div class="modal fade" id="attendanceModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLongTitle" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle">ATTENDANCE DETAILS</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('.viewAttendance').click(function (e) {
                e.preventDefault();
                var userid = $(this).attr('value');

                if (userid) {
                    $.ajax({
                        url: '../commonAjax.php',
                        type: 'POST',
                        data: {action: 'GETATTENDANCEDETAIL', userid: userid},
                        success: function (response) {
                            $('.modal-body').html(response);
                        }
                    });
                }
            });


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
 
    var table = $('#example').DataTable( {
        orderCellsTop: true,
        fixedHeader: true
    } );

        });

       function markattendance(id) {

        var res = id.split("-");

        var id=res[0];

        var userid=res[1];


            var bool = confirm("Are you sure?");
            
            // if confirmed true mark attendance
            if (bool) {

                //$("#attendanceModal .close").click();

                    $.ajax({
                        url: '../commonAjax.php',
                        type: 'POST',
                        data: {action: 'MARKATTENDANCE', id: id},
                        success: function (response) {
                            if (response==1) {

                                $('#success').click();
                 if (userid) {
                    $.ajax({
                        url: '../commonAjax.php',
                        type: 'POST',
                        data: {action: 'GETATTENDANCEDETAIL', userid: userid},
                        success: function (response) {
                            $('.modal-body').html(response);
                        }
                    });
                }

                            }

                            if (response==0) {

                              $('#error').click();  
                            }
                        }
                    });
                

            }else{

                //$("#attendanceModal .close").click();
            }

        } 


        $( "#success" ).click(function(e) {
                              swal(
                'Success',
                'Updated <b style="color:green;">Successfully</b> !',
                'success'
                
            )
            });

        $( "#error" ).click(function(e) {
                              swal(
                'Error!',
                'Something went <b style="color:red;">Wrong</b> !',
                'error'
                
            )
            });
    </script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/limonte-sweetalert2/7.2.0/sweetalert2.all.min.js"></script>
  <script type="text/javascript" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
    <?php
    echo $OUTPUT->footer();
    ?>

