<?php

namespace Merlin;

use Symfony\Component\DomCrawler\Crawler;
use Merlin\Output\OutputInterface;

class ProcessController {


  /**
   * Get a processor.
   *
   * @param array $config
   *   The configuration array to build a process object.
   * @param Symfony\Component\DomCrawler\Crawler $crawler
   *   The DOM.
   * @param Merlin\Output\OutputInterface $output
   *   The output file.
   * @param string $processor
   *   (Optional) The process name
   *
   * @return Merlin\Processor\ProcessorInterface
   *   An instantiated processor object.
   */
  public static function get(array $config, Crawler $crawler, OutputInterface $output, $processor=NULL) {
    if (empty($processor)) {
      $processor = $config['processor'];
      unset($config['processor']);
    }

    $processor = str_replace('_', '', ucwords($processor, '_'));
    $class = "Merlin\\Processor\\".ucfirst($processor);

    if (!class_exists($class)) {
      throw new \Exception("No handler for {$processor}: ".json_encode($config));
    }

    return new $class($config, $crawler, $output);

  }//end get()


  /**
   * Get all processors from a configuration array.
   *
   * @param array $config
   *   The config array for a processor.
   * @param Symfony\Component\DomCrawler\Crawler $crawler
   *   The DOM.
   * @param Merlin\Output\OutputInterface $output
   *   The output file.
   *
   * @return array
   *   A list of processors.
   */
  public static function getAll(array $config, Crawler $crawler, OutputInterface $output) {
    $processors = [];

    foreach ($config as $processor => $pconf) {
      if (!is_numeric($processor)) {
        $pconf['processor'] = $processor;
      }

      $processors[] = self::get($pconf, $crawler, $output);
    }

    return $processors;

  }//end getAll()


  /**
   * Apply all processors from a configuration array.
   *
   * @param string $value
   *   The value to apply the processors to.
   * @param array $confg
   *   The configuration array for all processors to apply.
   * @param Symfony\Component\DomCrawler\Crawler $crawler
   *   The DOM.
   * @param Merlin\Output\OutputInterface $output
   *   The output file.
   *
   * @return string
   *   A value with the processors applied.
   */
  public static function apply($value, array $config, Crawler $crawler, OutputInterface $output) {
    foreach (self::getAll($config, $crawler, $output) as $processor) {
      $value = $processor->process($value);
    }

    return $value;

  }//end apply()


}//end class
