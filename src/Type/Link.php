<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Utility\ElementTrait;

/**
 * Generate link output from a selector.
 *
 * @example:
 *   field: field_related_links
 *   selector: 'ul.related-link-list li'
 *   type: link
 *   options:
 *     link: href # Attribute to determine the URL
 *     text: a # Element that contains the text
 */
class Link extends TypeBase implements TypeInterface
{

    use ElementTrait;


    /**
     * Get an option from the configuration with defaults.
     *
     * @param string $opt
     *   The key to access.
     *
     * @return string
     *   The option value.
     */
    public function getOption($opt, $xpath=false)
    {
        $defaults = [
            'link'                => $xpath ? './a/@href' : 'href',
            'text'                => $xpath ? './a/text()' : 'a',
            'internal_identifier' => 'internal:',
        ];

        $options = !empty($this->config['options']) ? $this->config['options'] : [];
        $options = array_merge($defaults, $options);
        return array_key_exists($opt, $options) ? $options[$opt] : false;

    }//end getOption()


    /**
     * Determine if a given path is relative or absolute.
     *
     * @param string $uri
     *   The uri to tet.
     *
     * @return bool
     *   If the uri is relative or not.
     */
    public function isRelativeUri($uri='')
    {
        return substr($uri, 0, 1) === '/';

    }//end isRelativeUri()


    /**
     * {@inheritdoc}
     */
    public function processXpath()
    {
        $results = [];

        $this->crawler->each(
            function (Crawler $node) use (&$results) {
                $text = $node->evaluate($this->getOption('text', 1));
                $link = $node->evaluate($this->getOption('link', 1));

                if (!$this->isValidElement($text) || !$this-> isValidElement($link)) {
                    return;
                }

                $text = $text->text();
                $link = $link->text();

                $text = $this->processValue($text);
                $link = $this->processValue($link);

                // Validate links - allow anchor links.
                if (!parse_url($link) && (substr($link, 0, 1) != '#')) {
                    $this->output->mergeRow('error-unhandled', $link, ['Link value does not validate'], true);
                    return;
                }

                if ($this->isRelativeUri($link)) {
                    $link = $this->getOption('internal_identifier').$link;
                }

                $results[] = [
                    'link' => $link,
                    'text' => $text,
                ];
            }
        );

        $this->addValueToRow($results);

    }//end processXpath()


    /**
     * {@inheritdoc}
     */
    public function processDom()
    {
        $results = [];

        $this->crawler->each(
            function (Crawler $node) use (&$results) {

                $text = $node->filter($this->getOption('text'));
                $link = $node->filter('a');

                if (!$this->isValidElement($text) || !$this->isValidElement($link)) {
                    return;
                }

                $text = $text->text();
                $link = $link->attr($this->getOption('link'));

                // Validate links - allow anchor links.
                if (!parse_url($link) && (substr($link, 0, 1) != '#')) {
                    $this->output->mergeRow('error-unhandled', $link, ['Link value does not validate'], true);
                    return;
                }

                if ($this->isRelativeUri($link)) {
                    $link = $this->getOption('internal_identifier').$link;
                }

                $results[] = [
                    'link' => $link,
                    'text' => $text,
                ];
            }
        );

        if (empty($results)) {
            throw new \Exception('Unable to find links');
        }

        foreach ($results as &$result) {
            $result['text'] = $this->processValue($result['text']);
            $result['link'] = $this->processValue($result['link']);
        }

        $this->addValueToRow($results);

    }//end processDom()


}//end class
