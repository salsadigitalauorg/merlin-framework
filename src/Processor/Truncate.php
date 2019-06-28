<?php

namespace Migrate\Processor;

/**
 * Truncate the value to a given lenth.
 *
 * @example:
 *    truncate: 256
 */
class Truncate implements ProcessorInterface {

  /**
   * The length to turncate to.
   *
   * @var integer
   */
  protected $length;


  public function __construct($length) {
    $this->length = (int) is_array($length) ? reset($length) : $length;

  }//end __construct()


  /**
   * {@inheritdoc}
   */
  public function process($value) {
    return substr($value, 0, $this->length);

  }//end process()


}//end class
