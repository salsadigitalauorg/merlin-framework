<?php

namespace Merlin\Processor;

/**
 * Remove whitespace characters.
 *
 * @usage:
 *   whitespace: { }
 */
class Whitespace implements ProcessorInterface
{


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        $value = preg_replace("/(\n|\t|\r)/", '', $value);
        $value = preg_replace('/ {2,}/', ' ', $value);
        $value = preg_replace('/[\x{200B}-\x{200D}]/u', '', $value);
        return trim($value);

    }//end process()


}//end class
