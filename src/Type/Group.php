<?php

namespace Merlin\Type;


use Merlin\Command\GenerateCommand;
use Symfony\Component\DomCrawler\Crawler;
use \Ramsey\Uuid\Uuid;

use function DeepCopy\deep_copy;


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

    $items = isset($this->config['each']) ? $this->config['each'] : [];
    $options = isset($this->config['options']) ? $this->config['options'] : [];

    if (empty($items)) {
      throw new \Exception('"each" key required for group.');
    }

    $results = [];

    // HERE BE MADNESS! This works but is hopefully not required.
    // Here we create a new document and then processes a
    // node as though it was separate by removing its siblings and then
    // rinse, wash, repeat for each sibling.
    /*
    foreach ($this->crawler as $content) {
      $selector = $this->config['selector'];
      $original_nodes = $this->crawler->evaluate($selector);

      foreach ($original_nodes as $idx => $node) {
        // We are going to modify the DOM so use a new document per node we want to process.
        $c = new Crawler($this->crawler->html(), $this->crawler->getUri(), $this->crawler->getBaseHref());

        // The original crawler object has private access on the document.  Of course.
        $patch = function() use ($items) {
          return $this->document;
        };
        $doc = $patch->call($c);

        // Find our group selector and remove each one unless
        // it's the current one in the outer loop.  (i.e. we
        // go over it and remove siblings for each sibling).
        // This lets us process them individually.
        $xpath = new \DOMXPath($doc);
        $nodes = $xpath->evaluate($selector);

        for ($i = $nodes->length; --$i >= 0;) {
          if ($i != $idx) {
            $n = $nodes->item($i);
            $n->parentNode->removeChild($n);
          }
        }

        // Iterate over our config child items and process.
        foreach ($items as $item) {
          // Todo: Allow global scope for items option i.e. make this concat bit optional.
          $item['selector'] = $selector.$item['selector'];

          $row = new \stdClass();
          $this->processItem($row, $c, $item);
          $tmp[$item['field']] = @$row->{$item['field']};
        }

        $results[] = $tmp;
      }//end foreach
    }//end foreach
    */

    // More sensible approach using subcrawler.
    foreach ($this->crawler as $content) {
      $selector = $this->config['selector'];

      $group_nodes = $this->crawler->evaluate($selector);

      foreach ($group_nodes->getIterator() as $n) {

        $cc = new Crawler($n, $this->crawler->getUri(), $this->crawler->getBaseHref());
        $tmp = [];

        foreach ($items as $idx => $item) {
          $row = new \stdClass();
          $this->processItem($row, $cc, $item);

          // If a field is required and it is empty, we skip the whole group.
          $required = ($item['options']['required'] ?? null);
          if ($required && empty(@$row->{$item['field']})) {
            return;
          }

          $tmp[$item['field']] = @$row->{$item['field']};

        }

        $serialised = json_encode($tmp);
        $url = $this->crawler->getUri();

        // NOTE: This key assumes that there are no identical groups anywhere on this page.
        $uuid_key = $url.$serialised;
        $uuid = uuid::uuid3(Uuid::NAMESPACE_DNS, $uuid_key);
        $tmp['uuid'] = $uuid;


        $results[] = $tmp;
      }
    }

    // Original quick approach.  Types that need to process parent nodes
    // though won't work!  (e.g. Media replace).
    /*
        $this->crawler->each(
        function(Crawler $node) use ($items, &$results) {

          $nodes = $node->filterXPath($this->config['selector']);

          foreach ($nodes as $n) {
            $tmp = [];
            foreach ($items as $item) {
              $doc = new \DOMDocument();
              $in = $doc->importNode($n, true);
              $doc->appendChild($in);
              $c = new Crawler($doc, $this->crawler->getUri(), $this->crawler->getBaseHref());
              $row = new \stdClass();
              $this->processItem($row, $c, $item);
              $tmp[$item['field']] = @$row->{$item['field']};
            }

            $results[] = $tmp;
          }//end foreach
        }
        );
    */

    $sortField = ($options['sort_field'] ?? null);
    if (!empty($sortField)) {
      $sortDirection = ($options['sort_direction'] ?? 'asc');
      $this->sortBy($sortField, $results, $sortDirection);
    }

    if (empty($options['exclude_from_output'])) {
      $this->row->{$this->config['field']} = [
        'type' => 'group',
        'children' => $results,
      ];
    }

    // NOTE: This does not currently support NESTED paragraph generation, would need to do like
    // NOTE: Iterations over 'children' or something (or any children found with type group).

    if (key_exists('output_filename', $options)) {
      // The output filename could be e.g. 'standard_page_paragraphs'
      $this->output->mergeRow($options['output_filename'], 'data', $results, true);

      $group_uuids = [];
      foreach (array_column($results, 'uuid') as $p_uuid) {
        $group_uuids[] = ['uuid' => $p_uuid];
      }

      if (@is_array($this->row->group_uuids)) {
        $this->row->group_uuids = array_merge($this->row->group_uuids, $group_uuids);
      } else {
        $this->row->group_uuids = $group_uuids;
      }
    }


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
