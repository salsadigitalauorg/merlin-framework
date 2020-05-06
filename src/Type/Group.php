<?php

namespace Merlin\Type;


use Merlin\Command\GenerateCommand;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The Group class lets you build a flattened, but grouped result set from
 * a nested DOM structure.  You can make your result nested, too, if you
 * wish by nesting groups.
 *
 * NOTE: Items in the group are NOT linked to their ancestors!
 * i.e. you cannot use parent, ../ or ancestor xpath commands.  This is
 * because it creates a new DOMDocument with the group to operate on.
 *
 * Class Group
 */
class Group extends TypeBase implements TypeInterface {


  /**
   * {@inheritdoc}
   */
  public function getSupportedSelectors()
  {
    return ['xpath'];

  }//end getSupportedSelectors()


  /**
   * {@inheritdoc}
   */
  public function nullValue()
  {
    return [];

  }//end nullValue()


  /**
   * @param                                       $row
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   * @param                                       $item
   *
   * @throws \Exception
   */
  public function processItem(&$row, Crawler $crawler, $item) {
      $type = GenerateCommand::TypeFactory($item['type'], $crawler, $this->output, $row, $item);
      try {
        $type->process();
      } catch (\Exception $e) {
        error_log($e->getMessage());
    }

  }//end processItem()


  /**
   * {@inheritdoc}
   */
  public function process()
  {
    $results = [];

    $items = isset($this->config['each']) ? $this->config['each'] : [];
    $options = isset($this->config['options']) ? $this->config['options'] : [];

    if (empty($items)) {
      throw new \Exception('"each" key required for group.');
    }

    $this->crawler->each(
        function(Crawler $node) use ($items, &$results) {

          $nodes = $node->filterXPath($this->config['selector']);

          foreach ($nodes as $n) {
            $tmp = [];
            foreach ($items as $item) {
              $doc = new \DOMDocument();
              $in = $doc->importNode($n, true);
              $doc->appendChild($in);
              $c = new Crawler($doc);
              $row = new \stdClass();
              $this->processItem($row, $c, $item);
              $tmp[$item['field']] = @$row->{$item['field']};
            }

            $results[] = $tmp;
          }//end foreach
        }
    );

    $sortField = ($options['sort_field'] ?? null);
    if (!empty($sortField)) {
      $sortDirection = ($options['sort_direction'] ?? 'asc');
      $this->sortBy($sortField, $results, $sortDirection);
    }

    $this->row->{$this->config['field']} = [
        'type'     => 'group',
        'children' => $results,
    ];

  }//end process()


  /**
   * Sorts an array by the value of a specified field (key).
   * @param        $field
   * @param        $array
   * @param string $direction
   */
  public function sortBy($field, &$array, $direction='asc') {
    $direction = $direction === 'asc' ? SORT_ASC : SORT_DESC;
    array_multisort(array_map('strtolower', array_column($array, $field)), $direction, $array);

  }//end sortBy()


}//end class
