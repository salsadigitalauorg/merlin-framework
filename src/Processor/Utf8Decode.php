<?php

namespace Migrate\Processor;

/**
 * Attempt to decode UTF-8 characters in the string.
 *
 * @usage:
 *    utf8_decode: { }
 */
class Utf8Decode implements ProcessorInterface
{


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        $trans = get_html_translation_table(HTML_ENTITIES);
        unset($trans["\""], $trans["<"], $trans[">"]);
        return strtr($value, $trans);

    }//end process()


}//end class
