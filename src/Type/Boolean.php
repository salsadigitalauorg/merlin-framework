<?php

namespace Migrate\Type;

/**
 * Boolean type will output TRUE or FALSE based on condition.
 *
 * @example:
 *   field: field_checkbox
 *   type: boolean
 *   selector: //@xpath-selector
 *   options:
 *     success_value: 'yes'
 *     fail_value: 'no'
 */
class Boolean extends TypeBase implements TypeInterface
{


    /**
     * {@inheritdoc}
     */
    public function process()
    {
        extract($this->config);

        // Try selecting the element via xpath.
        $element = @$this->crawler->evaluate($selector);
        if (is_array($element)) {
            $element = $this->crawler->filter($selector);
        }

        if (isset($options['success_value']) && $element->count() > 0) {
          return $this->addValueToRow($options['success_value']);
        }

        if (isset($options['fail_value']) && $element->count() == 0) {
          return $this->addValueToRow($options['fail_value']);
        }

        $this->addValueToRow($element->count() > 0);

    }//end process()


}//end class
