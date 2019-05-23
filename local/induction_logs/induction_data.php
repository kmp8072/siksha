<?php

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