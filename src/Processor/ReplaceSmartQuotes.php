<?php

namespace Migrate\Processor;

/**
 * Replace 'smart quotes' with standard characters.
 *
 * @usage:
 *   replace_smart_quotes: { }
 */
class ReplaceSmartQuotes implements ProcessorInterface
{


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        $chr_map = [
            "\xC2\x82"     => "'",
            "\xC2\x84"     => '"',
            "\xC2\x8B"     => "'",
            "\xC2\x91"     => "'",
            "\xC2\x92"     => "'",
            "\xC2\x93"     => '"',
            "\xC2\x94"     => '"',
            "\xC2\x9B"     => "'",
            "\xC2\xAB"     => '"',
            "\xC2\xBB"     => '"',
            "\xE2\x80\x98" => "'",
            "\xE2\x80\x99" => "'",
            "\xE2\x80\x9A" => "'",
            "\xE2\x80\x9B" => "'",
            "\xE2\x80\x9C" => '"',
            "\xE2\x80\x9D" => '"',
            "\xE2\x80\x9E" => '"',
            "\xE2\x80\x9F" => '"',
            "\xE2\x80\xB9" => "'",
            "\xE2\x80\xBA" => "'",
        ];

        $chr = array_keys($chr_map);
        $rpl = array_values($chr_map);
        $value = str_replace($chr, $rpl, html_entity_decode($value, ENT_QUOTES, "UTF-8"));

        return $value;

    }//end process()


}//end class
