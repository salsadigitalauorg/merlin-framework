<?php

namespace Migrate\Tests\Functional\Processor;

use Migrate\Tests\Functional\CrawlerTestCase;
use Migrate\Processor\Media;

/**
 * Ensure that the media trait correctly functions.
 */
class MediaNullAttributeTest extends CrawlerTestCase
{

  /**
   * Ensure that when used as a proceessor getEmbeddedAttributes returns valid.
   */
  public function testMediaNullAttributesProcessor()
  {
    $config = [
      'type' => 'media',
      'selector' => 'img',
      'file' => 'src',
      'name' => 'src',
      'alt' => 'alt',
      'data_embed_button' => 'test_media',
      'data_entity_embed_display' => 'view_mode:media.test',
      'data_entity_type' => 'test',
      'media_plugin' => 'media'
    ];

    $crawler = $this->getCrawler();

    $processor = new Media($config, $crawler, $this->getOutput());

    foreach ($processor->getEmbeddedAttributes() as $ak => $av) {
      $this->assertEquals($av, $config[$ak], "$ak");
    }
  }

  /**
   * Ensure that the processor value matches whats expected.
   */
  public function testProcessorValue()
  {
    $config = [
      'type' => 'media',
      'selector' => 'img',
      'file' => 'src',
      'name' => 'src',
      'alt' => 'alt',
      'data_embed_button' => 'test_media',
      'data_entity_embed_display' => 'view_mode:media.test',
      'data_entity_type' => 'test',
    ];

    $crawler = $this->getCrawler();

    $processor = new Media($config, $crawler, $this->getOutput());

    $markup = $crawler->filter('#with-image-null-attributes')->html();
    $value = $processor->process($markup);

    $this->assertTrue(strpos($value, 'data-embed-button="test_media"') !== FALSE);
    $this->assertTrue(strpos($value, 'data-entity-type="test"') !== FALSE);
    $this->assertTrue(strpos($value, 'data-entity-embed-display="view_mode:media.test"') !== FALSE);
    $this->assertTrue(strpos($value, '<drupal-entity') !== FALSE);
  }


}
