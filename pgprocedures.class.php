<?php
/* Copyright © 2014 ELOL
   Written by Philippe Martin (contact@elol.fr)
--------------------------------------------------------------------------------
    This file is part of pgprocedures.

    pgprocedures is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    pgprocedures is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with pgprocedures.  If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------

This class can be used to easily call PostgreSQL stored procedures from your PHP scripts.

*** 1 *** PRE-REQUISITES:
- Add the PL/PgSQL language to your database, if not done:
  CREATE LANGUAGE plpgsql;
- Execute the script pgprocedures.sql on your database.

*** 2 *** USAGE:

<?php
  // Include this file
  require_once 'pgprocedures.class.php';

  // Instanciate the class with the arguments necessary to connect to the database
  $base = new PgProcedures ($pg_host, $pg_user, $pg_pass, $pg_database);

  // Call a stored procedure: just use the name of the stored procedure itself, with its corresponding arguments
  // Depending on the return type of the stored procedure, the $ret variable will be 
  // - a simple variable (for, for example, RETURNS INTEGER), 
  // - an array (for, for example, RETURNS SETOF INTEGER), 
  // - a map (for, for example, RETURNS a_composite_type) or 
  // - an array of maps (for, for example, RETURNS SETOF a_composite_type).
  $ret = $base->my_plpgsql_function ($arg1, $arg2);

  // You can order the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->order('col1', 'DESC'));

  // Limit the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->limit(20));
  // ... with an offset:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->limit(20, 60));

  // Get DISTINCT values from the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->distinct ());

  // Get COUNT(*) from the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->count ());

  // You can also use these three functions for transactions:
  $base->startTransaction ();
  $base->commit ();
  $base->rollback ();

  // If the name of the stored procedure is contained in a string, you can call your function like this:
  $base->__call ($my_string_containing_the_stored_procedure_name, $my_array_containing_the_args_of_the_stored_procedure);

  // By default, dates are returned with the format "d/m/Y", and times with the format "H:i:s" 
  // (see the documentationfor the PHP __ date __ function for more information about date formats).
  // You can change these formats with the following functions:
  $base->set_timestamp_return_format ('Y-m-d h:i:s A');
  $base->set_date_return_format ('Y-m-d');
  $base->set_time_return_format ('h:i:s A');

  // By default, when you pass dates and times as arguments to a function, 
  // you have to use the formats '%d/%m/%Y' and '%H:%M:%S'
  // (see the documentation for the PHP __ strftime __ function for more information about this format).
  // You can change these formats with the following functions: 
  $base->set_timestamp_arg_format ('%Y-%m-%d %I:%M:%S %p');
  $base->set_date_arg_format ('%Y-%m-%d');
  $base->set_time_arg_format ('%I:%M:%S %p');

  // You can get the character set used in the database to store the text with the following function:
  $base->get_client_encoding ();

  // By default, text will be returned using the server character set. You can enable automatic character set conversion 
  // between server and client specifying with the following function the character set in which you want the text to be retrieved:
  $base->set_client_encoding ('LATIN1');

  // Stored procedures prefixed by an underscore (_) are not accessible through this class.
?>

*/

class PgProcException extends Exception {
  function __construct ($msg) { parent::__construct ($msg); }
}

class PgProcFunctionNotAvailableException extends Exception {
  function __construct ($msg) { parent::__construct ($msg); }
}

class PgSchema {
  private $base;
  private $name;

  function __construct ($base, $name) {
    $this->base = $base;
    $this->name = $name;
  }

