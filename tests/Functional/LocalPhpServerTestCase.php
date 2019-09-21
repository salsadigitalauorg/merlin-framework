<?php

/**
 * This base test case class spins up a local PHP server so that tests
 * that need a server (e.g. fetching tests) or server-side processing
 * checks can be performed.
 */

namespace Migrate\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class LocalPhpServerTestCase extends TestCase
{

  /** @var Process */
  private static $server;

  public static function startServer($wwwRoot = null)
  {

    $www = __DIR__ . "/../www";

    if (!empty($wwwRoot)) {
      $www = $wwwRoot;
    }

    if (!is_dir(realpath($www))) {
      throw new \Exception("Test www directory does not exist: {$www}");
    }

    $cmd = [
      'php',
      '-S',
      'localhost:8000',
      '-t',
      $www
    ];

    self::$server = new Process($cmd);
    self::$server->start();
    sleep(3);
  }

  public static function stopServer() {
    if (self::$server instanceof Process) {
      self::$server->stop();
    }
  }


  public static function tearDownAfterClass()
  {
    self::stopServer();
  }

  /**
   * You can use this in a test function, e.g. for @group tests, create a testPhpServerRunning()
   * function that calls this.  You can then mark your other functions with @depends testPhpServerRunning
   * to ensure that the local server is up.  See e.g. FetcherTest.php for how to set things up.
   *
   * @return bool
   */
  public function isServerRunning() {
    $errno = null;
    $errstr = null;
    $fp = fsockopen("tcp://localhost", 8000, $errno, $errstr);
    return $fp!==false;
  }

}