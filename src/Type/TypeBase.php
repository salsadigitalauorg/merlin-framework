<?php

namespace Merlin\Type;

use Merlin\Utility\Callback;
use Symfony\Component\DomCrawler\Crawler;
use Merlin\Output\OutputInterface;
use Merlin\Exception\ElementNotFoundException;
use Merlin\Exception\ValidationException;
use Symfony\Component\CssSelector\Exception\SyntaxErrorException;
use Merlin\ProcessController;

/**
 * Field type base.
 */
abstract class TypeBase implements TypeInterface {


  /**
   * The crawler object.
   *
   * @var Symfony\Component\DomCrawler\Crawler
   */
  protected $crawler;


  /**
   * The output object.
   *
   * @var Merlin\Output\OutputInterface
   */
  protected $output;


  /**
   * The row.
   *
   * @var array
   */
  protected $row;


  /**
   * The configuration array.
   *
   * @var array
   */
  protected $config;

  /**
   * The process conntroller.
   *
   * @var Merlin\ProcessControrller
   */
  protected $processors;


  /**
   * Build a field type parser.
   *
   * @param Symfony\Component\DomCrawler\Crawler $crawler
   *   The element filtered to the selector.
   * @param Merlin\Output\OutputInterface
   *   The output object.
   * @param mixed &$row
   *   The row.
   * @param array $config
   *   The configuration for the field.
   */
  public function __construct(Crawler $crawler, OutputInterface $output, $row, array $config=[])
  {
    $this->crawler = $crawler;
    $this->output = $output;
    $this->row = $row;
    $this->config = $config;
    $this->processors = new ProcessController;

  }//end __construct()


  /**
   * Add the value to the row.
   *
   * @return this
   */
  public function addValueToRow($value)
  {
    extract($this->config);

    if (empty($field)) {
      throw new ValidationException("'Field' is missing from the configuration");
    }

    $this->row->{$field} = $value;

    return $this;

  }//end addValueToRow()


  /**
   * Handle processing the value.
   *
   * @param mixed $value
   *   The value to process
   *
   * @return mixed
   *   The return value after processors have been applied.
   */
  public function processValue($value)
  {

    if (!empty($this->config['processors'])) {
    return $this->processors::apply(
        $value,
        $this->config['processors'],
        $this->crawler,
        $this->output
    );
    }

    return $value;

  }//end processValue()


  /**
   * {@inheritdoc}
   */
  public function getSupportedSelectors()
  {
    return [
        'dom',
        'xpath',
    ];

  }//end getSupportedSelectors()


  /**
   * {@inheritdoc}
   */
  public function supports($type)
  {
    return in_array($type, $this->getSupportedSelectors());

  }//end supports()


  /**
   * {@inheritdoc}
   */
  public function nullValue()
  {
    return [];

  }//end nullValue()


  /**
   * {@inheritdoc}
   *
   * @throws \Merlin\Exception\ElementNotFoundException
   * @throws \Merlin\Exception\ValidationException
   */
  public function process() {

    $selector = isset($this->config['selector']) ? $this->config['selector'] : FALSE;

    if (!is_array($selector)) {
      $selector = [$selector];
    }

    $failedSelectors = [];
    $selectorCount = count($selector);

    foreach ($selector as $currentSelector) {
      if (empty(trim($currentSelector))) {
        $this->output->mergeRow("warning-empty-selector", $this->config['field'], ["Selector missing!"], true);
      }

      $lastCrawler = $this->crawler;

      $results = $this->processSelector($currentSelector);

      if ($results === false) {
        $this->crawler = $lastCrawler;
        $failedSelectors[] = $currentSelector;
      } else {
        // We found our first match selector, so stop lookin'.
        break;
      }
    }//end foreach

    // If none of the specified selectors matched for this field, report as error.
    if ($failedSelectors && count($failedSelectors) === $selectorCount) {
      $field = ($this->config['field'] ?? null);
      $fieldLabel = " for field '{$field}'";
      throw new ElementNotFoundException("Failed to find any multiple selector{$fieldLabel}: ".implode("; ", $failedSelectors));
    }

  }//end process()


  /**
   * Processes the current selector.
   * @param $selector
   *
   * @return bool
   * @throws \Merlin\Exception\ValidationException
   */
  private function processSelector($selector)
  {
    $xpath = FALSE;
    $element = $this->crawler;
    $sourceUri = $element->getUri();

    if ($this->supports('xpath') && $selector) {
      $element = @$this->crawler->evaluate($selector);
      $xpath = TRUE;

      if ($element instanceof Crawler && $element->count() == 0) {
        // The DOMCrawler can return an empty DOMNode list or an array
        // if the selector doesn't match something that xpath can evaluate.
        $xpath = FALSE;
        $element = $this->crawler;
      }

      if (is_array($element)) {
        // If the evaluate method returns an array we don't have a valid xpath
        // selector so we reset these values and continue forward!
        $xpath = FALSE;
        $element = $this->crawler;
      }
    }

    if ($this->supports('xpath') && !$selector && !empty($this->config['xpath'])) {
      // If a selector hasn't been set we can still allow types to override the xpath
      // option and use the xpath processing by sepcifing the xpath flag in the config.
      $xpath = TRUE;
    }

    if (!$xpath && $selector) {
      // If we haven't found an element with xpath lets try the DOM.
      try {
        $element = $this->crawler->filter($selector);
      } catch (SyntaxErrorException $syntax) {
        // If the domcrawler couldn't filter to the selector, default to an
        // empty crawler.
        $element = new Crawler();
      }
    }

    $this->crawler = $element;

    if ($this->crawler->count() == 0) {
      if (!empty($this->config['options']['allow_null'])) {
        $this->row->{$this->config['field']} = $this->nullValue();
      }

      if (!empty($this->config['options']['mandatory'])) {
        $this->row->mandatory_fail = TRUE;
        $this->output->mergeRow("warning-mandatory", $this->config['field'], ["Mandatory element using selector '{$selector}' missing in url: {$sourceUri}"], true);
      }

      if (isset($this->config['default'])) {
        $this->processDefault();
      }

      return false;
    }

    if ($xpath) {
      $this->processXpath();
    } else {
      $this->processDom();
    }

    return true;

  }//end processSelector()


  /**
   * processXpath.
   *
   * This is defined as an empty method on the base class so that it can be overidden in child classes.
   */
  public function processXpath()
  {

  }//end processXpath()


  /**
   * processDom.
   *
   * This is defined as an empty method on the base class so that it can be overidden in child classes.
   */
  public function processDom()
  {

  }//end processDom()


  /**
   * processDefault.
   *
   * Sets any default if defined and selector was not found.  Some types may override default below.
   *
   * @throws \Merlin\Exception\ValidationException
   */
  public function processDefault() {

    if (is_array($this->config['default']) && key_exists('function', $this->config['default'])) {
      $value = Callback::getResult($this->config['default']['function'], $this->crawler);
    } else if (is_array($this->config['default']) && key_exists('fields', $this->config['default'])) {
      $results = [];
      foreach ($this->config['default']['fields'] as $field => $data) {
        $results[$field] = $data;
      }

      $value = $results;
    } else {
      $value = $this->config['default'];
    }

    $this->addValueToRow($value);

  }//end processDefault()


}//end class
