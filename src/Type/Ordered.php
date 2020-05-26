<?php

namespace Merlin\Type;

use Merlin\Command\GenerateCommand;
use Symfony\Component\DomCrawler\Crawler;

class Ordered extends TypeBase implements TypeInterface {


  /**
   * Process the item rows of the ordered list.
   *
   * @param object &$row
   *   The result row.
   * @param Crawler $crawler
   *   The narrowed crawler object to the particular found item.
   * @param array $item
   *   The item configuration for the child.
   */
  public function processItem(&$row, Crawler $crawler, $item) {
    $children = isset($item['fields']) ? $item['fields'] : [];
    foreach ($children as $child) {
      $type = GenerateCommand::TypeFactory($child['type'], $crawler, $this->output, $row, $child);
      try {
        $type->process();
      } catch (\Exception $e) {
        // :(
      }
    }

  }//end processItem()


  /**
   * {@inheritdoc}
   */
  public function process() {
    // Parent will select based on DOM or Xpath.
    parent::process();
    $results = [];

    $list = isset($this->config['available_items']) ? $this->config['available_items'] : FALSE;

    if (!$list) {
      throw new \Exception('"available_items" key missing.');
    }

    $this->crawler->each(
        function(Crawler $node) use ($list, &$results) {
          foreach ($list as $item) {
            if (isset($item['by']['selector'])) {
              $result = $node->evaluate($item['by']['selector']);
              if ($result->count() == 0) {
                continue;
              } else {
                $node = $result;
              }
            } else {
              $attr = $node->attr($item['by']['attr']);
                if (strpos($attr, $item['by']['text']) === FALSE && count($list) > 1) {
                  continue;
                }
            }

            $row = new \stdClass();
            $this->processItem($row, $node, $item);
            $results[] = $row;
          }//end foreach
        }
    );

    $flat_results = [];
    $hashed_results = [];
    foreach ($results as $result) {
      $result = (array) $result;
      foreach ($result as $item) {
        $hash = md5(serialize($item));
        if (!in_array($hash, $hashed_results)) {
          $hashed_results[] = $hash;
          $flat_results[] = $item;
        }
      }
    }

    $this->row->{$this->config['field']} = [
        'type'     => 'container',
        'children' => $flat_results,
    ];

  }//end process()


}//end class
