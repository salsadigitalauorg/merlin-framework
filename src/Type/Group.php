<?php

namespace Migrate\Type;


use Migrate\Command\GenerateCommand;
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
 * @package Migrate\Type
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

    $this->row->{$this->config['field']} = [
        'type'     => 'group',
        'children' => $results,
    ];

  }//end process()


}//end class
