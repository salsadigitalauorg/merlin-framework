<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\Group;
use Migrate\Exception\ElementNotFoundException;

class GroupTest extends CrawlerTestCase {


  /**
   * Tests selecting and building nested group data.
   * @group type_group
   */
  public function testGroup() {

    $config = [
      'field' => 'most_downloaded_movies',
      'type' => 'group',
      'selector' => '//table[contains(@class, "t1")]//tbody//tr',
      'each' => [
        [
          "field"     => "rank",
          "type"      => "text",
          "selector"  => "//th[1]",
        ],
        [
          "field"     => "movie",
          "type"      => "text",
          "selector"  => "//td[1]",
        ],
        [
          "field"     => "downloads",
          "type"      => "text",
          "selector"  => "//td[2]",
        ],
        [
          "field"     => "grosses",
          "type"      => "text",
          "selector"  => "//td[3]",
        ]
      ],
    ];


    $row = new \stdClass;
    $type = new Group(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $type->process();

    $this->assertObjectHasAttribute('most_downloaded_movies', $row);
    $this->assertEquals(10, count($row->most_downloaded_movies['children']));

  }


  /**
   * @group type_group
   */
  public function testGroupSort() {

    $config = [
      'field' => 'most_downloaded_movies',
      'type' => 'group',
      'selector' => '//table[contains(@class, "t1")]//tbody//tr',
      'options' => [
        "sort_field" => "downloads",
        "sort_direction" => "asc"
      ],
      'each' => [
        [
          "field"     => "rank",
          "type"      => "text",
          "selector"  => "//th[1]",
        ],
        [
          "field"     => "movie",
          "type"      => "text",
          "selector"  => "//td[1]",
        ],
        [
          "field"     => "downloads",
          "type"      => "text",
          "selector"  => "//td[2]",
        ],
        [
          "field"     => "grosses",
          "type"      => "text",
          "selector"  => "//td[3]",
        ]
      ],
    ];

    $row = new \stdClass;
    $type = new Group(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $type->process();

    $this->assertObjectHasAttribute('most_downloaded_movies', $row);
    $this->assertEquals(10, count($row->most_downloaded_movies['children']));
    $this->assertEquals("Harry Potter and the Deathly Hallows Part 2",
      $row->most_downloaded_movies['children'][0]['movie']
    );
  }


  /**
   * Test 'each' missing in config.
   * @group type_group
   */
  public function testGroupEachMissing() {
    $config = [
      'field' => 'most_downloaded_movies',
      'type' => 'group',
      'selector' => '//table[contains(@class, "t1")]//tbody//tr',
    ];

    $row = new \stdClass;
    $type = new Group(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $this->expectException(\Exception::class);
    $type->process();
  }

}
