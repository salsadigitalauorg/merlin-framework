<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Exception\ElementNotFoundException;
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


  /**
   * Checks the default string value is returned
   */
  public function testDefaultValue() {
    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      [
        'field'    => 'title',
        'selector' => '//*/not-found',
        'default'  => 'specified_text_value']
    );

    try {
      $type->process();
    }
    catch (ElementNotFoundException $ex) {
      // We expect this...
    }

    $this->assertEquals('specified_text_value', $row->title);
  }

  /**
   * Checks the default field values are returned
   */
  public function testDefaultValueFields() {
    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      [
        'field'    => 'title',
        'selector' => '//*/not-found',
        'default'  => [
          'fields' => [
            'field_1' => 'field_1_value',
            'field_2' => 'field_2_value'
            ]
        ]
      ]
    );

    try {
      $type->process();
    }
    catch (ElementNotFoundException $ex) {
      // We expect this...
    }

    $expected = [
      'field_1' => 'field_1_value',
      'field_2' => 'field_2_value'
    ];

    $this->assertEqualsCanonicalizing($expected, $row->title);
  }


  /**
   * Checks the default value is calculated via a function
   */
  public function testDefaultValueFunction() {
    $row = new \stdClass();
    $type = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      [
        'field'    => 'title',
        'selector' => '//*/not-found',
        'default'  => [
          'function' => '
             function($crawler) {
               $value = "value_from_function";
               return $value;
             }'
        ]
      ]
    );

    try {
      $type->process();
    }
    catch (ElementNotFoundException $ex) {
      // We expect this...
    }

    $this->assertEquals('value_from_function', $row->title);
  }






}
