<?php

namespace Merlin\Processor;

use Merlin\Utility\Callback;
use Symfony\Component\DomCrawler\Crawler;
use Merlin\Output\OutputInterface;

/**
 * Run a callback function over a value.
 *
 * @usage:
 *   function_process: |
 */
class FunctionProcess extends ProcessorOutputBase
{

    /**
     * A function string that can be evaluated.
     *
     * @var string
     */
    protected $fn;


    /**
     * Build an instance of the function processor.
     */
    public function __construct($options, Crawler $crawler, OutputInterface $output)
    {
        parent::__construct($options, $crawler, $output);
        $this->fn = $options['function'];

    }//end __construct()


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        if (!empty($value)) {
            $value = Callback::getResult($this->fn, $value, $this->crawler);
        }

        return $value;

    }//end process()


}//end class
