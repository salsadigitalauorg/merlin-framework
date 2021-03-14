<?php

namespace Merlin\Type;

use Symfony\Component\DomCrawler\Crawler;
use Merlin\Utility\MediaTrait;
use Merlin\Utility\ProcessorOptionsTrait;
use Merlin\ProcessController;
use Merlin\Exception\ElementNotFoundException;

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
class Media extends TypeMultiComponent implements TypeInterface
{

  use MediaTrait;
  use ProcessorOptionsTrait;


  /**
   * {@inheritdoc}
   */
  public function options($xpath=false)
  {
    return [
        'process_name' => false,
        'process_file' => false,
        'file'         => $xpath ? './@src' : 'src',
        'name'         => $xpath ? './@alt' : 'alt',
        'alt'          => $xpath ? './@alt' : 'alt',
    ];

  }//end options()


  /**
   * {@inheritdoc}
   */
  public function processXpath()
  {
    $uuids = [];
    extract($this->config['options']);
    $type = isset($this->config['options']['type']) ? $this->config['options']['type'] : 'media';
    $external_assets = isset($this->config['options']['external_assets']) ? $this->config['options']['external_assets'] : false;
    $full_details = ($this->config['options']['full_details'] ?? false);
    $extra_attr = ($this->config['options']['extra_attributes'] ?? []);

    $this->crawler->each(
        function (Crawler $node) use (&$uuids, $type, $external_assets, $extra_attr) {
          try {
            $file = $node->evaluate($this->getOption('file', true));
            assert($file->count() > 0);

            // Guzzle Uri hates Unicode chars and will ruin your day, here
            // we make sure any unicode chars are urlencoded before going in.
            // They come out urldecoded afterwards from getFileUrl().
            $file = preg_replace_callback(
                '/[^\x20-\x7f]/',
                function ($match) {
                  return urlencode($match[0]);
                },
                $file->text()
            );
            $file = $this->getFileUrl($file);
          } catch (\Exception $error) {
            throw new ElementNotFoundException();
          }

          try {
            $name = $node->evaluate($this->getOption('name', true));
            assert($name->count() > 0);
            $name = $name->text();
          } catch (\Exception $error) {
            $parts = explode("/", $file);
            $name = $parts[(count($parts) - 1)];
            $this->output->mergeRow("warning-{$type}", $file, ["Using fallback name {$name}"], true);
          }

          try {
            $alt = $node->evaluate($this->getOption('alt', true));
            assert($alt->count() > 0);
            $alt = $alt->text();
          } catch (\Exception $error) {
            $alt = null;
          }

          $uuid = $this->getUuid($name, $file);

          // Ignore if external assets are not permitted.
          if (!$external_assets) {
            if ($this->checkExternalUrl($file)) {
            $this->output->mergeRow(
                "warning-{$this->type}",
                $file,
                ["Skipping external asset {$file} found on {$this->crawler->getUri()}"],
                true
            );
              return;
            }
          }

          // Extra attributes that may be specified.
          $extra_res = [];
          if ($extra_attr) {
            if (!is_array($extra_attr)) {
              $extra_attr = [$extra_attr];
            }

            foreach ($extra_attr as $extra) {
              foreach ($extra as $k => $selector) {
                try {
                  $ex = $node->evaluate($selector);
                  assert($ex->count() > 0);
                  $ex = $ex->text();
                } catch (\Exception $error) {
                  $ex = null;
                }

                $extra_res[$k] = $ex;
              }
            }
          }

          $entity = [
              'file' => $file,
              'uuid' => (string) $uuid,
              'alt'  => $alt,
              'name' => $name,
          ];

          if (count($extra_res)) {
            foreach ($extra_res as $k => $v) {
              $entity[$k] = $v;
            }
          }

          $this->entities[] = $this->applyProcessors($entity);
          $uuids[] = $uuid;
        }
    );

    if (count($this->entities) > 0) {
      $this->output->mergeRow("media-{$type}", 'data', $this->entities, true);
      if ($full_details) {
        $this->addValueToRow($this->entities);
      } else {
        $this->addValueToRow($uuids);
      }
    }

  }//end processXpath()


  /**
   * {@inheritdoc}
   */
  public function processDom()
  {
    $uuids = [];
    extract($this->config['options']);
    $type = isset($this->config['options']['type']) ? $this->config['options']['type'] : 'media';
    $external_assets = isset($this->config['options']['external_assets']) ? $this->config['options']['external_assets'] : false;
    $full_details = ($this->config['options']['full_details'] ?? false);

    $this->crawler->each(
        function (Crawler $node) use (&$uuids, $type, $external_assets) {
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

          // Ignore if external assets are not permitted.
          if (!$external_assets) {
            if ($this->checkExternalUrl($file)) {
              $this->output->mergeRow("warning-{$this->type}", $file, ["Skipping external asset {$file}"], true);
              return;
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
      $this->output->mergeRow("media-{$type}", 'data', $this->entities, true);
      if ($full_details) {
        $this->addValueToRow($this->entities);
      } else {
        $this->addValueToRow($uuids);
      }
    }

  }//end processDom()


}//end class
