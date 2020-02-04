<?php

namespace Merlin\Type;

/**
 * Output a static value to the migration JSON.
 *
 * @example
 *   field: site_id
 *   type: static_value
 *   options:
 *      value: 4
 */
class StaticValue extends TypeBase implements TypeInterface
{


    /**
     * {@inheritdoc}
     */
    public function process()
    {
        if (empty($this->config['options']['value'])) {
            throw new \Exception('Invalid static value configuration.');
        }

        $this->addValueToRow($this->config['options']['value']);

    }//end process()


}//end class
