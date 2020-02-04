<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Uuid;

class UuidTest extends CrawlerTestCase {

  /**
   * Ensure that the generated UUID matches for the path.
   */
  public function testPathUuid() {
    $row = new \stdClass();
    $uuid = new Uuid(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      ['field' => 'uuid']
    );

    $uuid->process();
    $this->assertEquals( '9f0aa4c1-0145-3b3f-b718-c8c37db7710f', $row->uuid);
  }

}
