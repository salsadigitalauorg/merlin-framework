<?php

namespace Migrate\Processor;

use Symfony\Component\DomCrawler\Crawler;
use Ramsey\Uuid\Uuid;
use Migrate\Output\OutputInterface;
use Migrate\Utility\Callback;
use Migrate\Utility\MediaTrait;
use Migrate\ProcessController;

/**
 * A media processor.
 *
 * This processor can be added to any text value and can be used to replace
 * links in-text with Drupal media embedded entities.
 *
 * This processor can use XPath selectors to access information in the current
 * DOM fragment to determine if we have valid media. To enable xpath you will
 * need to set the xpath flag.
 *
 * @usage:
 *   media:
 *     type: image
 *     selector: img
 *     file: src
 *     name: alt
 *     xpath: false
 */
class Media extends ProcessorOutputBase implements ProcessorInterface
{

    use MediaTrait;

    /** @var mixed|string  */
    public $type;

    /** @var mixed|string  */
    public $selector;

    /** @var mixed|string  */
    public $file;

    /** @var mixed|string  */
    public $name;

    /** @var mixed|string  */
    public $alt;

    /** @var boolean  */
    public $xpath;

    /** @var array  */
    public $entities;

    /** @var boolean|mixed  */
    public $processors;

    /** @var boolean|mixed  */
    public $process_name;

    /** @var boolean|mixed  */
    public $process_file;


    /**
     * {@inheritdoc}
     */
    public function __construct(array $config, Crawler $crawler, OutputInterface $output)
    {
        parent::__construct($config, $crawler, $output);

        $xpath = !empty($config['xpath']);
        $this->xpath = $xpath;

        // Default attribute selectors.
        $file = $xpath ? './@src' : 'src';
        $name = $xpath ? './@alt' : 'alt';
        $alt  = $xpath ? './@alt' : 'alt';

        $this->type     = isset($config['type']) ? $config['type'] : 'image';
        $this->selector = isset($config['selector']) ? $config['selector'] : 'img';
        $this->file     = isset($config['file']) ? $config['file'] : $file;
        $this->name     = isset($config['name']) ? $config['name'] : $name;
        $this->alt      = isset($config['alt']) ? $config['alt'] : $alt;

        $this->config = [];
        $this->config['attributes'] = [];

        $this->config['attributes']['data_embed_button']         = !empty($config['data_embed_button']) ? $config['data_embed_button'] : 'tide_media';
        $this->config['attributes']['data_entity_embed_display'] = !empty($config['data_entity_embed_display']) ? $config['data_entity_embed_display'] : 'view_mode:media.embedded';
        $this->config['attributes']['data_entity_type']          = !empty($config['data_entity_type']) ? $config['data_entity_type'] : 'media';
        $this->config['attributes']['media_plugin']              = !empty($config['media_plugin']) ? $config['media_plugin'] : 'media';

        $this->config['extra'] = isset($config['extra']) ? $config['extra'] : [];

        $this->entities = [];

        $this->processors   = isset($config['processors']) ? $config['processors'] : false;
        $this->process_name = isset($config['process_name']) ? $config['process_name'] : false;
        $this->process_file = isset($config['process_file']) ? $config['process_file'] : false;

    }//end __construct()


