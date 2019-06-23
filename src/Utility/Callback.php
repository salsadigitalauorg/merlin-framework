<?php

namespace Migrate\Utility;

/**
 * Utility to allow callbacks to be passed from YML.
 */
class Callback
{


    /**
     * Register a function string in the
     */
    public static function getResult($fnString, ...$params)
    {
        // Void function as the default.
        $callback = function () {
        };

        if (substr($fnString, -1) !== ';') {
            $fnString .= ';';
        }

        // Register the callback.
        eval('$callback = '.$fnString);
        return call_user_func_array($callback, $params);

    }//end getResult()


}//end class
