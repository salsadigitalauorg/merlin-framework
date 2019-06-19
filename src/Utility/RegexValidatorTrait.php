<?php

namespace Migrate\Utility;

trait RegexValidatorTrait {

  /**
   * Validate a regular expression string.
   *
   * This ensures that we have a valid regex string and includes
   * testing the delimeters.
   *
   * @param string $regex_string
   *   The string to validate.
   *
   * @return bool
   *   If the regular expression is valid.
   */
  public function isValidRegex($regex_string) {
    $start = substr($regex_string, 0, 1);
    $end = substr($regex_string, -1);
    return $start === $end && @preg_match($regex_string, "") !== FALSE;
  }
}
