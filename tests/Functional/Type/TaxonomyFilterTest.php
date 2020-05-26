<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\TaxonomyFilter;
use Merlin\Exception\ValidationException;
use Merlin\Exception\ElementNotFoundException;

/**
 * Tests for the taxonomy filter.
 */
class TaxonomyFilterTest extends CrawlerTestCase {

  /**
   * Ensure that the correct error is thrown if invalid selector.
   */
  public function testInvalidSelector() {
    $row = new \stdClass;
    $config = [
      'field' => 'taxonomy_filter',
      'selector' => '.taxonomy-term ul li',
      'options' => [
        'pattern' => '/(\d{4}-\d{4})/',
      ]
    ];
    $type = new TaxonomyFilter(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(ElementNotFoundException::class);
    $type->process();
  }

  /**
   * Ensure that vocab is required.
   */
  public function testInvalidVocab() {
    $row = new \stdClass;
    $config = [
      'field' => 'taxonomy_filter',
      'selector' => '.taxonomy-terms ul li',
      'options' => [
        'pattern' => '/(\d{4}-\d{4})/',
      ]
    ];
    $type = new TaxonomyFilter(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(ValidationException::class);
    $type->process();
  }

  /**
   * Pattern option is missing.
   */
  public function testMissingPattern() {
    $row = new \stdClass;
    $config = [
      'field' => 'taxonomy_filter',
      'selector' => '.taxonomy-terms ul li',
      'options' => [
        'vocab' => 'demo',
      ]
    ];
    $type = new TaxonomyFilter(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(ValidationException::class);
    $type->process();
  }

  /**
   * Ensure invalid patterns are caught correctly.
   */
  public function testInvalidPattern() {
    $row = new \stdClass;
    $config = [
      'field' => 'taxonomy_filter',
      'selector' => '.taxonomy-terms ul li',
      'options' => [
        'vocab' => 'demo',
        'pattern' => '(\d{4}-\d{4})',
      ]
    ];
    $type = new TaxonomyFilter(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(ValidationException::class);
    $type->process();
  }

  /**
   * Ensure if correct configuration is given expect results happen.
   */
  public function testMergeRow() {
    $results['1394653158'] = [
      'term_id' => 1394653158,
      'name' => '1900-1910'
    ];
    $results['3732598673'] = [
      'term_id' => 3732598673,
      'name' => '1910-1920',
    ];
    $results['4136223821'] = [
      'term_id' => 4136223821,
      'name' => '1920-1930',
    ];

    $output = $this->getOutput(['mergeRow']);
    $output->expects($this->once())
      ->method('mergeRow')
      ->with('demo', 'data', $results);


    $row = new \stdClass;
    $config = [
      'field' => 'taxonomy_filter',
      'selector' => '.taxonomy-terms ul li',
      'options' => [
        'vocab' => 'demo',
        'pattern' => '/(\d{4}-\d{4})/',
      ]
    ];

    $type = new TaxonomyFilter(
      $this->getCrawler(),
      $output,
      $row,
      $config
    );

    $type->process();

    $this->assertObjectHasAttribute('taxonomy_filter', $row);
    $this->assertEquals(3, count($row->taxonomy_filter));
  }

}
