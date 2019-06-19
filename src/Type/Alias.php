<?php

namespace Migrate\Type;

/**
 * Generate an alias for the given row.
 *
 * @example:
 *   field: alias
 *   type: alias
 */
class Alias extends TypeBase implements TypeInterface {
  /**
   * {@inheritdoc}
   */
  public function process() {
    $this->addValueToRow(parse_url($this->crawler->getUri(), PHP_URL_PATH));
  }
}
