<?php

namespace Merlin\Type;

use Symfony\Component\DomCrawler\Crawler;
use Merlin\Exception\ElementNotFoundException;

/**
 * Generate a structure for an accordion.
 *
 * @example:
 *   field: accordion_items
 *   selector: "#accordion-container"
 *   type: accordion
 *   options:
 *     title: .accordion-title
 *     body: .accordion-content
 */
class Accordion extends TypeBase implements TypeInterface
{


    /**
     * {@inheritdoc}
     */
    public function nullValue()
    {
        return [];

    }//end nullValue()


    /**
     * {@inheritdoc}
     */
    public function getSupportedSelectors()
    {
        return ['dom'];

    }//end getSupportedSelectors()


    /**
     * Extract the accordion items from the constricted DOM.
     *
     * @param array $options
     *   The configuration array.
     * @param array &$results
     *   The results array.
     */
    public static function extract($options, &$results)
    {
        return function (Crawler $node, $i) use ($options, &$results) {
            $title = isset($options['title']) ? $options['title'] : 'a';
            $body  = isset($options['body']) ? $options['body'] : 'div';

            if ($node->filter($title)->count() == 0 || $node->filter($body)->count() == 0) {
                // If we haven't been able to find the child elements we should skip this
                // there is likely something wrong with.
                throw new ElementNotFoundException("Unable to find {$title} or {$body} in accordion selector");
                return;
            }

            $results[] = [
                'accordion_title' => $node->filter($title)->html(),
                'accordion_body'  => $node->filter($body)->html(),
            ];
        };

    }//end extract()


    /**
     * {@inheritdoc}
     */
    public function processDom()
    {
        $field_name = $this->config['field'];
        $options    = $this->config['options'];
        $results    = [];

        if ($this->crawler->count() == 0) {
            $this->addValueToRow($this->nullValue());
        }

        $this->crawler->each(self::extract($options, $results));

        foreach ($results as &$result) {
            foreach ($result as &$value) {
                // Ensure the processes run over the values that we're pulling back.
                $value = $this->processValue($value);
                // @todo: Configuration.
                $result['format'] = 'rich_text';
            }
        }

        $this->addValueToRow($results);

    }//end processDom()


}//end class
