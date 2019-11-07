<?php

namespace Migrate\Tests\Crawler;

use Migrate\Fetcher\Cache;
use Migrate\Tests\Functional\LocalPhpServerTestCase;
use Migrate\Command\CrawlCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;


class CrawlerTest extends LocalPhpServerTestCase
{

  /** @var \Symfony\Component\Console\Tester\CommandTester  */
  private $cmdTester;

  /** @var string */
  private $outputDir;


  /**testGroupsSplitInFiles
   * {@inheritdoc}
   *
   * Start up the local PHP server with the www dir required for theses tests.
   * @throws \Exception
   */
  public static function setUpBeforeClass() {
    self::stopServer();
    self::startServer(__DIR__ . "/../www/crawler_tests");
  }


  /**
   * {@inheritdoc}
   *
   * Setup our command to test.  NB: setUp() is called before each test, not
   * once like the static setUpBeforeClass method.
   * @throws \Exception
   */
  protected function setUp() {

    $this->clearOutputDir();

    $application = new Application();
    $application->add(new CrawlCommand());
    $command = $application->find('crawl');
    $this->cmdTester = new CommandTester($command);

    $dstDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'merlin_test_output';
    if (!is_dir($dstDir)) {
      mkdir($dstDir, 0777, true);
    }

    if (is_writable($dstDir) && is_readable($dstDir)) {
      $this->outputDir = $dstDir;
    } else {
      throw new \Exception("Cannot write to temporary test dir: {$dstDir}");
    }
  }


  /**
   * {@inheritdoc}
   *
   * Removes expected output files to make sure no false-positives in subsequent tests.
   * Called after every test.
   */
  public function tearDown()
  {
    $this->clearOutputDir();
  }


  /**
   * Removes the yaml result files from the test output dir
   */
  private function clearOutputDir() {
    foreach (glob($this->outputDir . DIRECTORY_SEPARATOR . '*.yml') as $file) {
      unlink($file);
    }
  }


  /**
   * @group crawler_options
   */
  public function testPhpServerRunning() {
    $running = $this->isServerRunning();
    $this->assertNotFalse($running);
  }


  /**
   * Test all pages.
   * @group crawler_options
   * @depends testPhpServerRunning
   */
  public function testAllPages() {

    $config = __DIR__ . DIRECTORY_SEPARATOR . 'all_pages.yml';
    $this->cmdTester->execute(
      [
        '-c' => $config,
        '-o' => $this->outputDir
      ]
    );

    $crawled = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR . 'crawled-urls-crawler_test_default.yml');
    $crawled = Yaml::parse($crawled);

    $ymls = glob($this->outputDir. DIRECTORY_SEPARATOR . '*.yml');

