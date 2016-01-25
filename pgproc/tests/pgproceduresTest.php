<?php
require_once '../php/pgprocedures.php';
require_once '../../config.inc.php';

class pgproceduresTest extends PHPUnit_Framework_TestCase {
  
  private static $base;
  private static $pgHost;
  private static $pgUser;
  private static $pgPass;
  private static $pgDatabase;

  public static function setUpBeforeClass() {

    // Get connection params
    global $pg_host, $pg_user, $pg_pass, $pg_database;
    self::$pgHost = $pg_host;
    self::$pgUser = $pg_user;
    self::$pgPass = $pg_pass;
    self::$pgDatabase = $pg_database;
    self::assertNotNull(self::$pgHost);
    self::assertNotNull(self::$pgUser);
    self::assertNotNull(self::$pgPass);
    self::assertNotNull(self::$pgDatabase);
    
    // Create object
    self::$base = new PgProcedures2 (self::$pgHost, self::$pgUser, self::$pgPass, self::$pgDatabase);
    self::assertNotNull(self::$base);
    
    // Import SQL functions
    $res = self::$base->execute_sql(file_get_contents('tests.sql'));
    self::assertEmpty($res);
  }

  /*********
   * TESTS *
   *********/

  /*
   * Return values
   */
  public function testReturnsInteger() {
    $res = self::$base->pgtests->test_returns_integer();
    $this->assertSame($res, 42);
  }

  public function testReturnsIntegerAsString() {
    $res = self::$base->pgtests->test_returns_integer_as_string();
    $this->assertSame($res, '42');
  }

  public function testReturnsString() {
    $res = self::$base->pgtests->test_returns_string();
    $this->assertSame($res, 'hello');
  }
  
  public function testReturnsNumeric() {
    $res = self::$base->pgtests->test_returns_numeric();
    $this->assertEquals($res, 3.14159, '', 0.00001);
  }
  
  public function testReturnsReal() {
    $res = self::$base->pgtests->test_returns_real();
    $this->assertEquals($res, 3.14, '', 0.00001);
  }

  public function testReturnsBoolTrue() {
    $res = self::$base->pgtests->test_returns_bool_true();
    $this->assertSame($res, true);
  }

  public function testReturnsBoolFalse() {
    $res = self::$base->pgtests->test_returns_bool_false();
    $this->assertSame($res, false);
  }

  public function testReturnsDate() {
    self::$base->set_date_return_format("d/m/Y");
    $res = self::$base->pgtests->test_returns_date();
    $this->assertRegExp('|^\d\d/\d\d/\d\d\d\d$|', $res);

    self::$base->set_date_return_format("Y-m-d");
    $res = self::$base->pgtests->test_returns_date();
    $this->assertRegExp('|^\d\d\d\d-\d\d-\d\d$|', $res);
  }

  public function testReturnsTimestamp() {
    self::$base->set_timestamp_return_format("d/m/Y H:i:s");
    $res = self::$base->pgtests->test_returns_timestamp();
    $this->assertRegExp('|^\d\d/\d\d/\d\d\d\d \d\d:\d\d:\d\d$|', $res);

    self::$base->set_timestamp_return_format("Y-m-d H:i");
    $res = self::$base->pgtests->test_returns_timestamp();
    $this->assertRegExp('|^\d\d\d\d-\d\d-\d\d \d\d:\d\d$|', $res);
  }

  public function testReturnsTime() {
    self::$base->set_time_return_format("H:i:s");
    $res = self::$base->pgtests->test_returns_time();
    $this->assertRegExp('|^\d\d:\d\d:\d\d$|', $res);

    self::$base->set_time_return_format("H:i");
    $res = self::$base->pgtests->test_returns_time();
    $this->assertRegExp('|^\d\d:\d\d$|', $res);
  }

  public function testReturnsComposite() {
    $res = self::$base->pgtests->test_returns_composite();
    $this->assertSame(array('a'=> 1, 'b'=> 'hello'), $res);
  }

  public function testReturnsSetofComposite() {
    $res = self::$base->pgtests->test_returns_setof_composite();
    $this->assertSame(array (array('a'=> 1, 'b'=> 'hello'), array('a'=> 2, 'b'=> 'bye')), $res);
  }

  /**
   * Not found function
   * @expectedException PgProcFunctionNotAvailableException
   */
  public function testNotFoundFunction() {
    $res = self::$base->pgtests->not_found_function();
  }

  /**
   * Not found hidden (prefixed with _) function
   * @expectedException PgProcFunctionNotAvailableException
   */
  public function testNotFoundHiddenFunction() {
    $res = self::$base->pgtests->_hidden_function();
  }

  /**
   * Function in right schema
   */
  public function testRightSchema() {
    $res = self::$base->pgtests->function_in_tests_schema();
    $this->assertTrue($res);
  }

  /**
   * Function in another schema
   * @expectedException PgProcFunctionNotAvailableException
   */
  public function testWrongSchema() {
    $res = self::$base->otherschema->function_in_tests_schema();
  }

  /**
   * @expectedException PgProcException
   */
  public function testFunctionRaisingException() {
    $res = self::$base->pgtests->function_raising_exception();
  }

  /*
   * Input arguments 
   */
  public function testIncrementedInteger() {
    $n = 4;
    $res = self::$base->pgtests->test_returns_incremented_integer($n);
    $this->assertSame($n + 1, $res);
  }

