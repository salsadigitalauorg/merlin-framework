<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Link;
use Merlin\Type\LongText;
use Merlin\Type\Ordered;
use Merlin\Type\Text;


/**
 * Ensure that the XPath and DOM selectors yield the same results for the same selectors
 */
class XPathDomComparisonTest extends CrawlerTestCase
{

  /**
   * Ensure consistent output for XPath and DOM selectors for Link type
   */
  public function testLink()
  {
    // DOM
    $config_dom = [
      'field' => 'field',
      'selector' => 'ul.with-links > li'
    ];
    $row_dom = new \stdClass();
    $type_dom = new Link(
      $this->getCrawler(),
      $this->getOutput(),
      $row_dom,
      $config_dom
    );

    $type_dom->process();

    // XPath (selector converted using https://css-selector-to-xpath.appspot.com/)
    $config_xpath = [
      'field' => 'field',
      'selector' => '//ul[contains(concat(" ",normalize-space(@class)," ")," with-links ")]/li'
    ];
    $row_xpath = new \stdClass();
    $type_xpath = new Link(
      $this->getCrawler(),
      $this->getOutput(),
      $row_xpath,
      $config_xpath
    );

    $type_xpath->process();

    // Compare DOM and XPath
    $this->assertEquals($row_dom->field, $row_xpath->field);
  }

  /**
   * Ensure consistent output for XPath and DOM selectors for LongText type
   */
  public function testLongText()
  {
    // DOM
    $row_dom = new \stdClass();
    $type_dom = new LongText(
      $this->getCrawler(),
      $this->getOutput(),
      $row_dom,
      ['field' => 'field_body', 'selector' => '.main-content']
    );

    $type_dom->process();

    // XPath (selector converted using https://css-selector-to-xpath.appspot.com/)
    $row_xpath = new \stdClass();
    $type_xpath = new LongText(
      $this->getCrawler(),
      $this->getOutput(),
      $row_xpath,
      ['field' => 'field_body', 'selector' => '//*[contains(concat(" ",normalize-space(@class)," ")," main-content ")]']
    );

    $type_xpath->process();

    // Compare DOM and XPath
    $this->assertEquals($row_dom->field_body, $row_xpath->field_body);
  }

  /**
   * Ensure consistent output for XPath and DOM selectors for LongText type
   */
  public function testOrdered()
  {
    $config_dom = [
      'field' => 'ordered',
      'type' => 'ordered',
      'selector' => 'ul.with-links > li',
      'available_items' => [
        [
          'by' => [
            'attr' => 'class',
            'text' => 'content'
          ],
          'fields' => [
            [
              'field' => 'field_body',
              'type' => 'longtext',
            ]
          ],
        ]
      ],
    ];
    // DOM
    $row_dom = new \stdClass();
    $type_dom = new Ordered(
      $this->getCrawler(),
      $this->getOutput(),
      $row_dom,
      $config_dom
    );

    $type_dom->process();

    // XPath (selector converted using https://css-selector-to-xpath.appspot.com/)
    $config_xpath = $config_dom;
    $config_xpath['selector'] = '//ul[contains(concat(" ",normalize-space(@class)," ")," with-links ")]/li';

    $row_xpath = new \stdClass();
    $type_xpath = new Ordered(
      $this->getCrawler(),
      $this->getOutput(),
      $row_xpath,
      $config_xpath
    );

    $type_xpath->process();

    // Compare DOM and XPath
    $this->assertEquals($row_dom->ordered, $row_xpath->ordered);
  }

  /**
   * Ensure consistent output for XPath and DOM selectors for Text type
   */
  public function testText()
  {
    // DOM
    $row_dom = new \stdClass();
    $type_dom = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row_dom,
      ['field' => 'title', 'selector' => 'h1']
    );

    $type_dom->process();

    // XPath
    $row_xpath = new \stdClass();
    $type_xpath = new Text(
      $this->getCrawler(),
      $this->getOutput(),
      $row_xpath,
      ['field' => 'title', 'selector' => '//h1']
    );

    $type_xpath->process();

    // Compare DOM and XPath
    $this->assertEquals($row_dom->title, $row_xpath->title);
  }

}
