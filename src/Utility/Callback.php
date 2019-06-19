<?php

namespace Migrate\Utility;

/**
 * Utility to allow callbacks to be passed from YML.
 */
class Callback {

  /**
   * Register a function string in the
   */
  public static function getResult($fn_string, ...$params) {
    // Void function as the default.
    $callback = function () {};

    if (substr($fn_string, -1) !== ';') {
      $fn_string .= ';';
    }

    // Register the callback.
    eval('$callback = ' . $fn_string);
    return call_user_func_array($callback, $params);
  }

}
