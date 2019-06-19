<?php

namespace Migrate\Utility;

trait ProcessorOptionsTrait {

  /**
   * Provide a default set of options.
   *
   * @return array
   */
  public function options($xpath = FALSE) {
    return [];
  }

  /**
   * Get a specific option value.
   *
   * @param string $key
   *   The option key.
   */
  public function getOption($key) {
    $options = !empty($this->config['options']) ? $this->config['options'] : [];
    $options = array_merge($this->options(), $options);
    return array_key_exists($key, $options) ? $options[$key] : FALSE;
  }

}
