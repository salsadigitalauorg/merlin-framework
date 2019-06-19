<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\Alias;

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

}
