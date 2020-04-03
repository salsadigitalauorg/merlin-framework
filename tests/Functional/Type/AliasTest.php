<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Alias;

class AliasTest extends CrawlerTestCase {

  /**
   * Ensure that the path matches the expected result.
   */
  public function testPathAlias() {
    $row = new \stdClass();
    $alias = new Alias(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      ['field' => 'alias']
    );

    $alias->process();
    $this->assertEquals('/test', $row->alias);
  }

  public function testAliasProcessors() {
    $row = new \stdClass;
    $alias = new Alias(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      [
        'field' => 'alias',
        'processors' => [
          'replace' => [
            'pattern' => '\/test',
            'replace' => '/replaced',
          ]
        ]
      ]
    );
    $alias->process();
    $this->assertEquals('/replaced', $row->alias);
  }

}
