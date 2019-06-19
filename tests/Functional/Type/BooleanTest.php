<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\Boolean;

class BooleanTest extends CrawlerTestCase {

  /**
   * A list of selectors and if they exist in the DOM.
   */
  public function selectors() {
    return [
      ['//*[@id="wrapper"]/h3[1]', TRUE],
      ['//*[@id="wrapper"]/h3[6]', FALSE],
      ['.sample-class', TRUE],
      ['.another-sample-class', FALSE],
    ];
  }

  /**
   * Ensure that the path matches the expected result.
   *
   * @dataProvider selectors
   */
  public function testEvaluation($selector, $expected) {
    $row = new \stdClass();
    $boolean = new Boolean (
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      [
        'field' => 'boolean',
        'selector' => $selector,
      ]
    );
    $boolean->process();
    $this->assertEquals($expected, $row->boolean);
  }
}
