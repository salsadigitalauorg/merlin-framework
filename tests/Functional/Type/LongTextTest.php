<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\LongText;

class LongTextTest extends CrawlerTestCase {

  /**
   * Get a valid configuration object.
   */
  public function validConfig() {
    return [
      [
        [
          'field' => 'field_body',
          'selector' => '.main-content',
        ],
      ],
    ];
  }

  /**
   * Ensure that links in the DOM can be found.
   *
   * @dataProvider validConfig
   */
  public function testValueExtraction($config) {
    $row = new \stdClass();
    $type = new LongText(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $type->process();

    $this->assertTrue(\property_exists($row, $config['field']));
    $this->assertArrayHasKey('format', $row->{$config['field']}[0]);
    $this->assertArrayHasKey('value', $row->{$config['field']}[0]);
  }
}
