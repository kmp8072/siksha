
<?php

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');



$strheading = "Custom Notification";

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/custom_notification/dashboard.php'));

$PAGE->set_title($strheading);

$PAGE->navbar->add($strheading);





require_login();







echo $OUTPUT->header();

global $SESSION;



?>

<h2>Custom Notification</h2>

<div id="success" style="text-align: center;></div>
  

<?php


if(isset($_POST['submit'])){

$courseid=$_POST['courseid'];
$message=$_POST['Message'];

$query="SELECT DISTINCT u.id AS userid, u.device_token AS device_token, c.id AS courseid, DATE_FORMAT(FROM_UNIXTIME(ue.timecreated),'%m/%d/%Y') AS timecreated
FROM mdl_user u
JOIN mdl_user_enrolments ue ON ue.userid = u.id
JOIN mdl_enrol e ON e.id = ue.enrolid
JOIN mdl_role_assignments ra ON ra.userid = u.id
JOIN mdl_context ct ON ct.id = ra.contextid
AND ct.contextlevel =50
JOIN mdl_course c ON c.id = ct.instanceid
AND e.courseid = c.id
JOIN mdl_role r ON r.id = ra.roleid
AND r.shortname =  'student'
WHERE e.status =0
AND u.suspended =0
AND u.deleted =0
AND (
ue.timeend =0
OR ue.timeend > NOW( )
)
AND ue.status =0
AND courseid =$courseid";

$enrolled_users=$DB->get_records_sql($query);

 define( 'API_ACCESS_KEY', 'AAAAnK1uKQE:APA91bFci9UuTjgzfSZAp1Xniz1NiPjN2Fx10iFFoQruC62NG3jgQhpQ4T3vMDVhvrXy9HfKdI1etFgcxqPNErJkI9nGOpDyUshpGDhlt2hl1TcNLp6M2kuP2ED4Ys9_S8Wnh3_-I6QX' );


$timecreated=time();

foreach ($enrolled_users as $enrolled_user) {
  
  $device_token=$enrolled_user->device_token;
  $userid=$enrolled_user->userid;
  $registrationIds = $device_token;

  $query12="SELECT `pushid` FROM mdl_user_devices WHERE id=(SELECT max(id) FROM mdl_user_devices WHERE userid=$userid)";
  $pushid_object=$DB->get_record_sql($query12);
  $pushid=$pushid_object->pushid;

  $query11="INSERT INTO {message} (useridfrom, useridto,fullmessage,fullmessageformat,fullmessagehtml,  smallmessage,timecreated,notification)
            VALUES (2, $userid, '$message',1,'$message','$message',$timecreated,1)";

  $DB->execute($query11);

#prep the bundle

     $notification = array('title' => 'Notification - Siksha App',
                        'body'=> 'click to talk',
                        'sound'=>'default'
                         );
     $data = array
          (
        'body'  => $message,
        'title' => 'Title Of Notification',
                //'icon'  => 'myicon',/*Default Icon*/
                //'sound' => 'mySound'/*Default sound*/

          );
    $fields = array
            (
                'to'        => $pushid,
                //'notification'  => $msg,
                //'registration_ids'  => $ids,
                'data'              => $data, 
                'content_available'    => true,                 
                'priority'              => 'high',    
                'notification' => $notification,
            );
    
    
    $headers = array
            (
                'Authorization: key=' . API_ACCESS_KEY,
                'Content-Type: application/json'
            );
#Send Reponse To FireBase Server    
        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch);
        curl_close( $ch );

 
}

}
?>
             

 <?php

echo $OUTPUT->footer();

?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
<script>

$(document).ready(function(){

$('#success').html("Your Message sent Successfully");

});
</script>
