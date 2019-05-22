<?php
require_once (dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
class local_programenrolemail_observer
{
    public static function program_enrol_completion(shezar_program\event\program_assigned $event)

    {
    	global $CFG,$DB;
		
          $event_data = $event->get_data();
			
		 $users=$DB->get_record_sql('SELECT * FROM {user} where id = ?',array($event_data['userid']));
		 $user = new stdClass();
			 $user->id = $users->id;
             $user->email     = $users->email;
             $user->firstname = $users->firstname;
 			 $user->lastname = $users->lastname;
             $user->maildisplay = true;
             $user->mailformat = 1;
		
		 $programdetails=$DB->get_record_sql('SELECT * FROM {prog} where id = ?',array($event_data['objectid']));
		
		 
		 $admins=$DB->get_record_sql('SELECT * FROM {user} where id = ?',array('2'));
		 $admin = new stdClass();
			 $admin->id = $admins->id;
             $admin->email     = $admins->email;
             $admin->firstname = $admins->firstname;
 			 $admin->lastname = $admins->lastname;
             $admin->maildisplay = true;
             $admin->mailformat = 1;
		
             
             $emailsubject = "Siksha Portal: Enrolled In $programdetails->fullname Program";
           
             $emailmessage = "Hi,

You are now enrolled in $programdetails->fullname program

You can start with completing the courses.

Visit https://siksha.tvscredit.com/ --  My Learning 

Login with your OLD bandhan ID and password.
To retrieve your password, Click on 'Forgot Password'.

For any further assistance, Feel free to contact us.

Regards,
Learning.Development@tvscredit.com";
            
              $sent= email_to_user($user, $admin, $emailsubject, $emailmessage);
			
    }
	
}
