<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\Text;

class TextTest extends CrawlerTestCase {

  /**
   * Ensure that links in the DOM can be found.
   */
  public function testSingleValue() {
    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      ['field' => 'title', 'selector' => '.page-title']
    );

    $type->process();
    $this->assertTrue(\property_exists($row, 'title'));
    $this->assertEquals('This is the primary heading and there should only be one of these per page', $row->title);
  }

  /**
   * Ensure the correct exception is thrown.
   */
  public function testMultipleValues() {
    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      ['field' => 'title', 'selector' => '//*/h1']
    );
    $type->process();
    $this->assertEquals(5, count($row->title));
  }
}
