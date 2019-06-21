<?php

namespace Migrate\Processor;

/**
 * Dcecode any HTML entities of the row.
 *
 * @usage:
 *   html_entity_decode: { }
 */
class HtmlEntityDecode implements ProcessorInterface
{


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        return html_entity_decode($value);

    }//end process()


}//end class
