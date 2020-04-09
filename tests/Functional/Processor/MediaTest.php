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
    $this->assertTrue(strpos($value, '<drupal-entity') !== FALSE);
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
    $this->assertTrue(strpos($value, 'data-entity-uuid="54c7ec67-fa8b-3bb5-8976-7f3cab3886e2"') !== FALSE);
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
    $this->assertTrue(strpos($value, 'data-entity-uuid="a50ca849-19e0-3009-bf11-a2d4be7dde4c"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something.doc"') !== FALSE);

    # something.pdf
    $this->assertTrue(strpos($value, 'data-entity-uuid="ac575920-fc8b-3ce6-bdfd-015b1f764681"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something.pdf"') !== FALSE);

    # something with spaces.pdf
    $this->assertTrue(strpos($value, 'data-entity-uuid="f9178c12-92b8-34b8-8e11-e999715660c3"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something with spaces.pdf"') !== FALSE);

    # something%20with%20encoded.pdf
    $this->assertTrue(strpos($value, 'data-entity-uuid="61719010-8cdf-303a-837e-a66abce18964"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something with encoded.pdf"') !== FALSE);
    $this->assertTrue(strpos($value, '<a href="/sites/default/files/something%with%encoded.pdf"') === FALSE);
  }


}
