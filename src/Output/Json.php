<?php

namespace Migrate\Output;

/**
 * Manage the output in json.
 */
class Json extends OutputBase {

  protected $ext = 'json';

  /**
   * {@inheritdoc}
   */
  public function toString(array $data = []) {
    return json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
  }

}
