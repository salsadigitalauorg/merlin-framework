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
 *     allowed_classes:
 *       - class1
 *       - wildcard-* matching
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
        $this->allowed_classes = isset($config['allowed_classes']) ? $config['allowed_classes'] : [];

    }//end __construct()


    private function wildcard_match($source, $pattern) {
      $pattern = preg_quote($pattern,'/');
      $pattern = str_replace('\*' , '.*', $pattern);
      return preg_match('/^'.$pattern.'$/i' , $source);

    }//end wildcard_match()


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

        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Change saveHTML function if use below.
//        @$dom->loadHtml(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'), (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD));
        @$dom->loadHTML($string, (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD));


        $xpath = new \DOMXPath($dom);

        // Keep classes.
        if (!empty($this->allowed_classes)) {
          $nodes_with_class = $xpath->query('//*[@class]');
          foreach ($nodes_with_class as $n) {
            // @var \DOMNode $n
            if ($n->nodeType === XML_ELEMENT_NODE) {
              // @var \DOMElement $n
              $class = $n->getAttribute("class");
              $classes = explode(" ", $class);
              $nc = [];
              foreach ($classes as $c) {
                foreach ($this->allowed_classes as $allowed_class) {
                  if (strstr($allowed_class, '*')) {
                    if ($this->wildcard_match($c, $allowed_class)) {
                      $nc[] = $c;
                    }
                  } else {
                    if (trim($c) == trim($allowed_class)) {
                      $nc[] = $c;
                    }
                  }
                }
              }

              $n->setAttribute("class", implode(" ", $nc));
            }//end if
          }//end foreach

          // Remove 'class' from the remove attr list if we are keeping classes.
          if (($key = array_search('class', $this->remove_attr)) !== false) {
            unset($this->remove_attr[$key]);
          }
        }//end if

        // Remove attributes.
        foreach ($this->remove_attr as $attr) {
            $node_list = $xpath->query('//*[@'.$attr.']');
            foreach ($node_list as $node) {
                $node->removeAttribute($attr);
            }
        }

        // Use with original loadHtml() function
//        $value = $dom->saveHTML();
//      return substr(substr($value, 5), 0, -7);


      $value = utf8_decode($dom->saveHTML($dom->documentElement));

      // Replace ridiculous unicode spaces?
      $value = str_replace("\xc2\xa0", ' ', $value);

      return substr(substr($value, 5), 0, -6);


    }//end process()


}//end class
