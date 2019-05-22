<?php
 
/*
 * DataTables example server-side processing script.
 *
 * Please note that this script is intentionally extremely simple to show how
 * server-side processing can be implemented, and probably shouldn't be used as
 * the basis for a large complex system. It is suitable for simple use cases as
 * for learning.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */
 
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */
 
// DB table to use
$table = 'view_all_user_details_new';
 
// Table's primary key
$primaryKey = 'id';
 
// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes
$col = 0;
$columns = array(
 array( 'db' => 'id', 'dt' => $col++ ),
    array( 'db' => 'username  ', 'dt' => $col++ ),
    array( 'db' => 'fullname',  'dt' => $col++ ),
    array( 'db' => 'email', 'dt' => $col++ ),
    array( 'db' => 'unit',  'dt' => $col++ ),
    array( 'db' => 'region', 'dt' => $col++ ),
    array( 'db' => 'plarea',  'dt' => $col++ ),
    array( 'db' => 'location', 'dt' => $col++ ),
    array( 'db' => 'state',  'dt' => $col++ ),
    array( 'db' => 'depatment',   'dt' => $col++ ),
    array( 'db' => 'designation',     'dt' => $col++ ),
    array( 'db' => 'guru_id',  'dt' => $col++ ),
    array( 'db' => 'guruname', 'dt' => $col++ ),
    array( 'db' => 'lastlogin',  'dt' => $col++ ),
    array( 'db' => 'firstaccess',   'dt' => $col++ ),
    array( 'db' => 'lastaccess',     'dt' => $col++ ),
    array(
        'db'        => 'lastaccess',
        'dt'        => $col++,
        'formatter' => function( $d, $row ) {
            return date( 'jS M y', strtotime($d));
        }
    )
    
);


 

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 */
 
require( 'ssp.class.php' );
 
echo json_encode(
    SSP::simple( $_GET, $sql_details, $table, $primaryKey, $columns )
);