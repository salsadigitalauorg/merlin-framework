<?php

use Migrate\Output\Json;
use Migrate\Parser\Config;
use Migrate\Parser\WebConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Migrate\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawler;

class FetcherTest extends TestCase
{

  /** @var Process */
  private static $server;

  public static function setUpBeforeClass()
  {
    $www = __DIR__ . "/../../www";
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

  public static function tearDownAfterClass()
  {
    self::$server->stop();
  }

  public function getInputMock() {

    /** @var InputInterface $input */
    $input = $this
      ->getMockBuilder(InputInterface::class)
      ->disableOriginalClone()
      ->getMock();

    $input->expects($this->at(0))
      ->method('getOption')
      ->with($this->equalTo('config'))
      ->willReturn(__DIR__ . DIRECTORY_SEPARATOR . 'fetcher_config_test.yml');

    return $input;
  }


  public function getOutputMock()
  {
    $output = $this->getMockBuilder(OutputInterface::class)
      ->disableOriginalClone()
      ->getMock();
    $formatter = $this->getMockBuilder(OutputFormatterInterface::class)->getMock();
    $output->expects($this->any())->method('getFormatter')->willReturn($formatter);

    /** @var $output OutputInterface */
    return $output;
  }//end getOutputMock


  /**
   * Sets a config data attribute (normally protected property)
   * @param \Migrate\Parser\WebConfig $config
   * @param                           $property
   * @param                           $value
   */
  private function setConfigDataProperty(WebConfig $config, $property, $value) {
    $patch = function() use ($property, $value) {
      $this->data[$property] = $value;

      // Update the url count in case changed
      $this->totals['urls'] = count($this->data['urls']);
      $this->totals['urls_from_config'] = count($this->data['urls']);
    };
    $patch->call($config);
  }


  /**
   * Perform a request via a fetcher.
   *
   * @param $expected
   * @param $configData
   *
   * @return mixed
   */
  private function doRequest($expected, array $configData) {

    $input = $this->getInputMock();
    $output = $this->getOutputMock();

    $config = new Config($input->getOption('config'));
    $config = $config->getConfig();

    foreach ($configData as $property => $data) {
      $this->setConfigDataProperty($config, $property, $data);
    }

    $io = new SymfonyStyle($input, $output);
    $json = new Json($io, $config);

    // Instead of creating a fetcher, could potentially use the command class:
//    $c = new \Migrate\Command\GenerateCommand();
//    $f = function() use ($config, $json, $io) {
//      $this->config = $config;
//      $this->runWeb($json, $io);
//    };
//    $f->call($c);

    $fetcher = new \Migrate\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawler($io, $json, $config);
    $urls = $configData['urls'] ?? [];
    foreach($urls as $url) {
      $fetcher->addUrl($config->get('domain') . $url);
    }
    $fetcher->start();
    $fetcher->complete();

    // Check for a sensible result
    // PHP puts the expected result in a h1, which is mapped in fetcher_config_test.yml
    $data = json_decode(json_encode($json->getData()), true);
    $result = $data['phpunit_test'][0]['test_result'] ?? null;

    $this->assertEquals($expected, $result);

    return $data;

  }


  /**
   * @group fetch_options
   * @group url_options
   */
  public function testPhpServerRunning() {
    $errno = null;
    $errstr = null;
    $fp = fsockopen("tcp://localhost", 8000, $errno, $errstr);
    $this->assertNotFalse($fp);
  }


  /**
   * @group fetch_options
   * @depends testPhpServerRunning
   */
  public function testFollowRedirectsTrue() {

    $config = [

      'urls' => [
        '/redirect_src.php',
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'follow_redirects' => true,
      ]

    ];

    $this->doRequest("Redirect Successful", $config);
  }


  /**
   * @group fetch_options
   * @depends testPhpServerRunning
   */
  public function testFollowRedirectsFalse() {

    $config = [

      'urls' => [
        '/redirect_src.php',
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'follow_redirects' => false,
      ]

    ];

    $this->doRequest(null, $config);
  }


  /**
   * @group fetch_options
   * @depends testPhpServerRunning
   */
  public function testExecuteJavascriptTrue() {

    $config = [

      'urls' => [
        '/javascript.php',
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js' => true
      ]

    ];

    $this->doRequest("Hello from window.onLoad", $config);

  }

  /**
   * @group fetch_options
   * @depends testPhpServerRunning
   */
  public function testExecuteJavascriptFalse() {

    $config = [

      'urls' => [
        '/javascript.php',
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => false
      ]

    ];

    $this->doRequest("No Javascript", $config);

  }


  /**
   * @group fetch_options
   * @depends testPhpServerRunning
   */
  public function testUserAgent() {
    $config = [

      'urls' => [
        '/user_agent.php',
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => false,
        'user_agent'    => 'Merlin'
      ]

    ];

    $this->doRequest("Merlin", $config);

  }



  /**
   * @group url_options
   * @depends testPhpServerRunning
   */
  public function testUrlQueryTrue() {

    $url = "/url_query.php";
    $query = "?query=bananas";
    $fullUrl = $url.$query;

    $config = [

      'urls' => [
       $fullUrl
      ],

      'url_options' => [
        'include_query' => true,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => false
      ]

    ];

    $data = $this->doRequest("bananas", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($fullUrl, $alias);

  }


  /**
   * @group url_options
   * @depends testPhpServerRunning
   */
  public function testUrlQueryFalse() {

    $url = "/url_query.php";
    $query = "?query=bananas";
    $fullUrl = $url.$query;

    $config = [

      'urls' => [
        $fullUrl
      ],

      'url_options' => [
        'include_query' => false,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => false
      ]

    ];

    $data = $this->doRequest("bananas", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($url, $alias);

  }


  /**
   * @group url_options
   * @depends testPhpServerRunning
   */
  public function testUrlFragmentTrue() {

    $url = "/url_fragment.php";
    $frag = "#i-am-a-fragment";
    $fullUrl = $url.$frag;

    $config = [

      'urls' => [
        $fullUrl,
      ],

      'url_options' => [
        'include_fragment' => true,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => true,
      ]

    ];

    $data = $this->doRequest("#i-am-a-fragment", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($fullUrl, $alias);

  }


  /**
   * @group url_options
   * @depends testPhpServerRunning
   */
  public function testUrlFragmentFalse() {

    $frag = "#i-am-a-fragment";
    $url = "/url_fragment.php";
    $fullUrl = $url.$frag;

    $config = [

      'urls' => [
        $fullUrl,
      ],

      'url_options' => [
        'include_fragment' => false,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => true,
      ]

    ];

    $data = $this->doRequest("#i-am-a-fragment", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($url, $alias);

  }



  /**
   * @group url_options
   * @depends testPhpServerRunning
   */
  public function testFindContentDuplicatesTrue() {

    $url = "/duplicate_content.php";
    $query1 = "?query=bananas";
    $query2 = "?query=bananas-are-yum";
    $query3 = "?query=bananas-are-yellow";
    $fullUrl1 = $url.$query1;
    $fullUrl2 = $url.$query2;
    $fullUrl3 = $url.$query3;

    $config = [

      'urls' => [
        $fullUrl1,
        $fullUrl2,
        $fullUrl3,
      ],

      'url_options' => [
        'include_query' => true,
        'find_content_duplicates' => true,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => false
      ]

    ];

    $data = $this->doRequest("Duplicate Bananas", $config);

    // Check we got the right number of duplicate results
    $duplicateUrls = $data['url-content-duplicates']['duplicates'][0]['urls'] ?? [];

    $this->assertNotEmpty($duplicateUrls);
    $this->assertCount(count($config['urls']), $duplicateUrls);

  }


  /**
   * @group url_options
   * @depends testPhpServerRunning
   */
  public function testFindContentDuplicatesFalse() {

    $url = "/duplicate_content.php";
    $query1 = "?query=bananas";
    $query2 = "?query=bananas-are-yum";
    $query3 = "?query=bananas-are-yellow";
    $fullUrl1 = $url.$query1;
    $fullUrl2 = $url.$query2;
    $fullUrl3 = $url.$query3;

    $config = [

      'urls' => [
        $fullUrl1,
        $fullUrl2,
        $fullUrl3,
      ],

      'url_options' => [
        'include_query' => true,
        'find_content_duplicates' => false,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js'    => false
      ]

    ];

    $data = $this->doRequest("Duplicate Bananas", $config);

    // Check we got the right number of results and no duplicates
    $duplicateUrls = $data['url-content-duplicates']['duplicates'][0]['urls'] ?? [];
    $results = $data['phpunit_test'] ?? [];

    $this->assertEmpty($duplicateUrls);
    $this->assertCount(count($config['urls']), $results);

  }

}
