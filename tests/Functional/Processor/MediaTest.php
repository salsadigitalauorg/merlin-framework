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

    $this->assertStringContainsString('data-embed-button="test_media"', $value);
    $this->assertStringContainsString('data-entity-type="test"', $value);
    $this->assertStringContainsString('data-entity-embed-display="view_mode:media.test"', $value);
    $this->assertStringContainsString('<drupal-media', $value);
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

    $this->assertStringContainsString('data-entity-type="media"', $value);
    $this->assertStringContainsString('data-entity-substitution="media"', $value);
    $this->assertStringContainsString('data-entity-uuid="26520ab1-6c12-30ee-8e63-00b618fd598b"', $value);
    $this->assertStringContainsString('<a href="/sites/default/files/400x400.jpg"', $value);
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

    $this->assertStringContainsString('data-entity-type="media"', $value);
    $this->assertStringContainsString('data-entity-substitution="media"', $value);

    # something.doc
    $this->assertStringContainsString('data-entity-uuid="2f978042-b8eb-35ab-b32d-cc28d0e27214"', $value);
    $this->assertStringContainsString('<a href="/sites/default/files/something.doc"', $value);

    # something.pdf
    $this->assertStringContainsString('data-entity-uuid="a31c283f-5a5c-3a33-bab1-60dfce929a77"', $value);
    $this->assertStringContainsString('<a href="/sites/default/files/something.pdf"', $value);

    # something%20with%20encoded.pdf
    $this->assertStringContainsString('data-entity-uuid="dc894c2a-4f96-361a-818c-8d0352fc98ce"', $value);
    $this->assertStringContainsString('<a href="/sites/default/files/something with encoded.pdf"', $value);
    $this->assertStringNotContainsString('<a href="/sites/default/files/something%with%encoded.pdf"', $value);
  }


}
