<?php

namespace Merlin\Processor;

use Merlin\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * A processor that requires context of the crawler and the output object.
 *
 * This is a more complex processor that can parse the values of a type plugin
 * and alter the output based on HTML structure. It also will have access to
 * $output so it can create additional files based on its parsing rules.
 */
abstract class ProcessorOutputBase implements ProcessorInterface
{

    /** @var array */
    public $config;

    /** @var \Merlin\Output\OutputInterface  */
    public $output;

    /** @var \Symfony\Component\DomCrawler\Crawler  */
    public $crawler;


    /**
     * Construct an instance of a processor with output.
     *
     * @param                                       $config
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @param \Merlin\Output\OutputInterface       $output
     */
    public function __construct($config, Crawler $crawler, OutputInterface $output)
    {
        $this->config  = $config;
        $this->crawler = $crawler;
        $this->output  = $output;

    }//end __construct()


}//end class
