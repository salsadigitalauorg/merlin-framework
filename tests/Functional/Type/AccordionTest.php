<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\Accordion;
use Migrate\Exception\ElementNotFoundException;

class AccordionTest extends CrawlerTestCase {

  private $config = [
    'field' => 'accordion',
    'selector' => '#accordionGroup > div',
    'options' => [
      'title' => 'h3',
      'body' => 'div',
    ],
  ];

  /**
   * Test valid configuration.
   */
  public function testValidConfiguraiton() {
    $row = new \stdClass;
    $type = new Accordion(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $this->config
    );
    $type->process();
    $this->assertObjectHasAttribute('accordion', $row);
    $this->assertEquals(3, count($row->accordion));
  }

  /**
   * Test title not found.
   */
  public function testTitleNotFound() {
    $row = new \stdClass;
    $config = array_merge($this->config, ['options' => [
      'title' => 'h2',
    ]]);
    $type = new Accordion(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(ElementNotFoundException::class);
    $type->process();
  }

  /**
   * Test body not found.
   */
  public function testBodyNotFound() {
    $row = new \stdClass;
    $config = array_merge($this->config, ['options' => [
      'body' => 'section',
    ]]);
    $type = new Accordion(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(ElementNotFoundException::class);
    $type->process();
  }

}