    /**
     * Process media items that will be selected using Xpath selectors.
     *
     * @param string value
     *   The value to search through.
     *
     * @return string
     *   The replaced string.
     */
    protected function processXpath(&$value)
    {
        $media = $this->crawler->evaluate($this->selector);

        if (is_array($media) || $media->count() == 0) {
            // Ensure that we can find media that matches $this->selector.
            return $value;
        }

        $media->each(
            function (Crawler $node) use (&$value) {
                $name = $node->evaluate($this->name);
                $file = $node->evaluate($this->file);
                $alt  = $node->evaluate($this->alt);

                if (!method_exists($name, 'count') || !method_exists($file, 'count')) {
                    // Invalid xpath selector for the child elements.
                    return;
                }

                if ($file->count() == 0) {
                    // Valid xpath but doesn't match anything.
                    return;
                }

                if ($name->count() == 0 && $file->count() > 0) {
                    // We have a file name, but no name match, use the last part of the file as the name.
                    $parts = explode("/", $file->text());
                    $name = $parts[(count($parts) - 1)];
                    $this->output->mergeRow("warning-{$this->type}", $file->text(), ["Using fallback name {$name}"], true);
                } else {
                    $name = $name->text();
                }

                $file = urldecode($file->text());
                $alt = ($alt->count() > 0) ? $alt->text() : null;

                $uuid = $this->getUuid($name, $file);

                if ($this->process_file) {
                    $file = ProcessController::apply($file, $this->process_file, $this->crawler, $this->output);
                }

                if ($this->process_name) {
                    $name = ProcessController::apply($name, $this->process_name, $this->crawler, $this->output);
                }

                // @TODO: Process controller that can apply to
                // types or processors recursively and manage this
                // type of thing ongoing.
                if ($this->processors) {
                    foreach ($this->processors as $processor => $config) {
                        if ($processor == 'replace') {
                              $p    = new Replace($config);
                              $file = $p->process($file);
                        }
                    }
                }

                $this->entities[] = [
                    'name' => $name,
                    'file' => $this->getFileUrl($file),
                    'uuid' => $uuid,
                    'alt'  => $alt,
                ];

                $parent     = $node->getNode(0);
                $outer_html = $parent->ownerDocument->saveHtml($parent);

                // Basic support for linkit vs. media embed.
                switch ($this->config['attributes']['media_plugin']) {
                  case "linkit":
                    $value = str_replace($outer_html, $this->getDrupalLinkitEmbed($parent, $this->getFileUrl($file), $uuid), $value);
                  break;

                  case "media":
                  default:
                    $value = str_replace($outer_html, $this->getDrupalEntityEmbed($uuid), $value);
                  break;
                }
            }
        );

    }//end processXpath()


    /**
     * Process media items that will be selected using DOM selectors.
     *
     * @param string value
     *   The value to search through.
     *
     * @return string
     *   The replaced string.
     */
    protected function processDom(&$value)
    {
        $media = $this->crawler->filter($this->selector);

        if ($media->count() == 0) {
            // Ensure that we can find media that matches $this->selector.
            return $value;
        }

        $media->each(
            function (Crawler $node) use (&$value) {
                $name = $node->attr($this->name);
                $file = $node->attr($this->file);
                $alt  = $node->attr($this->alt);
                $uuid = $this->getUuid($name, $file);

                if ($this->process_file) {
                    $file = ProcessController::apply($file, $this->process_file, $this->crawler, $this->output);
                }

                if ($this->process_name) {
                    $name = ProcessController::apply($name, $this->process_name, $this->crawler, $this->output);
                }

                if (empty($name) && !empty($file)) {
                    // We have a file name, but no name match, use the last part of the file as the name.
                    $parts = explode("/", $file);
                    $name = $parts[(count($parts) - 1)];
                    $this->output->mergeRow("warning-{$this->type}", $file, ["Using fallback name {$name}"], true);
                }

                // @TODO: Process controller that can apply to
                // types or processors recursively and manage this
                // type of thing ongoing.
                if ($this->processors) {
                    foreach ($this->processors as $processor => $config) {
                        if ($processor == 'replace') {
                              $p    = new Replace($config);
                              $file = $p->process($file);
                        }
                    }
                }

                $this->entities[] = [
                    'name' => substr($name, 0, 255),
                    'file' => $this->getFileUrl($file),
                    'uuid' => $uuid,
                    'alt'  => substr($alt, 0, 512),
                ];

                $parent     = $node->getNode(0);
                $outer_html = $parent->ownerDocument->saveHtml($parent);

                // Basic support for linkit vs. media embed.
                switch ($this->config['attributes']['media_plugin']) {
                  case "linkit":
                    $value = str_replace($outer_html, $this->getDrupalLinkitEmbed($parent, $this->getFileUrl($file), $uuid), $value);
                  break;

                  case "media":
                  default:
                    $value = str_replace($outer_html, $this->getDrupalEntityEmbed($uuid), $value);
                  break;
                }
            }
        );

    }//end processDom()


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        $this->xpath ? $this->processXpath($value) : $this->processDom($value);

        if (count($this->entities) === 0) {
            return $value;
        }

        // Remove duplicate UUIDs.
        $tmp            = array_unique(array_column($this->entities, 'uuid'));
        $this->entities = array_intersect_key($this->entities, $tmp);

        if (count($this->entities) > 0) {
            // If we found entities to add - we'll create a new output file and add
            // the entities directly.
            $this->output->mergeRow("media-{$this->type}", 'data', $this->entities, true);
        }

        return $value;

    }//end process()


}//end class
