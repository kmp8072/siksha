<?php  // shezar configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'sikshanew';
$CFG->dbuser    = 'root';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '3307',
  'dbsocket' => '',
);

$CFG->wwwroot   = 'http://192.168.1.86:7777/siksha';
$CFG->dataroot  = 'D:\xampp\sikshadata';
$CFG->admin     = 'admin';


$CFG->directorypermissions = 0777;

require_once(dirname(__FILE__) . '/lib/setup.php');

define('servername', 'siksha');

define('initialradius', 75);
define('radiusincrease', 10);
define('maxradius', 125);



// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!