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

  public function getInputMock($config = '/allPages.yml')
  {
    $input = $this
      ->getMockBuilder(InputInterface::class)
      ->disableOriginalClone()
      ->getMock();

    $input->expects($this->at(0))
      ->method('getOption')
      ->with($this->equalTo('config'))
      ->willReturn(__DIR__ . $config);

    $input->expects($this->at(1))
      ->method('getOption')
      ->with('output')
      ->willReturn(\sys_get_temp_dir());

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
   * {@inheritdoc}
   *
   * Removes expected outupt files to make sure no false-positives in subsequent tests.
   */
  public function tearDown()
  {
    foreach (glob(sys_get_temp_dir() . '/*.yml') as $file) {
      unlink($file);
    }
  }//end tearDown()


  /**
   * Test all pages.
   */
  public function testAllPages()
  {
    $input = $this->getInputMock();
    $output = $this->getOutputMock();

    $crawl = new CrawlCommand();
    $this->getMethod('execute')->invokeArgs($crawl, [$input, $output]);

    $crawled = file_get_contents(sys_get_temp_dir() . '/crawled-urls-default.yml');
    $crawled = Yaml::parse($crawled);

    $ymls = glob(sys_get_temp_dir() . '/*.yml');

    $this->assertEquals(1, count($ymls));
    $this->assertEquals(9, count($crawled['default']));

  }//end testAllPages()


  /**
   * Ensure groups are pulled out in their own files.
   */
  public function testGroupsSplitInFiles()
  {
    $input = $this->getInputMock('/groups.yml');
    $output = $this->getOutputMock();

    $crawl = new CrawlCommand();
    $this->getMethod('execute')->invokeArgs($crawl, [$input, $output]);

    $ymls = glob(sys_get_temp_dir() . '/*.yml');

    $group1 = file_get_contents(sys_get_temp_dir() . '/crawled-urls-group1.yml');
    $group1 = Yaml::parse($group1);
    $default = file_get_contents(sys_get_temp_dir() . '/crawled-urls-default.yml');
    $default = Yaml::parse($default);

    $this->assertEquals(2, count($ymls));
    $this->assertEquals(1, count($group1['group1']));
    $this->assertEquals(8, count($default['default']));

    $expected_default = [
      '/',
      '/search.php',
      '/home.html',
      '/index.html',
      '/test.html',
      '/test.php?p=1',
      '/test.php?p=2',
      '/test.php?p=3',
    ];

    $expected_group1 = ['/about.html'];

    foreach ($expected_default as $path) {
      $this->assertContains($path, $default['default']);
    }

    foreach ($expected_group1 as $path) {
      $this->assertContains($path, $group1['group1']);
    }
  }

}
