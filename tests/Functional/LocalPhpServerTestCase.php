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

  /**
   * Starts the local PHP server
   *
   * @param null $wwwRoot
   *
   * @param int  $port
   *
   * @throws \Exception
   */
  public static function startServer($wwwRoot = null, $port=8000)
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
      'localhost:' . $port,
      '-t',
      $www
    ];

    self::$server = new Process($cmd);
    self::$server->start();
    sleep(5);
  }


  /**
   * Stops the local PHP server
   */
  public static function stopServer() {
    if (self::$server instanceof Process) {
      self::$server->stop();
      sleep(5);
    }
  }


  /**
   * {@inheritdoc}
   */
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