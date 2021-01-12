<?php

namespace Merlin\Processor;

/**
 * ALPHA Unwrap elements in link and insert link within element where appropriate.
 *
 * YMMV using this, it's rough and ready but works for simple markup.
 * 
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


  private function remove_empty($html) {
//    $html = str_replace('&nbsp;', ' ', $html);
    $html = str_replace('</drupal-entity>', '###_REMOVE_ME_###</drupal-entity>', $html);
    do {
      $tmp = $html;
      $html = preg_replace(
        '#<([^ >]+)[^>]*>[[:space:]]*</\1>#', '', $html );
    } while ( $html !== $tmp );

    return $html;
  }

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

// todo: Make a more comprehensive list
    $no_children = [
//      'img',
//      'br',
    ];

    for ($i = $links->length; --$i >= 0; ) {

      /** @var \DOMElement $n */
      $n = $links->item($i);
      $href = $n->getAttribute('href') ;

      if ($n->hasChildNodes()) {

        $children = $n->childNodes;
        $nodes = [];


        for ($j = $children->length; --$j >= 0;) {
          $child = $children->item($j);

          if ($child->nodeType == XML_ELEMENT_NODE && !in_array($child->nodeName, $no_children)) {

            // Move links inside elements, make it <element><a></element>
            $new_a = $n->cloneNode();
            $child_clone = $child->cloneNode();
            foreach ($child->childNodes as $cn) {
              $new_a->appendChild($cn);
            }
            $child_clone->appendChild($new_a);
            $nodes[] = $child_clone;

          }
          else if ($child->nodeType == XML_TEXT_NODE) {
            // Skip
          }
          else {
            // Else wrap element in a <a><element></a>
            $new_a = $n->cloneNode();
            $new_a->appendChild($child);
            $nodes[] = $new_a;
          }

        }

        $nodes = array_reverse($nodes);
        foreach ($nodes as $node) {
          $n->parentNode->appendChild($node);
        }

        // Remove our original <a>
//        $n->parentNode->removeChild($n);

      }
    }

    $value = $dom->saveHTML();

    // So so gross.
    $value = $this->remove_empty($value);
    $value = str_replace('###_REMOVE_ME_###</drupal-entity>', '</drupal-entity>', $value);


    return substr(substr($value, 5), 0, -7);

  }//end process()


}//end class
