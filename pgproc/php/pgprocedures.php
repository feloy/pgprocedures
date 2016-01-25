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
- package php5-pgsql
- package phpunit (for tests)
- Add the PL/PgSQL language to your database, if not done:
  CREATE LANGUAGE plpgsql;
- Execute the script pgprocedures.sql on your database.

*** 2 *** USAGE:

<?php
  // Include this file
  require_once 'pgprocedures.php';

  // Instanciate the class with the arguments necessary to connect to the database
  $base = new PgProcedures2 ($pg_host, $pg_user, $pg_pass, $pg_database);

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
  $base->set_timestamp_arg_format ('%Y-%m-%d %H:%M:%S %p');
  $base->set_date_arg_format ('%Y-%m-%d');
  $base->set_time_arg_format ('%H:%M:%S %p');

  // You can get the character set used in the database to store the text with the following function:
  $base->get_client_encoding ();

  // By default, text will be returned using the server character set. You can enable automatic character set conversion 
  // between server and client specifying with the following function the character set in which you want the text to be retrieved:
  $base->set_client_encoding ('LATIN1');

  // Stored procedures prefixed by an underscore (_) are not accessible through this class.
?>

*/


require_once (dirname(__FILE__) . '/PgProcException.class.php');
require_once (dirname(__FILE__) . '/PgProcFunctionNotAvailableException.class.php');
require_once (dirname(__FILE__) . '/PgSchema.class.php');
require_once (dirname(__FILE__) . '/PgProcedures2.class.php');

?>
