<?php

namespace Merlin\Utility;

use Ramsey\Uuid\Uuid;

/**
 * Utility functions related to UUID.
 */
class MerlinUuid
{


    /**
     * Generate a Merlin-standard UUID.
     *
     * Strips http[s]://www from strings prior to generation.
     * Makes string lowercase prior to generation.
     * @param string $string
     * @return string
     */
    public static function getUuid($string)
    {
      $string = strtolower($string);
      $string = preg_replace('/^(https?:\/\/)?(www\.)?/', '', $string);
      return Uuid::uuid3(Uuid::NAMESPACE_DNS, $string)->toString();

    }//end getUuid()


}//end class
