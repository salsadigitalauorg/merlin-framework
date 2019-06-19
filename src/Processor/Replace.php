<?php

namespace Migrate\Processor;

/**
 * Replace a pattern in the row.
 *
 * Setting replace to false will replace with an empty string.
 *
 * @usage:
 *   replace:
 *     pattern: \d+
 *     replace: false
 */
class Replace implements ProcessorInterface {

  /**
   * Build an instance of the repalce processor.
   */
  public function __construct(array $config) {
    $this->pattern = $config['pattern'];
    $this->replace = !empty($config['replace']) ? $config['replace'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function process($value) {
    $string = preg_replace("#$this->pattern#", $this->replace, $value);
    return $string;
  }
}
