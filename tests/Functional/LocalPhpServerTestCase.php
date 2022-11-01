<?php

/**
 * This base test case class spins up a local PHP server so that tests
 * that need a server (e.g. fetching tests) or server-side processing
 * checks can be performed.
 */

namespace Merlin\Tests\Functional;

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

    // Note: IP address used below (not 'localhost:') so ipv4 only resolve works in cURL for Fetcher tests.
    $cmd = [
      'php',
      '-S',
      '127.0.0.1:' . $port,
      '-t',
      $www
    ];

    self::$server = new Process($cmd);
    self::$server->setTimeout(15);
    self::$server->start();

    self::$server->waitUntil(function ($type, $output) {
      return strpos($output, "started") !== FALSE;
    });
  }


  /**
   * Stops the local PHP server
   */
  public static function stopServer() {
    if (self::$server instanceof Process) {
      self::$server->stop();
      sleep(10);
    }
  }


  /**
   * {@inheritdoc}
   */
  public static function tearDownAfterClass(): void
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
