<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Utility\MediaTrait;
use Migrate\Utility\ProcessorOptionsTrait;
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
        'file'         => $xpath ? '@src' : 'src',
        'name'         => $xpath ? '@alt' : 'alt',
        'alt'          => $xpath ? '@alt' : 'alt',
    ];

  }//end options()


  /**
   * {@inheritdoc}
   */
  public function processXpath() {
    $uuids = [];

    $this->crawler->each(
        function (Crawler $node) use (&$uuids) {

            try {
                $name = $node->evaluate($this->getOption('name'));
                assert($name->count() > 0);
            } catch (\Exception $error) {
                throw new ElementNotFoundException();
            }

            try {
                $file = $node->evaluate($this->getOption('file'));
                assert($file->count() > 0);
            } catch (\Exception $error) {
                throw new ElementNotFoundException();
            }

            try {
                $alt = $node->evaluate($this->getOption('alt'));
                assert($alt->count() > 0);
            } catch (\Exception $error) {
                throw new ElementNotFoundException();
            }

            $name = $name->text();
            $file = $this->getFileUrl($node->text());
            $alt = $node->text();
            $uuid = $this->getUuid($name, $file);

            $entity = [
                'file' => $file,
                'uuid' => $uuid,
                'alt' => $alt,
                'name' => $name,
            ];

            $this->entities[] = $this->applyProcessors($entity);
            $uuids[] = $uuid;
        }
    );

    if (count($this->entities) > 0) {
        extract($this->config);
        $this->output->mergeRow("media-{$type}", 'data', $this->entities, TRUE);
        $this->addValueToRow($uuids);
    }

  }//end processXpath()


  /**
   * {@inheritdoc}
   */
  public function processDom() {
    $uuids = [];

    $this->crawler->each(
        function (Crawler $node) use (&$uuids) {
            $name = $node->attr($this->getOption('name'));
            $file = $node->attr($this->getOption('file'));
            $file = $this->getFileUrl($file);
            $alt = $node->attr($this->getOption('alt'));
            $uuid = $this->getUuid($name, $file);

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
        extract($this->config);
        $this->output->mergeRow("media-{$type}", 'data', $this->entities, TRUE);
        $this->addValueToRow($uuids);
    }

  }//end processDom()


}//end class
