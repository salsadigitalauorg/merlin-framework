<?php

namespace Migrate\Type;

use Migrate\ProcessController;

/**
 * Define a type that produces more than one output.
 *
 * @example
 *    type: link
 *    processors:
 *      link: []
 *      text:
 *        - replace:
 *              pattern: /w+/
 */
abstract class TypeMultiComponent extends TypeBase {


    /**
     * Process the field values.
     */
    public function applyProcessors(&$output)
    {
        foreach ($output as $key => &$value) {
            if (is_array($value)) {
                return $this->processValue($value);
            }

            if (isset($this->config['processors'][$key])) {
                $value = ProcessController::apply(
                    $value,
                    $this->config['processors'][$key],
                    $this->crawler,
                    $this->output
                );
            }
        }

        return $output;

    }//end processValue()

}
