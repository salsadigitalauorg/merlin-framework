<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Group;
use Merlin\Exception\ElementNotFoundException;
use Merlin\Type\TypeBase;

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
          "selector"  => ".//th[1]",
        ],
        [
          "field"     => "movie",
          "type"      => "text",
          "selector"  => ".//td[1]",
        ],
        [
          "field"     => "downloads",
          "type"      => "text",
          "selector"  => ".//td[2]",
        ],
        [
          "field"     => "grosses",
          "type"      => "text",
          "selector"  => ".//td[3]",
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
          "selector"  => ".//th[1]",
        ],
        [
          "field"     => "movie",
          "type"      => "text",
          "selector"  => ".//td[1]",
        ],
        [
          "field"     => "downloads",
          "type"      => "text",
          "selector"  => ".//td[2]",
        ],
        [
          "field"     => "grosses",
          "type"      => "text",
          "selector"  => ".//td[3]",
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


  /**
   * Test whole GROUP is skipped if option set and one field is required.
   * @group type_group
   */
  public function testGroupRequiredFieldSkipGroup() {
    $config = [
      'field' => 'people',
      'type' => 'group',
      'selector' => '//div[@id="group_container_of_things_2"]//div[contains(@class, "person")]',
      'options' => [
        'required_skip_group' => true
      ],
      'each' => [
        [
          "field"     => "name",
          "type"      => "text",
          "selector"  => ".//div[@class='name']",
        ],
        [
          "field"     => "email",
          "type"      => "text",
          "selector"  => ".//div[@class='email']",
        ],
        [
          "field"     => "phone",
          "type"      => "text",
          "selector"  => ".//div[@class='phone']",
        ],
        [
          "field"     => "skills",
          "type"      => "text",
          "selector"  => ".//ul[@class='skills']//li",
          "options"   => [
            "required" => true
          ]
        ],
        [
          "field"     => "skills_group",
          "type"      => "group",
          "selector"  => ".//ul[@class='skills']//li",
          "each"      => [
            [
              "field" => "skill",
              "type"  => "text",
              "selector" => "."
            ]
          ]
        ],
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
    $results = json_decode(json_encode($row), true);
    $this->assertTrue(empty($results));

  }


  /**
   * Test whole CHILD is skipped if option set and one field is required.
   * @group type_group
   */
  public function testGroupRequiredFieldSkipChild() {
    $config = [
      'field' => 'people',
      'type' => 'group',
      'selector' => '//div[@id="group_container_of_things_2"]//div[contains(@class, "person")]',
      'options' => [
        'required_skip_child' => true
      ],
      'each' => [
        [
          "field"     => "name",
          "type"      => "text",
          "selector"  => ".//div[@class='name']",
        ],
        [
          "field"     => "email",
          "type"      => "text",
          "selector"  => ".//div[@class='email']",
        ],
        [
          "field"     => "phone",
          "type"      => "text",
          "selector"  => ".//div[@class='phone']",
        ],
        [
          "field"     => "skills",
          "type"      => "text",
          "selector"  => ".//ul[@class='skills']//li",
          "options"   => [
            "required" => true
          ]
        ],
        [
          "field"     => "skills_group",
          "type"      => "group",
          "selector"  => ".//ul[@class='skills']//li",
          "each"      => [
            [
              "field" => "skill",
              "type"  => "text",
              "selector" => "."
            ]
          ]
        ],
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
    $results = json_decode(json_encode($row), true);

    // There are two items that have the required skills items.
    $c = $results['people']['children'] ?? [];
    $this->assertEquals(2, count($c));

  }


  /**
   * Test each child gets a UUID generated if option set.
   *
   * @group type_group
   * @throws \Merlin\Exception\ElementNotFoundException
   * @throws \Merlin\Exception\ValidationException
   */
  public function testGroupGenerateUUID() {
    $config = [
      'field' => 'people',
      'type' => 'group',
      'selector' => '//div[@id="group_container_of_things_2"]//div[contains(@class, "person")]',
      'options' => [
        'generate_uuid' => true
      ],
      'each' => [
        [
          "field"     => "name",
          "type"      => "text",
          "selector"  => ".//div[@class='name']",
        ],
        [
          "field"     => "email",
          "type"      => "text",
          "selector"  => ".//div[@class='email']",
        ],
        [
          "field"     => "phone",
          "type"      => "text",
          "selector"  => ".//div[@class='phone']",
        ],
        [
          "field"     => "skills",
          "type"      => "text",
          "selector"  => ".//ul[@class='skills']//li",
          "options"   => [
            "required" => true
          ]
        ],
        [
          "field"     => "skills_group",
          "type"      => "group",
          "selector"  => ".//ul[@class='skills']//li",
          "each"      => [
            [
              "field" => "skill",
              "type"  => "text",
              "selector" => "."
            ]
          ]
        ],
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
    $results = json_decode(json_encode($row), true);

    // There are four items so should have four uuids
    $uuids = array_column($results['people']['children'], 'uuid');
    $this->assertNotEmpty($uuids);
    $this->assertEquals(4, count($uuids));

    foreach($uuids as $uuid) {
      $this->assertNotEmpty($uuid);
    }

  }

}
