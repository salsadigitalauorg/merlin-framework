<?php

namespace Merlin\Parser;


class ArrayConfig extends ConfigBase
{


  /**
   * Build a configuration object.
   */
  public function __construct($data)
  {
    $this->data = $data;

  }//end __construct()


  public function parse() {
    // Nada.
    return $this;

  }//end parse()


}//end class
