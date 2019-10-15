<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Utility\MediaTrait;
use Migrate\Utility\ProcessorOptionsTrait;
use Migrate\Utility\Callback;
use Migrate\ProcessController;
use Migrate\Exception\ElementNotFoundException;

/**
 * A media processor.
 *
 * This processor can be added to any text value and can be used to replace
 * links in-tezt with Drupal media embedded entities.
 *
 * This processor can use XPath selectors to access information in the current
 * DOM fragment to determine if we have valid media. To enable xpath you will
 * need to set the xpath flag.
 *
 * @usage:
 *   media:
 *     type: image
 *     selector: img
 *     options:
 *       file: src
 *       name: alt
 *       xpath: false
 */
class Media extends TypeMultiComponent implements TypeInterface {

  use MediaTrait;
  use ProcessorOptionsTrait;


  /**
   * {@inheritdoc}
   */
  public function options($xpath=FALSE) {
    return [
        'process_name' => FALSE,
        'process_file' => FALSE,
        'file'         => $xpath ? './@src' : 'src',
        'name'         => $xpath ? './@alt' : 'alt',
        'alt'          => $xpath ? './@alt' : 'alt',
    ];

  }//end options()


  /**
   * {@inheritdoc}
   */
  public function processXpath() {
    $uuids = [];
    extract($this->config['options']);
    $type = isset($this->config['options']['type']) ? $this->config['options']['type'] : 'media';

    $this->crawler->each(
        function (Crawler $node) use (&$uuids, $type) {
            try {
                $file = $node->evaluate($this->getOption('file', TRUE));
                assert($file->count() > 0);
                $file = $this->getFileUrl($file->text());
                if (isset($this->config['options']['process_file'])) {
                    if (!empty($file)) {
                        $file = Callback::getResult($this->config['options']['process_file'], $file);
                    }
                }
            } catch (\Exception $error) {
                throw new ElementNotFoundException();
            }

            try {
                $name = $node->evaluate($this->getOption('name', TRUE));
                assert($name->count() > 0);
                $name = $name->text();
                if (isset($this->config['options']['process_name'])) {
                    if (!empty($name)) {
                        $name = Callback::getResult($this->config['options']['process_name'], $name);
                    }
                }
            } catch (\Exception $error) {
                $parts = explode("/", $file);
                $name = $parts[(count($parts) - 1)];
                $this->output->mergeRow("warning-{$type}", $file, ["Using fallback name {$name}"], true);
            }

            try {
                $alt = $node->evaluate($this->getOption('alt', TRUE));
                assert($alt->count() > 0);
                $alt = $alt->text();
            } catch (\Exception $error) {
                $alt = null;
            }

            $uuid = $this->getUuid($name, $file);

            $entity = [
                'file' => $file,
                'uuid' => $uuid,
                'alt'  => $alt,
                'name' => $name,
            ];

            $this->entities[] = $this->applyProcessors($entity);
            $uuids[] = $uuid;
        }
    );

    if (count($this->entities) > 0) {
        $this->output->mergeRow("media-{$type}", 'data', $this->entities, TRUE);
        $this->addValueToRow($uuids);
    }

  }//end processXpath()


  /**
   * {@inheritdoc}
   */
  public function processDom() {
    $uuids = [];
    extract($this->config['options']);
    $type = isset($this->config['options']['type']) ? $this->config['options']['type'] : 'media';

    $this->crawler->each(
        function (Crawler $node) use (&$uuids, $type) {
            $name = $node->attr($this->getOption('name'));
            $file = $node->attr($this->getOption('file'));
            $file = $this->getFileUrl($file);
            $alt = $node->attr($this->getOption('alt'));
            $uuid = $this->getUuid($name, $file);

            if (empty($name) && !empty($file)) {
                // We have a file name, but no name match, use the last part of the file as the name.
                $parts = explode("/", $file);
                $name = $parts[(count($parts) - 1)];
                $this->output->mergeRow("warning-{$type}", $file, ["Using fallback name {$name}"], true);
            }

            if (isset($this->config['options']['process_name'])) {
                if (!empty($name)) {
                    $name = Callback::getResult($this->config['options']['process_name'], $name);
                }
            }

            if (isset($this->config['options']['process_file'])) {
                if (!empty($file)) {
                    $file = Callback::getResult($this->config['options']['process_file'], $file);
                }
            }

            $entity = [
                'name' => $name,
                'file' => $file,
                'uuid' => $uuid,
                'alt'  => $alt,
            ];

            $this->entities[] = $this->applyProcessors($entity);
            $uuids[] = $uuid;
        }
    );

    if (count($this->entities) > 0) {
        $this->output->mergeRow("media-{$type}", 'data', $this->entities, TRUE);
        $this->addValueToRow($uuids);
    }

  }//end processDom()


}//end class
