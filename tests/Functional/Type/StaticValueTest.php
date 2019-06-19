<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\StaticValue;

class StaticValueTest extends CrawlerTestCase {

  /**
   * Get a valid configuration object.
   */
  public function validConfig()
  {
    return [
      [
        [
          'field' => 'site',
          'options' => ['value' => '4'],
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
    $type = new StaticValue(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $type->process();
    $this->assertTrue(\property_exists($row, $config['field']));
    $this->assertEquals($config['options']['value'], $row->{$config['field']});
  }

  /**
   * Ensure the correct exception is thrown.
   */
  public function testInvalidConfigError() {
    $row = new \stdClass();
    $type = new StaticValue(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      ['field' => 'site', 'options' => []]
    );
    $this->expectException(\Exception::class);
    $type->process();
  }
}
