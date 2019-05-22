<?php 
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/moodlelib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/messagelib.php');



$toadmin = "parul@shezartech.in"; 
$user = new stdClass();
$user->email     = $toadmin;
$user->firstname = 'admin';
// $user->lastname = 'xyz';
$user->maildisplay = true;
$user->mailformat = 1;
$user->id = 2;




$message = new \core\message\message();
$message->component = 'moodle';
$message->name = 'instantmessage';
$message->userfrom = $USER;
$message->userto = $user;
$message->subject = 'message subject 1';
$message->fullmessage = 'message body';
$message->fullmessageformat = FORMAT_MARKDOWN;
$message->fullmessagehtml = '<p>message body</p>';
$message->smallmessage = 'small message';
$message->notification = '0';
$message->contexturl = 'http://GalaxyFarFarAway.com';
$message->contexturlname = 'Context name';
$message->replyto = "random@example.com";
$content = array('*' => array('header' => ' test ', 'footer' => ' test ')); // Extra content for specific processor
$message->set_additional_content('email', $content);
 
// Create a file instance.
$usercontext = context_user::instance($user->id);
$file = new stdClass;
$file->contextid = $usercontext->id;
$file->component = 'user';
$file->filearea  = 'private';
$file->itemid    = 0;
$file->filepath  = '/';
$file->filename  = '1.txt';
$file->source    = 'test';
 
$fs = get_file_storage();
$file = $fs->create_file_from_string($file, 'file1 content');
$message->attachment = $file;
 
$messageid = message_send($message);