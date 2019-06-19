<?php

namespace Migrate\Processor;

/**
 * Remove whitespace characters.
 *
 * @usage:
 *   whitespace: { }
 */
class Whitespace implements ProcessorInterface {
  /**
   * {@inheritdoc}
   */
  public function process($value) {
    $value = preg_replace("/(\n|\t|\r)/", '', $value);
    $value = preg_replace('/ {2,}/', '', $value);
    return trim($value);
  }
}
