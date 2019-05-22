<?php

require_once("../../config.php");
//require_once("lib.php");
//require_once($CFG->libdir.'/tablelib.php');

////////////////////////////////////////////////////////
//get the params
////////////////////////////////////////////////////////
$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', false, PARAM_INT);
$do_show = required_param('do_show', PARAM_ALPHA);
$perpage = optional_param('perpage', FEEDBACK_DEFAULT_PAGE_COUNT, PARAM_INT);  // how many per page
$showall = optional_param('showall', false, PARAM_INT);  // should we show all users
// $SESSION->feedback->current_tab = $do_show;
$current_tab = $do_show;

////////////////////////////////////////////////////////
//get the objects
////////////////////////////////////////////////////////

?>

<link href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">


<?php

if (! $cm = get_coursemodule_from_id('feedback', $id)) {
    print_error('invalidcoursemodule');
}

if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
    print_error('coursemisconf');
}

if (! $feedback = $DB->get_record("feedback", array("id"=>$cm->instance))) {
    print_error('invalidcoursemodule');
}

$url = new moodle_url('/mod/feedback/show_entries_details.php', array('id'=>$cm->id, 'do_show'=>$do_show));

$PAGE->set_url($url);

$context = context_module::instance($cm->id);

require_login($course, true, $cm);

require_capability('mod/feedback:viewreports', $context);


if ($do_show == 'showoneentry') {
    //get the feedbackitems
    $feedbackitems = $DB->get_records('feedback_item', array('feedback'=>$feedback->id), 'position');

    $params = array('feedback'=>$feedback->id,
                    'userid'=>$userid,
                    'anonymous_response'=>FEEDBACK_ANONYMOUS_NO);

    $feedbackcompleted = $DB->get_record('feedback_completed', $params); //arb
}

/// Print the page header
$strfeedbacks = get_string("modulenameplural", "feedback");
$strfeedback  = get_string("modulename", "feedback");

$PAGE->set_heading($course->fullname);
$PAGE->set_title($feedback->name);
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($feedback->name));

require('tabs.php');





if ($do_show == 'showallresponses') {

	// print_object($cm);
	// print_object($course);
	// print_object($feedback);

	$qry1="select distinct(fc.userid) as user_id,fc.id as completion_number,concat(u.firstname,' ',u.lastname) as username,u.username as user_name,fc.timemodified as completion_time from {feedback_completed} as fc join {user} as u on u.id=fc.userid where fc.feedback=$feedback->id group by user_id";

	$results1=$DB->get_records_sql($qry1);


    $qry8="select tagid from {tag_instance} where itemid=$feedback->id and itemtype='feedback' and component='mod_feedback'";

    $results8=$DB->get_record_sql($qry8);

    $tagid=$results8->tagid;

	  //print_object($results1);
	// echo "string";
	// echo $results1->username;
	// echo "string";
	// die();

	$qry2="select id,name from {feedback_item} where feedback=$feedback->id and typ !='label'";

	$results2=$DB->get_records_sql($qry2);

    $results5=$results2;

	 //print_object($results2);


	?>

    <input type="button" class="btn btn-success" name="export" id="cmd" value="Export">

	<table id="example" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Name</th>
                <th>UserID</th>
                <th>Date</th>
                <?php
                foreach ($results2 as $results2) {
                	$question=$results2->name;
                	?>
                	<th><?php echo $question; ?></th>
                	<?php
                }

                if ($tagid==1) {
                    ?>

                    <th>Attendance</th>

                    <?php
                }

                ?>

                
            </tr>
        </thead>
        <tbody>
        	<?php
        	foreach ($results1 as $results1) {
                $username=$results1->user_name;
                $user_id=$results1->user_id;
        		$user_name=$results1->username;
        		$submission_time=$results1->completion_time;

                $completion_number=$results1->completion_number;
        		?>
        	<tr>
                <td><?php echo $user_name; ?></td>
                <td><?php echo $username; ?></td>
                <td><?php echo date('d/m/Y H:i:s', $submission_time); ?></td>
                <?php

                foreach ($results5 as $results51) {
                    //$item_no=$results5->id;
                     $qry3="select value from {feedback_value} where item='$results51->id' and completed=$completion_number";
                    $results3=$DB->get_record_sql($qry3);

                    $given_rating=$results3->value;
                    ?>

                    <td><?php echo $given_rating; ?></td>
                

                  <?php  
                }

                $qry11="select ispresent from {feedback_tracking} where userid=$user_id and completed=$completion_number and feedback=$feedback->id";
                    $results11=$DB->get_record_sql($qry11);

                    $attendance=$results11->ispresent;

                    if ($attendance==1) {
                        
                        $attendance='Present';
                    }else{
                        $attendance='Absent';
                    }

                    if($tagid==1){

                        ?>
                        <td><?php echo $attendance; ?></td>
                        <?php

                    }


                ?>
                
            </tr>
        		<?php
        	}

            ?>
            
            
        </tbody>
    </table>


   <?php
    }
        
        echo $OUTPUT->footer();

?>

<script type="text/javascript" src="table2excel.js"></script> 


<script type="text/javascript">
	
 $(document).ready(function() {
    $('#cmd').on("click",function(){
         $("#example").table2excel({
                filename: "Report.xls"
            });
        

    });
});


</script>
   
