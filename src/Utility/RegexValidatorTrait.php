<?php

namespace Merlin\Utility;

trait RegexValidatorTrait
{


    /**
     * Validate a regular expression string.
     *
     * This ensures that we have a valid regex string and includes
     * testing the delimeters.
     *
     * @param string $regex
     *   The string to validate.
     *
     * @return bool
     *   If the regular expression is valid.
     */
    public function isValidRegex($regex)
    {
        $start = substr($regex, 0, 1);
        $end   = substr($regex, -1);
        return $start === $end && @preg_match($regex, "") !== false;

    }//end isValidRegex()


}
