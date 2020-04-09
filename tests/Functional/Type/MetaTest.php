<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Meta;

class MetaTest extends CrawlerTestCase {

  /**
   * Get a valid configuration object.
   */
  public function validConfig() {
    return [
      [
        [
          'field' => 'meta.keywords',
          'options' => ['value' => 'keywords'],
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
    $type = new Meta(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $type->process();
    $this->assertTrue(\property_exists($row, $config['field']));
    $this->assertEquals('HTML, Meta Tags, Metadata', $row->{$config['field']});
  }

  /**
   * Ensure the correct exception is thrown.
   */
  public function testInvalidConfigError() {
    $row = new \stdClass();
    $type = new Meta(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      ['field' => 'meta.keywords', 'options' => []]
    );
    $this->expectException(\Exception::class);
    $type->process();
  }

}
