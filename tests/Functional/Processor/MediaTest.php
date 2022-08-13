<?php

namespace Merlin\Tests\Functional\Processor;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Processor\Media;

/**
 * Ensure that the media trait correctly functions.
 */
class MediaTest extends CrawlerTestCase
{

  /**
   * Ensure that when used as a proceessor getEmbeddedAttributes returns valid.
   */
  public function testMediaAttributesProcessor()
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
      'media_plugin' => 'media',
      'external_assets' => false,
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
      'external_assets' => false,
    ];

    $crawler = $this->getCrawler();

    $processor = new Media($config, $crawler, $this->getOutput());

    $markup = $crawler->filter('#with-image')->html();
    $value = $processor->process($markup);

    $this->assertTrue(strpos($value, 'data-embed-button="test_media"') !== FALSE);
    $this->assertTrue(strpos($value, 'data-entity-type="test"') !== FALSE);
    $this->assertTrue(strpos($value, 'data-entity-embed-display="view_mode:media.test"') !== FALSE);
    $this->assertTrue(strpos($value, '<drupal-media') !== FALSE);
  }


  /**
   * Ensure that the processor value is valid for linkit (image).
   */
  public function testProcessorLinkitImageValue()
  {
    $config = [
      'type' => 'media',
      'selector' => 'img#400x400',
      'file' => 'src',
      'name' => 'src',
      'alt' => 'alt',
      'media_plugin' => 'linkit',
    ];

    $crawler = $this->getCrawler();

    $processor = new Media($config, $crawler, $this->getOutput());

    $markup = $crawler->filter('#with-image')->html();
    $value = $processor->process($markup);
    $this->assertTrue(strpos($value, 'data-entity-type="media"') !== FALSE);
    $this->assertTrue(strpos($value, 'data-entity-substitution="media"') !== FALSE);
    $this->assertTrue(strpos($value, 'data-entity-uuid="26520ab1-6c12-30ee-8e63-00b618fd598b"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/400x400.jpg"') !== FALSE);
  }

  /**
   * Ensure that the processor value is valid for linkit (inline document).
   */
  public function testProcessorLinkitDocumentValue()
  {
    $config = [
      'type' => 'media',
      'selector' => '//a[contains(@href, ".pdf") or contains(@href, ".doc")]',
      'file' => './@href',
      'name' => './text()',
      'xpath' => true,
      'media_plugin' => 'linkit',
    ];

    $crawler = $this->getCrawler();

    $processor = new Media($config, $crawler, $this->getOutput());

    $markup = $crawler->filter('#with-links')->html();
    $value = $processor->process($markup);

    $this->assertTrue(strpos($value, 'data-entity-type="media"') !== FALSE);
    $this->assertTrue(strpos($value, 'data-entity-substitution="media"') !== FALSE);

    # something.doc
    $this->assertTrue(strpos($value, 'data-entity-uuid="2f978042-b8eb-35ab-b32d-cc28d0e27214"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something.doc"') !== FALSE);

    # something.pdf
    $this->assertTrue(strpos($value, 'data-entity-uuid="a31c283f-5a5c-3a33-bab1-60dfce929a77"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something.pdf"') !== FALSE);

    # something with spaces.pdf
    $this->assertTrue(strpos($value, 'data-entity-uuid="92eab4f3-8e59-3ab3-ae0d-304622893db7"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something with spaces.pdf"') !== FALSE);

    # something%20with%20encoded.pdf
    $this->assertTrue(strpos($value, 'data-entity-uuid="dc894c2a-4f96-361a-818c-8d0352fc98ce"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something with encoded.pdf"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something%with%encoded.pdf"') === FALSE);
  }


}
