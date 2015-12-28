# pgprocedures
This class can be used to easily call PostgreSQL stored procedures from your PHP code. 

## PRE-REQUISITES

- Add the PL/PgSQL language to your database, if not done:
  CREATE LANGUAGE plpgsql;

- Execute the script pgprocedures.sql on your database.

## USAGE
```php
<?php
  // Include this file
  require_once 'pgprocedures.php';
```
```php
  // Instanciate the class with the arguments necessary to connect to the database
  $base = new PgProcedures2 ($pg_host, $pg_user, $pg_pass, $pg_database);
```
```php
  // Call a stored procedure: just use the name of the stored procedure itself, 
  // with its corresponding arguments.
  // Depending on the return type of the stored procedure, the $ret variable will be 
  // - a simple variable (for, for example, RETURNS INTEGER), 
  // - an array (for, for example, RETURNS SETOF INTEGER), 
  // - a map (for, for example, RETURNS a_composite_type) or 
  // - an array of maps (for, for example, RETURNS SETOF a_composite_type).
  $ret = $base->my_plpgsql_function ($arg1, $arg2);
```
```php
  // You can order the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->order('col1', 'DESC'));
```
```php
  // Limit the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->limit(20));
  // ... with an offset:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->limit(20, 60));
```
```php
  // Get DISTINCT values from the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->distinct ());
  // Get COUNT(*) from the result:
  $ret = $base->my_plpgsql_function ($arg1, $arg2, $base->count ());
```
```php
  // You can also use these three functions for transactions:
  $base->startTransaction ();
  $base->commit ();
  $base->rollback ();
```
```php
  // If the name of the stored procedure is contained in a string, you can call your function like this:
  $base->__call ($my_string_containing_the_stored_procedure_name, 
                 $my_array_containing_the_args_of_the_stored_procedure);
  // By default, dates are returned with the format "d/m/Y", and times with the format "H:i:s" 
  // (see the documentationfor the PHP __ date __ function for more information about date formats).
```
```php
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
```
```php
  // You can get the character set used in the database to store the text with the following function:
  $base->get_client_encoding ();
  // By default, text will be returned using the server character set. You can enable automatic character set conversion 
  // between server and client specifying with the following function the character set in which you want the text to be retrieved:
  $base->set_client_encoding ('LATIN1');
  // Stored procedures prefixed by an underscore (_) are not accessible through this class.
?>
```
