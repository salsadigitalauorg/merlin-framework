<?php

namespace Migrate\Processor;

/**
 * Trim leading and trailing whitespace.
 *
 * @usage:
 *   trim: { }
 */
class Trim implements ProcessorInterface
{


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        return trim($value);

    }//end process()


}//end class