  public function __call ($method, $args) {
    if (substr ($method, 0, 1) != '_') {
      $pargs = end ($args);
      $limit = NULL;
      $orders = array ();
      $distinct = false;
      $count = false;
      $nargstodel = 0;
      while (1) {
	if ($this->is_order_arg ($pargs)) {
	  $orders[] = $pargs['order'];
	} else if ($this->is_limit_arg ($pargs)) {
	  $limit = $pargs['limit'];
	} else if ($this->is_distinct_arg ($pargs)) {
	  $distinct = true;
	} else if ($this->is_count_arg ($pargs)) {
	  $count = true;
	} else
	  break;
	$pargs = prev ($args);
	$nargstodel++;
      }
      reset ($args);
      for ($i=0; $i<$nargstodel; $i++)
	array_pop ($args);

      // Search the argument and return types for this function
      list ($schema, $argtypes, $rettype, $retset) = $this->search_pg_proc ($method, $args);
      if (!is_array ($argtypes) || !strlen ($schema)) {
	// Function not found
	throw new PgProcFunctionNotAvailableException ('Function '.$this->name.'.'.$method.' not available');
      } 
      // Create the SQL string to call the function
      $query = "SELECT ";
      if ($distinct)
	$query .= 'DISTINCT ';
      if ($count)
	$query .= "COUNT(*) ";
      else 
	$query .= "* ";
      $query .= "FROM ".$schema.".".$method." (  ";
      foreach ($argtypes as $i => $argtype) {
	$value = $args[$i];
	if ($value === NULL)
	  $sqlvalue = 'null';
	else {
	  $sqlvalue = $this->escape_value ($argtype, $value);
	}
	$query .= $sqlvalue.", ";
      }
      $query = substr ($query, 0, -2);
      $query .= ")";

      if (!empty ($orders)) {
	$orders = array_reverse ($orders);
	$orderstr = ' ORDER BY ';
	foreach ($orders as $order) {
	  foreach ($order as $k => $v) {
	    $orderstr .= $k." ".$v.", ";
	  }
	}
	$orderstr = substr ($orderstr, 0, -2);
	$query .= $orderstr;
      }

      if ($limit != NULL) {
	foreach ($limit as $k => $v) {
	  $query .= ' LIMIT '.$k;
	  if ($v)
	    $query .= ' OFFSET '.$v;
	}
      }
      
      // Prepare the return value depending on the return type
      if ($count)  {
	if ($res = $this->pgproc_query ($query)) { 
	  $row = pg_fetch_array ($res);
	  return $row['count'];
	}

      } else if (is_array ($rettype)) { // Composite type

	if ($res = $this->pgproc_query ($query)) { 

	  if ($retset) { // SETOF
	    $retsetvalue = array ();
	    while ($row = pg_fetch_array ($res)) {
	      $ret = array ();
	      foreach ($rettype as $name => $subtype) {
		$ret[$name] = $this->cast_value ($subtype, $row[$name]);
	      }
	      $retsetvalue[] = $ret;
	    }
	    if (empty ($retsetvalue))
	      return NULL;
	    else
	      return $retsetvalue;

	  } else { // no SETOF
	    if ($row = pg_fetch_array ($res)) {
	      $ret = array ();
	      foreach ($rettype as $name => $subtype) {
		$ret[$name] = $this->cast_value ($subtype, $row[$name]);
	      }
	      return $ret;
	    }
	  }
	}
	
      } else { // Scalar type

	if ($res = $this->pgproc_query ($query)) { 

	  if ($retset) { // SETOF
	    $retsetvalue = array ();
	    while ($row = pg_fetch_array ($res)) {
	      $retsetvalue[] = $this->cast_value ($rettype, $row[$method]);
	    }
	    if (empty ($retsetvalue))
	      return NULL;
	    else
	      return $retsetvalue;

	  } else { // no SETOF
	    if ($row = pg_fetch_array ($res)) {
	      return $this->cast_value ($rettype, $row[$method]);
	    }
	  }

	}
      }
    } else {
      throw new PgProcFunctionNotAvailableException ('Function not available');
    }
  }

  /* PRIVATE */
  private static function is_order_arg ($arg) {
    return (is_array ($arg) && isset ($arg['order']));
  }

  private static function is_distinct_arg ($arg) {
    return (is_array ($arg) && isset ($arg['distinct']));
  }

  private static function is_count_arg ($arg) {
    return (is_array ($arg) && isset ($arg['count']));
  }

  private static function is_limit_arg ($arg) {
    return (is_array ($arg) && isset ($arg['limit']));
  }

  /**
   * Search method by name and number of args
   * Returns: The types of the arguments
   */
  private function search_pg_proc ($method, $args) {
    $argtypenames = array ();
    $nargs = count ($args);

    $query = "SELECT * FROM pgprocedures.search_function ('".$this->name."', '$method', $nargs)";
    
    $rettypename = null;

    if ($res = $this->pgproc_query ($query)) {
      if ($row = pg_fetch_array ($res)) {
	$schema = $row['proc_nspname'];
	$argtypes = $row['proargtypes'];
	$rettype = $row['prorettype'];

	// Get the arguments types
	$argtypeslist = explode (' ', $argtypes);
	foreach ($argtypeslist as $argtype) {
	  if (!strlen (trim ($argtype)))
	    continue;

	  $argtypenames[] = $this->get_pgtype ($argtype);	 
	}

	if ($row['ret_nspname'] == 'pg_catalog' && ($row['ret_typtype'] == 'b' || $row['ret_typtype'] == 'p')) { // System scalar type
	  $rettypename = $row['ret_typname'];
	  
	} else if ($row['ret_nspname'] != 'pg_catalog' && $row['ret_typtype'] == 'c') { // User-defined composite type
	  $query3 = "select attname, typname FROM pg_attribute INNER JOIN pg_type ON pg_attribute.atttypid = pg_type.oid WHERE pg_attribute.attrelid = (select oid FROM pg_class where relname = '".$row['ret_typname']."') AND attnum > 0 ORDER BY attnum";
	  if ($res3 = $this->pgproc_query ($query3)) {
	    $rettypename = array();
	    while ($row3 = pg_fetch_array ($res3)) {
	      $rettypename[$row3['attname']] =  $row3['typname'];
	    }
	  }
	}

      }
    }
    if (count ($argtypenames) == $nargs)
      return array ($schema, $argtypenames, $rettypename, ($row['proretset'] == 't'));
    else
      return NULL;
  }

