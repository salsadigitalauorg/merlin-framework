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
class UnwrapLinks implements ProcessorInterface
{


  /**
   * Create a strip tags processor.
   */
   public function __construct(array $config)
   {
     $this->allowed_tags = isset($config['allowed_tags']) ? $config['allowed_tags'] : null;
     $this->remove_attr  = isset($config['remove_attr']) ? $config['remove_attr'] : [];
     $this->allowed_classes = isset($config['allowed_classes']) ? $config['allowed_classes'] : [];

  }//end __construct()


  /**
   * {@inheritdoc}
   */
  public function process($value)
  {

    $string = $value;

    if (substr($string, 1, 4) !== 'div') {
      // We add a wrapping DIV as DOMDocument::saveHTML() expects the document
      // fragment to be wrapped in the BODY element and we're preventing this
      // with the options passed to loadHTML. This prevents issues where the
      // first found tag is expanded and wraps the entire content.
      $string = "<div>{$string}</div>";
    }

    $dom = new \DOMDocument('1.0', 'utf-8');
    @$dom->loadHtml(
        mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'),
        (LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD)
    );

    $links = $dom->getElementsByTagName('a');

    for ($i = $links->length; --$i >= 0;) {
      // @var \DOMElement $n
      $n = $links->item($i);
      $href = $n->getAttribute('href');

      if ($n->hasChildNodes()) {
        $children = $n->childNodes;
        $nodes = [];
        for ($j = $children->length; --$j >= 0;) {
          $child = $children->item($j);
          $new_a = $n->cloneNode();
          // $new_a->appendChild($child->cloneNode(true));
          $new_a->appendChild($child);
          $nodes[] = $new_a;
        }

        $nodes = array_reverse($nodes);
        foreach ($nodes as $node) {
          $n->parentNode->appendChild($node);
        }

        // Remove our original (now empty) <a>.
        $n->parentNode->removeChild($n);
      }//end if
    }//end for

    $value = $dom->saveHTML();
    return substr(substr($value, 5), 0, -7);

  }//end process()


}//end class
