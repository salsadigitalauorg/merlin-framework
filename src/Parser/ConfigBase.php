<?php

namespace Migrate\Parser;

abstract class ConfigBase implements ParserInterface {

  /**
   * The path to the configuration source.
   *
   * @var string
   */
  protected $source;

  /**
   * The parsed data for the configuration object.
   *
   * @var array
   */
  protected $data;

  /**
   * A list of totals.
   *
   * @var array
   */
  protected $totals;

  /**
   * Build a configuration object.
   */
  public function __construct($source) {
    $this->source = $source;
    $this->parse();
  }

  /**
   * {@inheritdoc}
   */
  protected function parse() {
    if (!file_exists($this->source)) {
      throw new \Exception("Invalid source file provided: Cannot locate $this->source");
    }
    $data = \Spyc::YAMLLoad($this->source);

    if (empty($data['entity_type'])) {
      throw new \Exception("Invalid source file: No content type found in the source file");
    }

    if (empty($data['mappings'])) {
      throw new \Exception("Invalid source file: No mappings found in the source file");
    }

    $this->data = $data;
    $this->totals['mappings'] = count($data['mappings']);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    return $this->parse();
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->source = $source;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    if (empty($this->data)) {
      $this->parse();
    }
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapping() {
    if ($this->totals['mappings'] >= 0) {
      $this->totals['mappings']--;
      return array_shift($this->data['mappings']);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    if ($this->totals['urls'] > 0) {
      $this->totals['urls']--;
      return $this->data['domain'] . array_shift($this->data['urls']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return !empty($this->data[$key]) ? $this->data[$key] : FALSE;
  }

}