    $this->assertEquals(1, count($ymls));
    $this->assertEquals(11, count($crawled['urls']));


  }//end testAllPages()



  /**
   * Ensure groups are pulled out in their own files.
   * @group crawler_options
   * @depends testPhpServerRunning
   */
  public function testGroupsSplitInFiles()
  {

    $config = __DIR__ . DIRECTORY_SEPARATOR . 'groups.yml';
    $this->cmdTester->execute(
      [
        '-c' => $config,
        '-o' => $this->outputDir
      ]
    );

    $ymls = glob($this->outputDir . DIRECTORY_SEPARATOR . '*.yml');

    $group1 = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR . 'crawled-urls-crawler_test_group1.yml');
    $group1 = Yaml::parse($group1);
    $default = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR .'crawled-urls-crawler_test_default.yml');
    $default = Yaml::parse($default);

    $this->assertEquals(2, count($ymls));
    $this->assertEquals(1, count($group1['urls']));
    $this->assertEquals(10, count($default['urls']));

    $expected_default = [
      '/',
      '/search.php',
      '/home.html',
      '/index.php?p=1',
      '/index.php?p=2',
      '/index.php?p=3',
      '/duplicate_links.php',
      '/search.php?query=candy-cat',
      '/search.php?query=pedro-pony',
      '/search.php?query=peppa-pig'
    ];

    $expected_group1 = ['/about.html'];

    foreach ($expected_default as $path) {
      $this->assertContains($path, $default['urls']);
    }

    foreach ($expected_group1 as $path) {
      $this->assertContains($path, $group1['urls']);
    }


  }//end testGroupsSplitInFiles()



  /**
   * Test caching crawler caching
   * @group crawler_options
   * @depends testPhpServerRunning
   */
  public function testCache() {

    // First test to fetch and cache the content
    $config = __DIR__ . DIRECTORY_SEPARATOR . 'cache_test.yml';
    $this->cmdTester->execute(
      [
        '-c' => $config,
        '-o' => $this->outputDir
      ]
    );

    $crawled = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR . 'crawled-urls-crawler_test_default.yml');
    $crawled = Yaml::parse($crawled);

    $ymls = glob($this->outputDir. DIRECTORY_SEPARATOR . '*.yml');

    $this->assertEquals(1, count($ymls));
    $this->assertEquals(11, count($crawled['urls']));


    // Remove our output files, stop local server and test again.  The files should magically come from the cache.
    $this->tearDown();
    self::stopServer();


    // Second test from cache
    $this->cmdTester->execute(
      [
        '-c' => $config,
        '-o' => $this->outputDir
      ]
    );

    $crawled = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR . 'crawled-urls-crawler_test_default.yml');
    $crawled = Yaml::parse($crawled);

    $ymls = glob($this->outputDir. DIRECTORY_SEPARATOR . '*.yml');

    $this->assertEquals(1, count($ymls));
    $this->assertEquals(11, count($crawled['urls']));


    // Remove cached files
    $ymlDomain = 'http://localhost:8000';
    $cache = new Cache($ymlDomain);
    $cache->clearCache(true);


    // Restart local server in case this is not the last test
    self::startServer(__DIR__ . "/../www/crawler_tests");


  }//end testCache()


  /**
   * Test caching crawler caching
   * @group crawler_options
   * @depends testPhpServerRunning
   */
  public function testDuplicates() {

    $config = __DIR__ . DIRECTORY_SEPARATOR . 'duplicates_test.yml';
    $this->cmdTester->execute(
      [
        '-c' => $config,
        '-o' => $this->outputDir
      ]
    );

    $crawled = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR . 'crawled-urls-crawler_test_default.yml');
    $crawled = Yaml::parse($crawled);
    $crawledDupes = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR .'crawled-urls-crawler_test_duplicates.yml');
    $crawledDupes = Yaml::parse($crawledDupes);

    $ymls = glob($this->outputDir. DIRECTORY_SEPARATOR . '*.yml');
    $this->assertEquals(2, count($ymls));

    $this->assertArrayHasKey('urls', $crawled);
    $this->assertEquals(8, count($crawled['urls']));

    $this->assertArrayHasKey('duplicates', $crawledDupes);
    $this->assertIsArray($crawledDupes['duplicates']);
    $this->assertGreaterThan(0, count($crawledDupes['duplicates']));

    $duplicates = $crawledDupes['duplicates'][0];
    $this->assertArrayHasKey('hash', $duplicates);
    $this->assertArrayHasKey('urls', $duplicates);
    $this->assertEquals(4, count($duplicates['urls']));

  }// end testDuplicates()

  /**
   * Test that provided URLs are crawled
   * @group crawler_options
   * @depends testPhpServerRunning
   */
  public function testStartUrls() {

    $config = __DIR__ . DIRECTORY_SEPARATOR . 'urls_test.yml';
    $this->cmdTester->execute(
      [
        '-c' => $config,
        '-o' => $this->outputDir
      ]
    );

    $crawled = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR . 'crawled-urls-crawler_test_default.yml');
    $crawled = Yaml::parse($crawled);

    $expected_default = [
      '/',
      '/orphan.html',
      '/orphan-child.html',
      '/search.php',
      '/home.html',
      '/about.html',
      '/index.php?p=1',
      '/index.php?p=2',
      '/index.php?p=3',
      '/duplicate_links.php'
    ];

    $this->assertArrayHasKey('urls', $crawled);
    $this->assertEquals(10, count($crawled['urls']));

    foreach ($expected_default as $path) {
      $this->assertContains($path, $crawled['urls']);
    }

  }// end testStartUrls()

  /**
   * Test that starting URL is crawled when provided as a string
   * @group crawler_options
   * @depends testPhpServerRunning
   */
  public function testStartUrlsString() {

    $config = __DIR__ . DIRECTORY_SEPARATOR . 'urls_string_test.yml';
    $this->cmdTester->execute(
      [
        '-c' => $config,
        '-o' => $this->outputDir
      ]
    );

    $crawled = file_get_contents($this->outputDir . DIRECTORY_SEPARATOR . 'crawled-urls-crawler_test_default.yml');
    $crawled = Yaml::parse($crawled);

    $expected_default = [
      '/orphan.html',
      '/orphan-child.html'
    ];

    $this->assertArrayHasKey('urls', $crawled);
    $this->assertEquals(2, count($crawled['urls']));

    foreach ($expected_default as $path) {
      $this->assertContains($path, $crawled['urls']);
    }

  }// end testStartUrlsString()


}
