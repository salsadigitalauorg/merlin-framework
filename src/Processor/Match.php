<?php

namespace Merlin\Processor;

/**
 * Match part of the return value.
 *
 * @usage:
 *   match:
 * ((?:[1-9][0-9]*|0)(?:\/[1-9][0-9]*)?)
 */
class Match implements ProcessorInterface
{

    /**
     * The regex pattern to match on.
     *
     * @var string
     */
    protected $pattern;


    /**
     * Build an instance of the processor.
     *
     * @param string $pattern
     *   The regex pattern to match.
     */
    public function __construct($pattern)
    {
        $this->pattern = is_array($pattern) ? reset($pattern) : $pattern;

    }//end __construct()


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        preg_match("/$this->pattern/", $value, $matches);

        if (isset($matches[1])) {
            return $matches[1];
        }

        return null;

    }//end process()


}//end class
