<?php

use Migrate\Output\Json;
use Migrate\Parser\Config;
use Migrate\Parser\WebConfig;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class FetcherTest extends TestCase
{


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
   * Sets a config data attribute (normally protected)
   * @param \Migrate\Parser\WebConfig $config
   * @param                           $property
   * @param                           $value
   */
  private function setConfigDataProperty(WebConfig $config, $property, $value) {
    $modify = function() use ($property, $value) {
      $this->data[$property] = $value;

      // Update the url count in case changed
      $this->totals['urls'] = count($this->data['urls']);
      $this->totals['urls_from_config'] = count($this->data['urls']);
    };
    $modify->call($config);
  }





  private function doRedirect($redirect, $expected) {

    $input = $this->getInputMock();
    $output = $this->getOutputMock();

    $urls = [
      '/redirect_src.php',
    ];

    $fetchOptions = [
      'cache_enabled' => false,
      'follow_redirects' => $redirect,
    ];

    $config = new Config($input->getOption('config'));
    $config = $config->getConfig();

    $this->setConfigDataProperty($config, 'urls', $urls);
    $this->setConfigDataProperty($config, 'fetch_options', $fetchOptions);

    $io = new SymfonyStyle($input, $output);
    $json = new Json($io, $config);

    // Instead of creating a fetcher, we could alternatively use:
//    $c = new \Migrate\Command\GenerateCommand();
//    $f = function() use ($config, $json, $io) {
//      $this->config = $config;
//      $this->runWeb($json, $io);
//    };
//    $f->call($c);

    $fetcher = new \Migrate\Fetcher\Fetchers\SpatieCrawler\FetcherSpatieCrawler($io, $json, $config);
    foreach($urls as $url) {
      $fetcher->addUrl($config->get('domain') . $url);
    }
    $fetcher->start();

    $data = json_decode(json_encode($json->getData()), true); // yeep.
    $result = $data['phpunit_test'][0]['test_result'] ?? null;

    $this->assertEquals($expected, $result);

  }


  /**
   * @group fetcher
   */
  public function testFollowRedirects() {
    $this->doRedirect(true, "Redirect Successful");
  }


  /**
   * @group fetcher
   */
  public function testNoFollowRedirects() {
    $this->doRedirect(false, null);
  }





}