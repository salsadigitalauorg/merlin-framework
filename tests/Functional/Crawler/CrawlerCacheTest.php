<?php


use Migrate\Tests\Functional\LocalPhpServerTestCase;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlerCacheTest extends LocalPhpServerTestCase
{


  public function getInputMock($yamlConfgPath) {

    /** @var InputInterface $input */
    $input = $this
      ->getMockBuilder(InputInterface::class)
      ->disableOriginalClone()
      ->getMock();

    $input->expects($this->at(0))
      ->method('getOption')
      ->with($this->equalTo('config'))
      ->willReturn(__DIR__ . DIRECTORY_SEPARATOR . 'crawler_cache_test.yml');

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
   * @group crawler_options
   */
  public function testPhpServerRunning() {
    $running = $this->isServerRunning();
    $this->assertNotFalse($running);
  }




}