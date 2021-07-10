<?php

namespace Merlin\Tests\Functional\Type;

use PHPUnit\Framework\TestCase;
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
    $this->source_multiple_urls_files = __DIR__.'/config_base_multiple_urls_files.yml';
    $this->source_no_urls = __DIR__.'/config_base_no_urls.yml';
    $this->source_missing_urls_file = __DIR__.'/config_base_missing_urls_file.yml';
  }

  /**
   * Ensure that URLs are correctly extracted from each source and that there are the
   * correct number of each by total.
   */
  public function testUrls()
  {
    $sources = ['source', 'source_urls_only', 'source_urls_file_only', 'source_multiple_urls_files'];
    foreach ($sources as $source) {
      $base = new WebConfig($this->$source);

      // Note: totals isn't part of the data[] array, so get() returns false.
      // $totals = $base->get('totals');
      $t = function() {return $this->totals;};
      $totals = $t->call($base);

      $urls_from_file = $totals['urls_from_file'] ?? 0;
      $urls_from_config = $totals['urls_from_config'] ?? 0;

      $this->assertEquals($urls_from_file + $urls_from_config, $totals['urls']);
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
