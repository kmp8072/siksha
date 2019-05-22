<?php  

define('ALL', 1);
define('APP_ACCESSED', 2);
define('APP_NOT_ACCESSED_YET', 3);
define('GURU_MAPPED', 4);
define('GURU_NOT_MAPPED', 5);
define('APP_ACCESSED_BUT_GURU_NOT_MAPPED', 6);

// "SELECT distinct(u.id),u.email,u.username,u.timecreated,CONCAT(u.firstname,' ',u.lastname) AS fullname,u.firstaccess,u.lastaccess,u.lastlogin,gnm.guru_id,gnm.nj_id,CONCAT(g.firstname,' ',g.lastname) AS guruname ,gnm.status,uidunit.data AS unit,uid.data AS region,uidpl.data AS plarea,uids.data AS state,uidl.data AS location,uiddept.data AS depatment,uiddes.data AS designation FROM mdl_user u 
// JOIN mdl_role_assignments ra ON ra.userid=u.id 
// JOIN mdl_user_info_data uidunit ON uidunit.userid=u.id AND uidunit.fieldid=2 

// JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 
// JOIN mdl_user_info_data uidpl ON uidpl.userid=u.id AND uidpl.fieldid=4 
// JOIN mdl_user_info_data uidl ON uidl.userid=u.id AND uidl.fieldid=5 
// JOIN mdl_user_info_data uids ON uids.userid=u.id AND uids.fieldid=6 
// JOIN mdl_user_info_data uiddept ON uiddept.userid=u.id AND uiddept.fieldid=7 
// JOIN mdl_user_info_data uiddes ON uiddes.userid=u.id AND uiddes.fieldid=8 
// LEFT JOIN mdl_user g ON g.id=gnm.guru_id 
// LEFT JOIN mdl_guru_nj_mapping gnm ON gnm.nj_id=u.id 
// WHERE ra.roleid=4 OR ra.roleid=5 AND (gnm.status!=1 OR gnm.status!=0) order by 1 DESC LIMIT 0,10"


// SELECT distinct(u.id),u.email,u.username,u.timecreated,CONCAT(u.firstname,' ',u.lastname) AS fullname,u.firstaccess,u.lastaccess,u.lastlogin,uidunit.data AS unit,uid.data AS region,uidpl.data AS plarea,uids.data AS state,uidl.data AS location,uiddept.data AS depatment,uiddes.data AS designation, gnm.status FROM mdl_user u 
// JOIN mdl_role_assignments ra ON ra.userid=u.id 
// JOIN mdl_user_info_data uidunit ON uidunit.userid=u.id AND uidunit.fieldid=2 

// JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 
// JOIN mdl_user_info_data uidpl ON uidpl.userid=u.id AND uidpl.fieldid=4 
// JOIN mdl_user_info_data uidl ON uidl.userid=u.id AND uidl.fieldid=5 
// JOIN mdl_user_info_data uids ON uids.userid=u.id AND uids.fieldid=6 
// JOIN mdl_user_info_data uiddept ON uiddept.userid=u.id AND uiddept.fieldid=7 
// JOIN mdl_user_info_data uiddes ON uiddes.userid=u.id AND uiddes.fieldid=8 
// LEFT JOIN mdl_guru_nj_mapping gnm ON gnm.nj_id=u.id 

// WHERE ra.roleid=4 OR ra.roleid=5 AND (gnm.status IS NULL )

?>