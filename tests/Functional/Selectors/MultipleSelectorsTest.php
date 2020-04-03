<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Parser\Config;
use Merlin\Parser\ConfigBase;
use Merlin\Parser\WebConfig;
use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Text;


/**
 * Tests multiple selectors are giving the expected output.
 * Class MultipleSelectorsTest
 * @package Merlin\Tests\Functional\Type
 */
class MultipleSelectorsTest extends CrawlerTestCase
{


  /**
   * @return object
   * @throws \ReflectionException
   */
  private function getMappingsConfigObject(){
    $r = new \ReflectionClass(WebConfig::class);
    return $r->newInstanceWithoutConstructor();
  }

  /**
   * @group multiple_selectors
   */
  public function testInflateMappings() {

    $config = $this->getMappingsConfigObject();

    $data = [
      'mappings' => [
        [
          'field' => [
            'text_1',
            'text_2',
            'text_3',
          ],
          'selector' => [
            '//#multiple-selectors-1',
            '//#multiple-selectors-2',
            '//#multiple-selectors-3',
          ]
        ]
      ]
    ];

    $patch = function() use (&$data) {
      $data = $this->inflateMappings($data);
    };
    $patch->call($config);

    $this->assertArrayHasKey('mappings', $data);
    $this->assertCount(3, $data['mappings']);

    $mappings = $data['mappings'];

    $this->assertArrayHasKey("field",    $mappings[0]);
    $this->assertArrayHasKey("selector", $mappings[0]);
    $this->assertArrayHasKey("field",    $mappings[1]);
    $this->assertArrayHasKey("selector", $mappings[1]);
    $this->assertArrayHasKey("field",    $mappings[2]);
    $this->assertArrayHasKey("selector", $mappings[2]);
  }


  /**
   * Tests the many fields from many selectors mapping mode
   * @throws \Merlin\Exception\ElementNotFoundException
   * @throws \ReflectionException
   * @group multiple_selectors
   * @depends testInflateMappings
   */
  public function testManyFieldsFromManySelectors()
  {

    $config = $this->getMappingsConfigObject();

    $data = [
      'mappings' => [
        [
          'field' => [
            'text_1',
            'text_2',
            'text_3',
          ],
          'selector' => [
            '#multiple-selectors-1',
            '#multiple-selectors-2',
            '#multiple-selectors-3',
          ]
        ]
      ]
    ];

    $patch = function() use (&$data) {
      $data = $this->inflateMappings($data);
    };
    $patch->call($config);

    $mappings = $data['mappings'];

    foreach ($mappings as $idx => $mapping) {

      $row = new \stdClass();
      $type = new Text(
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        [
          'field' => $mapping['field'],
          'selector' => $mapping['selector']
        ]
      );

      $type->process();

      $count = $idx + 1;
      $expectedField = "text_{$count}";
      $expectedString = "This is a div for testing multiple selectors #{$count}";

      $this->assertTrue(\property_exists($row, $expectedField));
      $this->assertEquals($expectedString, $row->$expectedField);

    }

  }


  /**
   * Tests the many fields from single selector mapping mode
   * @throws \Merlin\Exception\ElementNotFoundException
   * @throws \ReflectionException
   * @group multiple_selectors
   * @depends testInflateMappings
   */
  public function testManyFieldsFromSingleSelector()
  {

    $config = $this->getMappingsConfigObject();

    $data = [
      'mappings' => [
        [
          'field' => [
            'text_1',
            'text_2',
            'text_3',
          ],
          'selector' => '#multiple-selectors-1'
        ]
      ]
    ];

    $patch = function() use (&$data) {
      $data = $this->inflateMappings($data);
    };
    $patch->call($config);

    $mappings = $data['mappings'];

    $results = [];

    foreach ($mappings as $idx => $mapping) {

      $row = new \stdClass();
      $type = new Text(
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        [
          'field' => $mapping['field'],
          'selector' => $mapping['selector']
        ]
      );

      $type->process();

      $results = array_merge($results, (array)$row);

    }

    $expectedString = "This is a div for testing multiple selectors #1";

    $this->assertArrayHasKey("text_1", $results);
    $this->assertArrayHasKey("text_2", $results);
    $this->assertArrayHasKey("text_3", $results);

    $this->assertEquals($expectedString, $results['text_1']);
    $this->assertEquals($expectedString, $results['text_2']);
    $this->assertEquals($expectedString, $results['text_3']);

  }


  /**
   * Tests the single field (first match) from multiple selectors mapping mode
   * @throws \Merlin\Exception\ElementNotFoundException
   * @throws \ReflectionException
   * @group multiple_selectors
   * @depends testInflateMappings
   */
  public function testSingleFieldFromMultipleSelectors()
  {

    $config = $this->getMappingsConfigObject();

    $data = [
      'mappings' => [
        [
          'field' => 'text_1',
          'selector' => [
            '#multiple-selectors-2',
            '#multiple-selectors-1',
            '#multiple-selectors-3',
          ]
        ]
      ]
    ];

    $patch = function() use (&$data) {
      $data = $this->inflateMappings($data);
    };
    $patch->call($config);

    $mappings = $data['mappings'];

    $results = [];

    foreach ($mappings as $idx => $mapping) {

      $row = new \stdClass();
      $type = new Text(
        $this->getCrawler(),
        $this->getOutput(),
        $row,
        [
          'field' => $mapping['field'],
          'selector' => $mapping['selector']
        ]
      );

      $type->process();

      $results = array_merge($results, (array)$row);

    }

    $expectedString = "This is a div for testing multiple selectors #2";
    $this->assertArrayHasKey("text_1", $results);
    $this->assertEquals($expectedString, $results['text_1']);

  }


}
