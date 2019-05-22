<?php
require_once (dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
class local_coursecompletionemail_observer
{
    public static function course_modules_completion(core\event\course_completed $event)

    {
    	global $CFG,$DB;
		
          $event_data = $event->get_data();
			
			
		 $users=$DB->get_record_sql('SELECT * FROM {user} where id = ?',array($event_data['relateduserid']));
		 $user = new stdClass();
			 $user->id = $users->id;
             $user->email     = $users->email;
             $user->firstname = $users->firstname;
 			 $user->lastname = $users->lastname;
             $user->maildisplay = true;
             $user->mailformat = 1;
		
		 $coursedetails=$DB->get_record_sql('SELECT * FROM {course} where id = ?',array($event_data['courseid']));
		
		 
		 $admins=$DB->get_record_sql('SELECT * FROM {user} where id = ?',array('2'));
		$admin = new stdClass();
			 $admin->id = $admins->id;
             $admin->email     = $admins->email;
             $admin->firstname = $admins->firstname;
 			 $admin->lastname = $admins->lastname;
             $admin->maildisplay = true;
             $admin->mailformat = 1;
		
             
             $emailsubject = 'Course Completion';
           
             $emailmessage = "Hi,
 
You have successfully completed $coursedetails->fullname
 
For any further assistance, Feel free to contact us.
 
Regards,
Learning & Development";

            
              $sent= email_to_user($user, $admin, $emailsubject, $emailmessage);
			
    }
}
