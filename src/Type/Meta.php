<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Utility\ProcessorOptionsTrait;

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
class Meta extends TypeBase implements TypeInterface
{

    use ProcessorOptionsTrait;


    /**
     * {@inheritdoc}
     */
    public function getSupportedSelectors()
    {
        return ['dom'];

    }//end getSupportedSelectors()


    /**
     * {@inheritdoc}
     */
    public function options($xpath=false)
    {
        return ['attr' => 'name'];

    }//end options()


    /**
     * {@inheritdoc}
     */
    public function processDom()
    {
        $value = $this->getOption('value');

        if (empty($value)) {
            throw new \Exception('Meta requries the value option.');
        }

        $metatags = $this->crawler->filter('meta');
        $meta     = null;

        $metatags->each(
            function (Crawler $node) use ($value, &$meta) {
                if ($node->attr($this->getOption('attr')) == $value) {
                    $meta = $node;
                }
            }
        );

        if ($meta) {
            $this->addValueToRow($meta->attr('content'));
        }

    }//end processDom()


}//end class
