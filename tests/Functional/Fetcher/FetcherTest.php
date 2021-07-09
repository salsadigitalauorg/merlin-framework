<?php

use Merlin\Output\Json;
use Merlin\Parser\Config;
use Merlin\Parser\WebConfig;
use Merlin\Tests\Functional\LocalPhpServerTestCase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Merlin\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawler;
use Symfony\Component\Yaml\Yaml;

class FetcherTest extends LocalPhpServerTestCase
{

  /**
   * Start up the local PHP server with the www dir required for these tests.
   * @throws \Exception
   */
  public static function setUpBeforeClass()
  {
    self::stopServer();
    self::startServer();
  }

  public function getInputMock()
  {

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
   *
   * @param \Merlin\Parser\WebConfig  $config
   * @param                           $property
   * @param                           $value
   */
  private function setConfigDataProperty(WebConfig $config, $property, $value)
  {
    $patch = function () use ($property, $value) {
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
   * @depends testPhpServerRunning
   *
   * @param mixed $expected
   * @param array $configData
   * @param bool  $assertExpected
   *
   * @return mixed
   */
  private function doRequest($expected, array $configData, $assertExpected=true)
  {

    $input = $this->getInputMock();
    $output = $this->getOutputMock();

    $config = new Config($input->getOption('config'));
    $config = $config->getConfig();

    foreach ($configData as $property => $data) {
      $this->setConfigDataProperty($config, $property, $data);
    }

    $io = new SymfonyStyle($input, $output);
    $json = new Json($io, $config);

    $use_js = $configData['fetch_options']['execute_js'] ?? false;

    // TODO: extend to run all non-js tests for both FetcherCurl and FetcherSpatieCrawler
    if ($use_js) {
      // We use Spatie to test JS.
      $fetcher = new \Merlin\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawler($io, $json, $config);
    } else {
      $fetcher = new \Merlin\Fetcher\Fetchers\Curl\FetcherCurl($io, $json, $config);
    }

    $urls = $configData['urls'] ?? [];
    foreach ($urls as $url) {
      $fetcher->addUrl($config->get('domain') . $url);
    }
    $fetcher->start();
    $fetcher->complete();

    $data = json_decode(json_encode($json->getData()), true);

    if ($assertExpected) {
      // Check for a sensible result
      // PHP puts the expected result in a h1, which is mapped in fetcher_config_test.yml
      $result = $data['phpunit_test'][0]['test_result'] ?? null;
      $this->assertEquals($expected, $result);
    }

    return $data;

  }


  /**
   * @group fetch_options
   * @group url_options
   */
  public function testPhpServerRunning()
  {
    $running = $this->isServerRunning();
    $this->assertNotFalse($running);
  }


  /**
   * @group   fetch_options
   * @depends testPhpServerRunning
   */
  public function testFollowRedirectsTrue()
  {

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
   * @group   fetch_options
   * @depends testPhpServerRunning
   */
  public function testFollowRedirectsFalse()
  {

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
   * @group   fetch_options
   * @depends testPhpServerRunning
   */
  public function testExecuteJavascriptTrue()
  {

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
   * @group   fetch_options
   * @depends testPhpServerRunning
   */
  public function testExecuteJavascriptFalse()
  {

    $config = [

      'urls' => [
        '/javascript.php',
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js' => false
      ]

    ];

    $this->doRequest("No Javascript", $config);

  }


  /**
   * @group   fetch_options
   * @depends testPhpServerRunning
   */
  public function testUserAgent()
  {
    $config = [

      'urls' => [
        '/user_agent.php',
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js' => false,
        'user_agent' => 'Merlin'
      ]

    ];

    $this->doRequest("Merlin", $config);

  }


  /**
   * @group   url_options
   * @depends testPhpServerRunning
   */
  public function testUrlQueryTrue()
  {

    $url = "/url_query.php";
    $query = "?query=bananas";
    $fullUrl = $url . $query;

    $config = [

      'urls' => [
        $fullUrl
      ],

      'url_options' => [
        'include_query' => true,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js' => false
      ]

    ];

    $data = $this->doRequest("bananas", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($fullUrl, $alias);

  }


  /**
   * @group   url_options
   * @depends testPhpServerRunning
   */
  public function testUrlQueryFalse()
  {

    $url = "/url_query.php";
    $query = "?query=bananas";
    $fullUrl = $url . $query;

    $config = [

      'urls' => [
        $fullUrl
      ],

      'url_options' => [
        'include_query' => false,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js' => false
      ]

    ];

    $data = $this->doRequest("bananas", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($url, $alias);

  }


  /**
   * @group   url_options
   * @depends testPhpServerRunning
   */
  public function testUrlFragmentTrue()
  {

    $url = "/url_fragment.php";
    $frag = "#i-am-a-fragment";
    $fullUrl = $url . $frag;

    $config = [

      'urls' => [
        $fullUrl,
      ],

      'url_options' => [
        'include_fragment' => true,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js' => true,
      ]

    ];

    $data = $this->doRequest("#i-am-a-fragment", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($fullUrl, $alias);

  }


  /**
   * @group   url_options
   * @depends testPhpServerRunning
   */
  public function testUrlFragmentFalse()
  {

    $frag = "#i-am-a-fragment";
    $url = "/url_fragment.php";
    $fullUrl = $url . $frag;

    $config = [

      'urls' => [
        $fullUrl,
      ],

      'url_options' => [
        'include_fragment' => false,
      ],

      'fetch_options' => [
        'cache_enabled' => false,
        'execute_js' => true,
      ]

    ];

    $data = $this->doRequest("#i-am-a-fragment", $config);

    // We need to check the alias for this too.
    $alias = $data['phpunit_test'][0]['alias'] ?? null;

    $this->assertEquals($url, $alias);

  }


  /**
   * @group   url_options
   * @depends testPhpServerRunning
   */
  public function testFindContentDuplicatesTrue()
  {

    $url = "/duplicate_content.php";
    $query1 = "?query=bananas";
    $query2 = "?query=bananas-are-yum";
    $query3 = "?query=bananas-are-yellow";
    $fullUrl1 = $url . $query1;
    $fullUrl2 = $url . $query2;
    $fullUrl3 = $url . $query3;

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
        'execute_js' => false
      ]

    ];

    $data = $this->doRequest("Duplicate Bananas", $config);

    // Check we got the right number of duplicate results
    $duplicateUrls = $data['phpunit_test-content-duplicates']['duplicates'][0]['urls'] ?? [];

    $this->assertNotEmpty($duplicateUrls);
    $this->assertCount(count($config['urls']), $duplicateUrls);

  }


  /**
   * @group   url_options
   * @depends testPhpServerRunning
   */
  public function testFindContentDuplicatesFalse()
  {

    $url = "/duplicate_content.php";
    $query1 = "?query=bananas";
    $query2 = "?query=bananas-are-yum";
    $query3 = "?query=bananas-are-yellow";
    $fullUrl1 = $url . $query1;
    $fullUrl2 = $url . $query2;
    $fullUrl3 = $url . $query3;

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
        'execute_js' => false
      ]

    ];

    $data = $this->doRequest("Duplicate Bananas", $config);

    // Check we got the right number of results and no duplicates
    $duplicateUrls = $data['url-content-duplicates']['duplicates'][0]['urls'] ?? [];
    $results = $data['phpunit_test'] ?? [];

    $this->assertEmpty($duplicateUrls);
    $this->assertCount(count($config['urls']), $results);

  }


  /**
   * @group    fetch_options
   * @depends_ testPhpServerRunning
   */
  public function test404()
  {

    $config = [
      'urls' => [
        '/404-test-1.php',
        '/404-test-2.php',
        '/404-test-3.php',
      ]
    ];

    $data = $this->doRequest(null, $config);
    $results = $data['phpunit_test-error-404']['urls'] ?? [];
    $this->assertCount(count($config['urls']), $results);

  }


  /**
   * @group    fetch_options
   * @depends_ testPhpServerRunning
   */
  public function testSubFetch()
  {

    $config = Yaml::parseFile(__DIR__ . DIRECTORY_SEPARATOR . 'subfetch_config_test.yml');
    $data = $this->doRequest("Subfetch Landing", $config);
    $items = $data['phpunit_test'][0]['items']['children'] ?? [];

    // There are 4 test urls to subfetch.
    $this->assertCount(4, $items);

    $idx = 1;
    foreach ($items as $item) {
      $d = $item['link']['data'];

      // Each item should have title, description price.
      $this->assertNotEmpty($d);
      $this->assertCount(3, $d);
      $this->assertNotEmpty($d['title']);
      $this->assertNotEmpty($d['description']);
      $this->assertNotEmpty($d['price']);

      // Check we get the right title data.
      $title = "Super Product Number {$idx}";
      $this->assertSame($title, $d['title']);

      // Check we get the right price data.
      $price = "{$idx}.00";
      $this->assertSame($price, $d['price']);

      $idx++;

    }
  }


  /**
   * @group    fetch_options
   * @depends_ testPhpServerRunning
   */
  public function testSubFetch404()
  {

    $config = Yaml::parseFile(__DIR__ . DIRECTORY_SEPARATOR . 'subfetch_config_test_404.yml');
    $data = $this->doRequest("Subfetch Landing", $config);
    $items = $data['item_subfetch-error-404'] ?? [];

    // There are 4 test 404 urls to subfetch.
    $this->assertCount(4, $items);

  }


  /**
   * @group    fetch_options
   * @depends_ testPhpServerRunning
   */
  public function testSubFetchJson()
  {

    $config = Yaml::parseFile(__DIR__ . DIRECTORY_SEPARATOR . 'subfetch_config_test_json.yml');
    $data = $this->doRequest("Subfetch Landing", $config);

    $items = $data['phpunit_test'][0]['items']['children'] ?? [];

    // There are 2 links to 2 different JSON test urls to subfetch.
    $this->assertCount(2, $items);

    // Check we fetch expected data.  First JSON has 3 items, second has 2.
    $j1 = $items[0]['link']['data'] ?? [];
    $j2 = $items[1]['link']['data'] ?? [];
    $this->assertCount(3, $j1);
    $this->assertCount(2, $j2);

    $titles1 = ['Brown eggs', 'Sweet fresh stawberry', 'Asparagus'];
    $titles2 = ['Plums', 'French fries'];

    foreach ($titles1 as $idx => $title) {
      $this->assertSame($title, $j1[$idx]['title']);
    }

    foreach ($titles2 as $idx => $title) {
      $this->assertSame($title, $j2[$idx]['title']);
    }

  }




  //TODO: Consider adding a cache test similar to the one defined in CrawlerTest


}
