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
    $this->assertEquals( 'cefc6637-1599-38a5-9a6b-8e7c0763490f', $row->uuid);
  }

}
