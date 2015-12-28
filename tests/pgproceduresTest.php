<?php
require_once '../pgprocedures.php';
require_once '../config.inc.php';

class pgproceduresTest extends PHPUnit_Framework_TestCase {
  
  private $base;
  private $pgHost;
  private $pgUser;
  private $pgPass;
  private $pgDatabase;

  public function testConfig() {
    global $pg_host, $pg_user, $pg_pass, $pg_database;
    $this->pgHost = $pg_host;
    $this->pgUser = $pg_user;
    $this->pgPass = $pg_pass;
    $this->pgDatabase = $pg_database;
    $this->assertNotNull($this->pgHost);
    $this->assertNotNull($this->pgUser);
    $this->assertNotNull($this->pgPass);
    $this->assertNotNull($this->pgDatabase);
  }
  
  /**
   * @depends testConfig 
   */
  public function testConstruct() {
    $this->testConfig();
    $this->base = new PgProcedures2 ($this->pgHost, $this->pgUser, $this->pgPass, $this->pgDatabase);
    $this->assertNotNull($this->base);
  }

  
}
