<?php

namespace Migrate\Type;

/**
 * Generate an alias for the given row.
 *
 * @example:
 *   field: alias
 *   type: alias
 */
class Alias extends TypeBase implements TypeInterface
{


    /**
     * {@inheritdoc}
     */
    public function process()
    {

        $alias = parse_url($this->crawler->getUri(), PHP_URL_PATH);
        $alias = $this->processValue($alias);
        $this->addValueToRow($alias);

    }//end process()


}//end class
