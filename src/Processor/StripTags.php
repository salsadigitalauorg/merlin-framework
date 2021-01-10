<?php

namespace Merlin\Processor;

/**
 * Strip tags and remove attributes from the markup.
 *
 * @usage:
 *   strip_tags:
 *     allowed_tags: <h1><h2><h3><br><img>
 *     remove_attr:
 *       - id
 *       - class
 *       - style
 */
class StripTags implements ProcessorInterface
{

    /**
     * @var string $allowed_tags
     */
    protected $allowed_tags;


    /**
     * Create a strip tags processor.
     */
    public function __construct(array $config)
    {
        $this->allowed_tags = isset($config['allowed_tags']) ? $config['allowed_tags'] : null;
        $this->remove_attr  = isset($config['remove_attr']) ? $config['remove_attr'] : [];

    }//end __construct()


    /**
     * {@inheritdoc}
     */
    public function process($value)
    {
        $string = strip_tags($value, $this->allowed_tags);

        if (substr($string, 1, 4) !== 'div') {
            // We add a wrapping DIV as DOMDocument::saveHTML() expects the document
            // fragment to be wrapped in the BODY element and we're preventing this
            // with the options passed to loadHTML. This prevents issues where the
            // first found tag is expanded and wraps the entire content.
            $string = "<div>{$string}</div>";
        }

        $dom = new \DOMDocument('1.0', 'utf-8');
        @$dom->loadHtml(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'), (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD));

        $xpath = new \DOMXPath($dom);
        foreach ($this->remove_attr as $attr) {
            $node_list = $xpath->query('//*[@'.$attr.']');
            foreach ($node_list as $node) {
                $node->removeAttribute($attr);
            }
        }

        $value = $dom->saveHTML();
        return substr(substr($value, 5), 0, -7);

    }//end process()


}//end class
