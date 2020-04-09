<?php

namespace Merlin\Processor;

/**
 * Explode a string so that it will have an array representation in the output.
 *
 * This will convert $value into an array, as a result it will break
 * compatibility with other processors and should be used last in the chain.
 *
 * @TODO:
 *   - Processor controller support arary or string values.
 *
 * @usage:
 *    -
 *      processor: explode
 *      delimiter: ","
 *      trim: false
 */

class Explode implements ProcessorInterface {


  public function __construct(array $config) {
    $this->delimiter = !empty($config['delimiter']) ? $config['delimiter'] : ',';
    $this->trim = !empty($config['trim']);

  }//end __construct()


  /**
   * {@inheritdoc}
   */
  public function process($value) {
    $value = explode($this->delimiter, $value);
    if ($this->trim) {
      $value = array_map('trim', $value);
    }

    return $value;

  }//end process()


}//end class
