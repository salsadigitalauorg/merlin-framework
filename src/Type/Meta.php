<?php

namespace Merlin\Type;

use Symfony\Component\DomCrawler\Crawler;
use Merlin\Utility\ProcessorOptionsTrait;
use Merlin\ProcessController;

/**
 * Extract meta tags.
 *
 * @example:
 *  field: field_name
 *  type: meta
 *  options:
 *    value: keywords
 *    attr: property
 */
class Meta extends TypeBase implements TypeInterface {

  use ProcessorOptionsTrait;


  /**
   * {@inheritdoc}
   */
  public function getSupportedSelectors() {
    return ['dom'];

  }//end getSupportedSelectors()


  /**
   * {@inheritdoc}
   */
  public function options($xpath=FALSE) {
    return ['attr' => 'name'];

  }//end options()


  /**
   * {@inheritdoc}
   */
  public function processDom() {
    $value = $this->getOption('value');

    if (empty($value)) {
      throw new \Exception('Meta requries the value option.');
    }

    $metatags = $this->crawler->filter('meta');
    $meta = null;

    $metatags->each(
        function(Crawler $node) use ($value, &$meta) {
        if ($node->attr($this->getOption('attr')) == $value) {
        $meta = $node;
        }
        }
    );

    if ($meta) {
      $value = $meta->attr('content');

      if (isset($this->config['processors'])) {
        $value = ProcessController::apply(
            $value,
            $this->config['processors'],
            $this->crawler,
            $this->output
        );
      }

      $this->addValueToRow($value);
    }

  }//end processDom()


}//end class
