<?php
require 'pgprocedures.php';
require_once ('config.inc.php');
$base = new PgProcedures2 ($pg_host, $pg_user, $pg_pass, $pg_database);

$cmd = $_SERVER['PHP_SELF'];
$cmd = basename ($cmd, '.php');

if (strpos($cmd, '@')) {
  list($schema, $function) = explode('@', $cmd, 2);
} else {
  $schema = 'public';
  $function = $cmd;
}

$debug = false;

$ret = $base->get_arguments ($schema, $function);
$all = false;
if (count ($ret)) {
  foreach ($ret as $r) {
    $all = true;
    foreach ($r['argnames'] as $argname) {
      if (!isset ($_REQUEST[$argname])) {
	if ($debug) {
	  echo "$argname not found\n" ;
	  print_r ($_REQUEST);
	}
	$all = false;
	break;
      }
    }
    if ($all) {
      break;
    }    
  }
}

// Continue, $r contains argnames and argtypes

$args = array ();
if ($all) {
  foreach ($r['argnames'] as $argname) {
    $args[] = get_magic_quotes_gpc() ? stripslashes($_REQUEST[$argname]) : $_REQUEST[$argname];
  }
 }
$results = $base->$schema->__call ($function, $args);

header ('Content-Type: application/json ; charset=utf-8');
header ('Cache-Control: no-cache , private');
header ('Pragma: no-cache');
if ($results) 
  echo json_encode ($results);
else 
  echo '[]';
exit;
