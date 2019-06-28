<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Utility\MediaTrait;
use Migrate\Utility\ProcessorOptionsTrait;
use Migrate\ProcessController;

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
class Media extends TypeBase implements TypeInterface {

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
    extract($this->config['options']);

    if (empty($name) || empty($file)) {
      throw new \Exception('Cannot parse media for '.$this->config['field']);
    }

    $this->crawler->each(
        function (Crawler $node) use (&$uuids) {
        $name = $node->evaluate($this->config['options']['name'])->text();
        $file = $node->evaluate($this->config['options']['file'])->text();
        if ($node->evaluate($this->getOption('alt'))->count() > 0) {
        $alt = $node->evaluate($this->getOption('alt'))->text();
        }

        $uuid = $this->getUuid($name, $file);

        if ($this->getOption('process_name')) {
        $name = ProcessController::apply($name, $this->getOption('process_name'), $node, $this->output);
        }

        $file = $this->getFileUrl($file);

        if ($this->getOption('process_file')) {
        $file = ProcessController::apply($file, $this->getOption('process_file'), $node, $this->output);
        }

        $this->entities[] = [
            'name' => $name,
            'file' => $file,
            'uuid' => $uuid,
            'alt'  => $alt,
        ];

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

    if (empty($name) || empty($file)) {
      throw new \Exception('Cannot parse media for '.$this->config['field']);
    }

    $this->crawler->each(
        function (Crawler $node) use (&$uuids) {
        $name = $node->attr($this->config['options']['name']);
        $file = $node->attr($this->config['options']['file']);
        $alt = $node->attr($this->getOption('alt'));
        $uuid = $this->getUuid($name, $file);

        if ($this->getOption('process_name')) {
        $name = ProcessController::apply($name, $this->getOption('process_name'), $node, $this->output);
        }

        $file = $this->getFileUrl($file);

        if ($this->getOption('process_file')) {
        $file = ProcessController::apply($file, $this->getOption('process_file'), $node, $this->output);
        }

        $this->entities[] = [
            'name' => $name,
            'file' => $file,
            'uuid' => $uuid,
            'alt'  => $alt,
        ];

        $uuids[] = $uuid;
        }
    );

    if (count($this->entities) > 0) {
      $this->output->mergeRow("media-{$type}", 'data', $this->entities, TRUE);
      $this->addValueToRow($uuids);
    }

  }//end processDom()


}//end class
