<?php

namespace Merlin\Processor;

/**
 * Convert the character encoding of the row.
 *
 * @usage:
 *   convert_encoding:
 *      to_encoding: 'UTF-8'
 *      from_encoding: 'HTML-ENTITIES'
 */
class ConvertEncoding implements ProcessorInterface
{


    /**
     * Build an instance of the encoding conversion processor.
     */
    public function __construct(array $config)
    {
        $this->to_encoding   = !empty($config['to_encoding']) ? $config['to_encoding'] : 'UTF-8';
        $this->from_encoding = !empty($config['from_encoding']) ? $config['from_encoding'] : null;

    }//end __construct()


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        return mb_convert_encoding($value, $this->to_encoding, $this->from_encoding);

    }//end process()


}//end class