  public function cast_value ($rettype, $value) {
    if (substr ($rettype, 0, 1) == '_') {
      $v = substr ($value, 1, -1);
      $ret = explode (',', $v);
      foreach ($ret as &$r) {
	$r = $this->cast_value (substr ($rettype, 1), $r);
      }
      return $ret;
    }
    
    switch ($rettype) {
    case 'int2':
    case 'int4':
    case 'int8':
    case 'numeric':
    case 'text':
    case 'varchar':
    case 'bpchar':
    case 'float4':
    case 'float8':
      return $value;
      break;	  
      
    case 'interval':
      if (substr ($value, 0, 3) == '00:')
	return substr ($value, 3);
      else
	return $value;
      break;
      
    case 'timestamp':
    case 'timestamptz':
      if (strlen ($value)) {
	$timestamp = strtotime ($value);
	return date ($this->base->timestamp_return_format, $timestamp);
      } else
	return NULL;
      break;
      
    case 'date':
      if (strlen ($value)) {
	$timestamp = strtotime ($value);
	return date ($this->base->date_return_format, $timestamp);
      } else {
	return NULL;
      }
      break;
      
    case 'time';
    case'timetz':
      if (strlen ($value)) {
	$timestamp = strtotime ($value);
	return date ($this->base->time_return_format, $timestamp);
      } else
	return NULL;

      break;
      
    case 'bool':
      return ($value == 't');
      break;
      
    case '': // void
    case 'void':
      return;

    case 'oidvector':
      if (strlen ($value))
	return explode (' ', $value);
      else 
	return;

    default: 
      echo "Unknown type $rettype\n";
    }
  }

  private function escape_value ($type, $value) {
    if (substr ($type, 0, 1) == '_' && is_array ($value)) {
      $sqlvalue = 'ARRAY[';
      foreach ($value as $subvalue) {
	$sqlvalue .= $this->escape_value (substr ($type, 1), $subvalue) . '::' . substr($type, 1) . ", "; // TODO replace , by pg_type.typdelim
      }      
      if (count ($value) > 0)     
	$sqlvalue = substr ($sqlvalue, 0, -2);
      $sqlvalue .= ']';
      return $sqlvalue;
    }
    switch ($type) {
    case 'int2':
    case 'int4':
    case 'int8':
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = intval ($value);
      break;

    case 'numeric':
    case 'float4':
    case 'float8':
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = floatval ($value);
      break;
      
    case 'text':
    case 'varchar':
    case 'bpchar':
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = "'".pg_escape_string ($this->handler, $value)."'";
      break;

    case 'bool':
      $sqlvalue = $value ? 'true' : 'false';
      break;

    case 'timestamp':
    case 'timestamptz':
      $parts = strptime ($value, $this->base->timestamp_arg_format);
      if ($parts) {
	$timestamp = mktime($parts['tm_hour'], $parts['tm_min'], $parts['tm_sec'], 
			    $parts['tm_mon']+1, $parts['tm_mday'], $parts['tm_year']+1900);
	$sqlvalue = "'".date ('Y-m-d H:i:s', $timestamp)."'";
      } else {
	$sqlvalue = 'null';
      }
      break;

    case 'date':
      $parts = strptime ($value, $this->base->date_arg_format);
      if ($parts) {
	$timestamp = mktime($parts['tm_hour'], $parts['tm_min'], $parts['tm_sec'], 
			    $parts['tm_mon']+1, $parts['tm_mday'], $parts['tm_year']+1900);
	$sqlvalue = "'".date ('Y-m-d', $timestamp)."'";
      } else {
	$sqlvalue = 'null';
      }
      break;

    case 'time';
    case'timetz':
      $parts = strptime ($value, $this->base->time_arg_format);
      if ($parts) {
	$timestamp = mktime($parts['tm_hour'], $parts['tm_min'], $parts['tm_sec']);
	$sqlvalue = "'".date ('H:i:s', $timestamp)."'";
      } else {
	$sqlvalue = 'null';
      }
      break;

    default:
      if ($value === null)
	$sqlvalue = 'null';
      else
	$sqlvalue = "'".pg_escape_string ($this->handler, $value)."'";
    }
    return $sqlvalue;
  }

