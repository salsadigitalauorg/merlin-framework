<?php

namespace Merlin\Tests\Functional\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Merlin\Parser\WebConfig;

/**
 * Ensure that URLs are correctly migrated from both files and config using the urls and urls_file properties.
 */
class UrlsFileTest extends TestCase
{

  /**
   * Set up the tests.
   */
  public function setUp()
  {
    $this->source = __DIR__.'/config_base.yml';
    $this->source_urls_only = __DIR__.'/config_base_urls_only.yml';
    $this->source_urls_file_only = __DIR__.'/config_base_urls_file_only.yml';
    $this->source_no_urls = __DIR__.'/config_base_no_urls.yml';
    $this->source_missing_urls_file = __DIR__.'/config_base_missing_urls_file.yml';
  }

  /**
   * Ensure that URLs are correctly extracted from each source and that there are the
   * correct number of each by total.
   */
  public function testUrls()
  {
    $sources = ['source', 'source_urls_only', 'source_urls_file_only'];
    foreach ($sources as $source) {
      $base = new WebConfig($this->$source);
      $totals = $base->get('totals');
      $this->assertEquals($totals['urls_from_file'] + $totals['urls_from_config'], $totals['urls']);
    }
  }

  /**
   * Ensure that an error is thrown when a urls_file is not found
   */
  public function testMissingUrlsFile()
  {
    $this->expectException(\Exception::class);
    $base = new WebConfig($this->source_missing_urls_file);
  }

  /**
   * Ensure an error is thrown if neither urls nor urls_file property is specified
   */
  public function testInvalidUrls()
  {
    $this->expectException(\Exception::class);
    $base = $this->getMockForAbstractClass(WebConfig::class, [$this->source_no_urls]);
  }

}
