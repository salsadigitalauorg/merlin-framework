<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Text;

/**
 * Ensure that the tool can locate a H1.
 */
class TestSelectH1 extends CrawlerTestCase
{

  /**
   * Ensure that links in the DOM can be found.
   */
  public function testSelectH1()
  {
    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      ['field' => 'title', 'selector' => 'h1']
    );

    $type->process();
    $this->assertTrue(\property_exists($row, 'title'));
    $this->assertEquals('This is the primary heading and there should only be one of these per page', $row->title[0]);
  }

}
