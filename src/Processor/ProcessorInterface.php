<?php

namespace Migrate\Processor;

/**
 * Row value processor interface.
 *
 * The processors operate on the value selected by the Type plugin and perform
 * additional massaging of the values pulled back by those types.
 */
interface ProcessorInterface {

  /**
   * Process the field value.
   *
   * This attempts to process the current field value
   */
  public function process($value);

}
