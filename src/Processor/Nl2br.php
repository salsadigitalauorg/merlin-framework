<?php

namespace Migrate\Processor;

/**
 * Convert new lines to <br />.
 *
 * @usage:
 *   nl2br: { }
 */
class Nl2br implements ProcessorInterface {


  /**
   * {@inheritdoc}
   */
  public function process($value) {
    $string = trim($value);
    return nl2br($string);

  }//end process()


}//end class
