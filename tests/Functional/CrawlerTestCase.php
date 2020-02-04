<?php

namespace Merlin\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Merlin\Output\OutputBase;

/**
 * A standardised way to access the crawler with HTML.
 */
class CrawlerTestCase extends TestCase {

  /**
   * Returns the path to the test file.
   */
  public function getHtmlFilePath() {
    return dirname(__FILE__) . '/../test.html';
  }

  /**
   * Return an instance of the crawler with loaded markup.
   *
   * @return Crawler
   */
  public function getCrawler() {
    $html = file_get_contents($this->getHtmlFilePath());
    return new Crawler($html, 'http://localhost/test');
  }

  /**
   * Get a mock OutputBase handler.
   */
  public function getOutput($methods = []) {
    $output = $this->getMockBuilder(OutputBase::class)
      ->disableOriginalConstructor();

    if (!empty($methods)) {
      if (!in_array('toString', $methods)) {
        $methods[] = 'toString';
      }
      $output->setMethods($methods);
    }

    return $output->getMock();
  }

}
