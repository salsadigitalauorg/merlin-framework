<?php

namespace Migrate\Tests\Crawler;

use PHPUnit\Framework\TestCase;
use Migrate\Command\CrawlCommand;
use Symfony\Component\Console\Input\InputInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Yaml\Yaml;

class CrawlerTest extends TestCase
{

  public function getInputMock()
  {
    $input = $this
      ->getMockBuilder(InputInterface::class)
      ->disableOriginalClone()
      ->getMock();

    $input->expects($this->at(0))
      ->method('getOption')
      ->with($this->equalTo('config'))
      ->willReturn(__DIR__ . '/allPages.yml');

    $input->expects($this->at(1))
      ->method('getOption')
      ->with('output')
      ->willReturn('/tmp');

    return $input;

  }//end getInputMock()


  public function getOutputMock()
  {

    $output = $this->getMockBuilder(OutputInterface::class)
      ->disableOriginalClone()
      ->getMock();

    $formatter = $this->getMockBuilder(OutputFormatterInterface::class)->getMock();
    $output->expects($this->any())->method('getFormatter')->willReturn($formatter);

    return $output;

  }//end getOutputMock


  public function getMethod($name)
  {
    $class = new ReflectionClass(CrawlCommand::class);
    $method = $class->getMethod($name);
    $method->setAccessible(true);

    return $method;

  }//end getMethod()


  /**
   * Test all pages.
   */
  public function testAllPages()
  {
    $input = $this->getInputMock();
    $output = $this->getOutputMock();

    $crawl = new CrawlCommand();
    $this->getMethod('execute')->invokeArgs($crawl, [$input, $output]);

    $crawled = file_get_contents('/tmp/crawled-urls-default.yml');
    $crawled = Yaml::parse($crawled);

    $this->assertEquals(6, count($crawled['default']));

  }//end testAllPages()

}