  public function testIncrementedNumeric() {
    $n = 3.14;
    $res = self::$base->pgtests->test_returns_incremented_numeric($n);
    $this->assertEquals($n + 1.5, $res, '', 0.00001);
  }

  public function testIncrementedReal() {
    $n = 1.414;
    $res = self::$base->pgtests->test_returns_incremented_real($n);
    $this->assertEquals($n + 1.42, $res, '', 0.00001);
  }

  public function testCatString() {
    $s = 'hello';
    $res = self::$base->pgtests->test_returns_cat_string($s);
    $this->assertSame($s . '.', $res);
  }

  public function testSameBool() {
    $res = self::$base->pgtests->test_returns_same_bool(true);
    $this->assertSame($res, true);

    $res = self::$base->pgtests->test_returns_same_bool(false);
    $this->assertSame($res, false);
  }

  public function testSameDate() {
    self::$base->set_date_return_format("d/m/Y");
    self::$base->set_date_arg_format("%Y-%m-%d");
    $res = self::$base->pgtests->test_returns_same_date('2015-05-04');
    $this->assertSame($res, '04/05/2015');

    self::$base->set_date_return_format("Y-m-d");
    self::$base->set_date_arg_format("%d/%m/%Y");
    $res = self::$base->pgtests->test_returns_same_date('07/11/2015');
    $this->assertSame($res, '2015-11-07');
  }

  public function testSameTimestamp() {
    self::$base->set_timestamp_return_format("d/m/Y H:i:s");
    self::$base->set_timestamp_arg_format("%Y-%m-%d %l:%M %p");
    $res = self::$base->pgtests->test_returns_same_timestamp('2015-05-04 02:25 PM');
    $this->assertSame($res, '04/05/2015 14:25:00');

    self::$base->set_timestamp_return_format("Y-m-d h:i:s A");
    self::$base->set_timestamp_arg_format("%d/%m/%Y %H:%M");
    $res = self::$base->pgtests->test_returns_same_timestamp('04/05/2015 14:25');
    $this->assertSame($res, '2015-05-04 02:25:00 PM');
  }

  public function testSameTime() {
    self::$base->set_time_return_format("H:i:s");
    self::$base->set_time_arg_format("%l:%M %p");
    $res = self::$base->pgtests->test_returns_same_time('02:25 PM');
    $this->assertSame($res, '14:25:00');

    self::$base->set_time_return_format("h:i:s A");
    self::$base->set_time_arg_format("%H:%M");
    $res = self::$base->pgtests->test_returns_same_time('14:25');
    $this->assertSame($res, '02:25:00 PM');
  }

  public function testIntegerArrayArg() {
    $in = array (1, 2, 3, 4);
    $out = self::$base->pgtests->test_integer_array_arg($in);
    $this->assertSame($out, $in);
  }

  public function testVarcharArrayArg() {
    $in = array ('a', 'b', 'c');
    $out = self::$base->pgtests->test_varchar_array_arg($in);
    $this->assertSame($out, $in);
  }

  /*
   * count 
   */
  public function testCount() {
    $in = array (1, 2, 3, 4);
    $out = self::$base->pgtests->test_integer_array_arg($in, PgProcedures2::count());
    $this->assertSame($out, count($in));
  }

  /*
   * order
   */
  public function testOrder() {
    $in = array (1, 3, 2, 4);
    $out = self::$base->pgtests->test_integer_array_arg($in, 
						      PgProcedures2::order('test_integer_array_arg', 'DESC'));
    $this->assertSame(array(4, 3, 2, 1), $out);

    $out = self::$base->pgtests->test_integer_array_arg($in, 
						      PgProcedures2::order('test_integer_array_arg', 'ASC'));
    $this->assertSame(array(1, 2, 3, 4), $out);
  }

  /*
   * limit
   */
  public function testLimit() {
    $in = array (1, 3, 2, 4);
    $out = self::$base->pgtests->test_integer_array_arg($in, 
						      PgProcedures2::limit(2));
    $this->assertSame(array(1, 3), $out);
  }

  /*
   * limit offset 
   */
  public function testLimitOffset() {
    $in = array (1, 3, 2, 4);
    $out = self::$base->pgtests->test_integer_array_arg($in, 
						      PgProcedures2::limit(2, 1));
    $this->assertSame(array(3, 2), $out);
  }

  /*
   * distinct / order
   */
  public function testDistinct() {
    $in = array (1, 3, 2, 3, 4);
    $out = self::$base->pgtests->test_integer_array_arg($in, 
						      PgProcedures2::distinct(),
						      PgProcedures2::order('test_integer_array_arg'));
    $this->assertSame(array(1, 2, 3, 4), $out);
  }

  /*
   * client encoding 
   */
  public function testGetClientEncoding() {
    $enc = self::$base->get_client_encoding();
    $this->assertEquals($enc, 'UTF8');
    
    $utf8string = self::$base->pgtests->test_returns_accented_string();
    $this->assertEquals($utf8string, 'héllo'); // Takes care this current file is utf-8 encoded
  }

  public function testSetClientEncoding() {
    $enc = self::$base->get_client_encoding();
    $this->assertEquals($enc, 'UTF8');
    self::$base->set_client_encoding('ISO-8859-1');
    $isoString = self::$base->pgtests->test_returns_accented_string();
    self::$base->set_client_encoding($enc);
    $this->assertEquals($isoString, utf8_decode('héllo')); // Takes care this current file is utf-8 encoded
  }
}
