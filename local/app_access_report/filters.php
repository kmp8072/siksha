<?php

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

global $USER, $DB, $CFG;

//find distinct regions

$distinct_regions_query="SELECT DISTINCT(data) FROM mdl_user_info_data WHERE fieldid=3";

$distinct_regions_obj=$DB->get_records_sql($distinct_regions_query);

//find distinct p&l area

$distinct_pl_query="SELECT DISTINCT(data) FROM mdl_user_info_data WHERE fieldid=4";

$distinct_pl_obj=$DB->get_records_sql($distinct_pl_query);

//find distinct location

$distinct_location_query="SELECT DISTINCT(data) FROM mdl_user_info_data WHERE fieldid=5";

$distinct_location_obj=$DB->get_records_sql($distinct_location_query);

//find distinct state

$distinct_state_query="SELECT DISTINCT(data) FROM mdl_user_info_data WHERE fieldid=6";

$distinct_state_obj=$DB->get_records_sql($distinct_state_query);

//find distinct department

$distinct_department_query="SELECT DISTINCT(data) FROM mdl_user_info_data WHERE fieldid=7";

$distinct_department_obj=$DB->get_records_sql($distinct_department_query);

//find distinct designation

$distinct_designation_query="SELECT DISTINCT(data) FROM mdl_user_info_data WHERE fieldid=8";

$distinct_designation_obj=$DB->get_records_sql($distinct_designation_query);

?>