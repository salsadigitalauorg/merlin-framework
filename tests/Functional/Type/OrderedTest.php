<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\Ordered;
use Migrate\Exception\ElementNotFoundException;

class OrderedTest extends CrawlerTestCase {

  private $config = [
    'field' => 'ordered',
    'type' => 'ordered',
    'selector' => 'ul.with-links > li',
    'available_items' => [
      [
        'by' => [
          'attr' => 'class',
          'text' => 'content'
        ],
        'fields' => [
          [
            'field' => 'field_body',
            'type' => 'longtext',
          ]
        ],
      ]
    ],
  ];

  /**
   * Test valid configuration.
   */
  public function testValidConfiguration() {
    $row = new \stdClass;
    $type = new Ordered(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $this->config
    );
    $type->process();
    $this->assertObjectHasAttribute('ordered', $row);
    $this->assertEquals(9, count($row->ordered['children']));
  }

    /**
   * Test available_items missing.
   */
  public function testAvailableItemsMissing() {
    $row = new \stdClass;
    $config = [
      'field' => 'ordered',
      'type' => 'ordered',
      'selector' => 'ul.with-links > li'
    ];
    $type = new Ordered(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(\Exception::class);
    $type->process();
  }

}
