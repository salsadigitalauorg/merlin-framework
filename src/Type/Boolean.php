<?php

namespace Migrate\Type;

/**
 * Boolean type will output TRUE or FALSE based on condition.
 *
 * @example:
 *   field: field_checkbox
 *   type: boolean
 *   condition: //@xpath-selector
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

        $this->addValueToRow($element->count() > 0);

    }//end process()


}//end class
