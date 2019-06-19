<?php

namespace Migrate\Processor;

use Migrate\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * A processor that requires context of the crawler and the output object.
 *
 * This is a more complex processor that can parse the values of a type plugin
 * and alter the output based on HTML structure. It also will have access to
 * $output so it can create additional files based on its parsing rules.
 */
abstract class ProcessorOutputBase implements ProcessorInterface {

  /**
   * Construct an instance of a processor with output.
   */
  public function __construct($config, Crawler $crawler,  OutputInterface $output) {
    $this->config = $config;
    $this->crawler = $crawler;
    $this->output = $output;
  }
}