  public function get_pgtype ($oid) {
    if (isset ($this->pgtypes[$oid]))
      return $this->pgtypes[$oid];
    else {
      $query2 = "SELECT typname FROM pg_type WHERE oid=".$oid;
      if ($res2 = $this->pgproc_query ($query2)) {
	if ($row2 = pg_fetch_array ($res2)) {
	  $this->pgtypes[$oid] = $row2['typname'];
	  return $row2['typname'];
	}
      }
    }
  }

  private function pgproc_query ($q) {
    try {
      return pg_query ($this->handler, $q);
    } catch (Exception $e) {
      throw new PgProcException (pg_last_error($this->handler));
    }      
  }
}

class PgProcedures {

  // PG connection parameters
  private $server;
  private $user;
  private $password;
  private $db;
  private $port;

  private $handler; // PG connection handler

  private $pgtypes; // Store already read pg_types 

  // Format under which timestamp, date and time values will be returned
  public $timestamp_return_format;
  public $date_return_format;
  public $time_return_format;

  public $timestamp_args_format;
  public $date_args_format;
  public $time_args_format;

  public function __construct ($server, $user, $password, $db, $port = '5432') {
    $this->server = $server;
    $this->user = $user;
    $this->password = $password;
    $this->db = $db;
    $this->port = $port;
    $this->connect ();
    
    $this->pgtypes = array ();

    $this->timestamp_return_format = "d/m/Y H:i:s";
    $this->date_return_format = "d/m/Y";
    $this->time_return_format = "H:i:s";

    $this->timestamp_arg_format = '%d/%m/%Y %H:%M:%S';
    $this->date_arg_format = '%d/%m/%Y';
    $this->time_arg_format = '%H:%M:%S';
  }

  public function __destruct () {
    $this->disconnect ();
  }

  public function __call ($func, $args) {
    $schema_public = new PgSchema ($this, 'public');
    return $schema_public->__call ($func, $args);
  }

  public function __get ($schema_name) {
    return new PgSchema ($this, $schema_name);
  }

  public function set_timestamp_return_format ($timestamp_return_format) {
    $this->timestamp_return_format = $timestamp_return_format;
  }
  
  public function set_date_return_format ($date_return_format) {
    $this->date_return_format = $date_return_format;
  }
  
  public function set_time_return_format ($time_return_format) {
    $this->time_return_format = $time_return_format;
  }
  
  public function set_timestamp_arg_format ($timestamp_arg_format) {
    $this->timestamp_arg_format = $timestamp_arg_format;
  }
  
  public function set_date_arg_format ($date_arg_format) {
    $this->date_arg_format = $date_arg_format;
  }
  
  public function set_time_arg_format ($time_arg_format) {
    $this->time_arg_format = $time_arg_format;
  }

  public function get_client_encoding () {
    return pg_client_encoding ($this->handler);
  }
  
  public function set_client_encoding ($encoding) {
    return pg_set_client_encoding ($this->handler, $encoding);
  }
  
  public static function order ($attribute, $direction = 'ASC') {
    return array ('order' => array ($attribute => $direction));
  }

  public static function limit ($number, $offset = 0) {
    return array ('limit' => array ($number => $offset));
  }

  public static function distinct () {
    return array ('distinct' => true);
  }

  public static function count () {
    return array ('count' => true);
  }

  // Transactions
  public function startTransaction () {
    $this->pgproc_query ('START TRANSACTION');
  }

  public function commit () {
    $this->pgproc_query ('COMMIT');
  }

  public function rollback () {
    $this->pgproc_query ('ROLLBACK');    
  }

  public function get_arguments ($schema_name, $function_name) {
    $rets = $this->pgprocedures->search_arguments ($schema_name, $function_name);
    if (count ($rets)) {
      foreach ($rets as &$ret) {
	if (count ($ret['argtypes'])) {
	  foreach ($ret['argtypes'] as &$argtype) {	  
	    $argtype = $this->pgprocedures->get_pgtype ($argtype);
	  }
	}
      }
    }
    return $rets;
  }

  
  /***********
   * PRIVATE *
   ***********/
  private function connect () {
    $connectionString = "host=".$this->server." port=".$this->port." dbname=".$this->db." user=".$this->user." password=".$this->password;
    $this->handler = pg_connect ($connectionString);
  }

  private function disconnect () {
    if ($this->handler)
      pg_close ($this->handler);
  }
  
  
  public function execute_sql ($sql) {
    $ret = array ();
    $res = $this->pgproc_query ($sql);
    while ($row = pg_fetch_array ($res)) {
      $ret[] = $row;
    }
    return $ret;
  }

  private function pgproc_query ($q) {
    try {
      return pg_query ($this->handler, $q);
    } catch (Exception $e) {
      throw new PgProcException (pg_last_error($this->handler));
    }      
  }
}
?>
