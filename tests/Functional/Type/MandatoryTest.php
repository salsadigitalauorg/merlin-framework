<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Exception\ElementNotFoundException;
use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Text;

class MandatoryTest extends CrawlerTestCase {

  /**
   * Ensures value is returned correctly if found and mandatory.
   */
  public function testMandatoryElementPresent() {

    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      [
        'field'    => 'title',
        'options'  => [
          'mandatory' => TRUE,
        ],
        'selector' => '.page-title',
      ]
    );

    $type->process();
    $this->assertTrue(\property_exists($row, 'title'));
    $this->assertFalse(\property_exists($row, 'mandatory_fail'));
    $this->assertEquals('This is the primary heading and there should only be one of these per page', $row->title);
  }

  /**
   * Checks row is flagged if not found but mandatory.
   */
  public function testMandatoryElementMissing() {
    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      [
        'field'    => 'title',
        'options'  => [
          'mandatory' => TRUE,
        ],
        'selector' => '//*/not-found',
      ]
    );

    try {
      $type->process();
    }
    catch (ElementNotFoundException $ex) {
      // We expect this...
    }

    $this->assertTrue(\property_exists($row, 'mandatory_fail'));
    $this->assertTrue($row->mandatory_fail);
  }



}
