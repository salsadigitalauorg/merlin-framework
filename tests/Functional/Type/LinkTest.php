<?php

namespace Migrate\Tests\Functional\Type;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Type\Link;

class LinkTest extends CrawlerTestCase {

  public function validConfig() {
    return [
      [
        [
        'field' => 'field_link',
        'selector' => 'ul.with-links > li',
        ],
        3,
      ],
      [
        [
          'field' => 'field_link',
          'selector' => '//*[@id="wrapper"]/div/ul[1]/li',
        ],
        3
      ],
    ];
  }

  /**
   * Ensure that links in the DOM can be found.
   *
   * @dataProvider validConfig
   */
  public function testValidLinks($config, $expected) {
    $row = new \stdClass();
    $link = new Link(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );
    $link->process();

    $this->assertTrue(\property_exists($row, $config['field']));
    $this->assertEquals($expected, count($row->{$config['field']}));
  }

  /**
   * Ensure that getOption returns expected values.
   */
  public function testGetOption() {
    $row = new \stdClass;
    $config = [
      'field' => 'field_link',
      'selector' => 'ul.with-links > li',
      'options' => [
        'link' => 'attr-link',
        'text' => 'attr-text',
      ],
    ];
    $link = new Link(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );

    $this->assertEquals('attr-link', $link->getOption('link'));
    $this->assertEquals('attr-text', $link->getOption('text'));
  }

  /**
   * Ensure that default options are expected values.
   */
  public function testGetOptionDefaults() {
    $row = new \stdClass;
    $config = [
      'field' => 'field_link',
      'selector' => 'ul.with-links > li',
    ];
    $link = new Link(
      $this->getCrawler(),
      $this->getOutput(),
      $row,
      $config
    );

    $this->assertEquals('href', $link->getOption('link'));
    $this->assertEquals('./a/@href', $link->getOption('link', TRUE));
    $this->assertEquals('a', $link->getOption('text'));
    $this->assertEquals('./a/text()', $link->getOption('text', TRUE));
    $this->assertEquals('internal:', $link->getOption( 'internal_identifier'));
  }

  /**
   * Ensure that the text can be processed.
   */
  public function testTextProcess() {
    $row = new \stdClass;
    $config = [
      'field' => 'field_link',
      'selector' => 'ul.with-links > li',
      'processors' => [
        'text' => [
          'replace' => [
            'pattern' => '[\w\s]+',
            'replace' => 'Test',
          ],
        ],
      ],
    ];

    $link = new Link($this->getCrawler(), $this->getOutput(), $row, $config);
    $link->process();

    foreach ($row->field_link as $link) {
      $this->assertEquals('Test', $link['text']);
      $this->assertNotEquals('Test', $link['link']);
    }
  }

  /**
   * Ensure taht the link processors can be applied.
   */
  public function testLinkProcess() {
    $row = new \stdClass;
    $config = [
      'field' => 'field_link',
      'selector' => 'ul.with-links > li',
      'processors' => [
        'link' => [
          'replace' => [
            'pattern' => '.*',
            'replace' => 'Test Link',
          ],
        ],
      ],
    ];

    $link = new Link($this->getCrawler(), $this->getOutput(), $row, $config);
    $link->process();

    foreach ($row->field_link as $link) {
      $this->assertNotEquals('Test', $link['text']);
      // Bad regex, but gets the point across.
      $this->assertEquals('Test LinkTest Link', $link['link']);
    }
  }

  /**
   * Ensure that we can process both.
   */
  public function testBothProcess() {
    $row = new \stdClass;
    $config = [
      'field' => 'field_link',
      'selector' => 'ul.with-links > li',
      'processors' => [
        'link' => [
          'replace' => [
            'pattern' => '.*',
            'replace' => 'Test Link',
          ],
        ],
        'text' => [
          'replace' => [
            'pattern' => '[\w\s]+',
            'replace' => 'Test text',
          ],
        ],
      ],
    ];

    $link = new Link($this->getCrawler(), $this->getOutput(), $row, $config);
    $link->process();

    foreach ($row->field_link as $link) {
      $this->assertEquals('Test text', $link['text']);
      $this->assertEquals('Test LinkTest Link', $link['link']);
    }
  }

}
