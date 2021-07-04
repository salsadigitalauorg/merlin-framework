<?php

namespace Merlin\Type;


use Merlin\Command\GenerateCommand;
use Symfony\Component\DomCrawler\Crawler;
use Merlin\Utility\MerlinUuid;

/**
 * The Group class lets you build a flattened, but grouped result set from
 * a nested DOM structure.  You can make your result nested, too, if you
 * wish by nesting groups.
 *
 * NOTE: Items in the group are NOT linked to their ancestors.
 * i.e. you cannot use parent, ../ or ancestor xpath commands.
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
   * @throws \Exception
   */
  public function process()
  {

    $items = ($this->config['each'] ?? []);
    $options = ($this->config['options'] ?? []);
    $req_skip_group = ($options['required_skip_group'] ?? false);
    $req_skip_child = ($options['required_skip_child'] ?? false);
    $generate_uuid = ($options['generate_uuid'] ?? false);
    $output_filename = ($options['output_filename'] ?? null);


    if (empty($items)) {
      throw new \Exception('"each" key required for group.');
    }

    $results = [];
    foreach ($this->crawler as $_content) {
      $selector = $this->config['selector'];

      $group_nodes = $this->crawler->evaluate($selector);

      foreach ($group_nodes->getIterator() as $n) {
        $cc = new Crawler($n, $this->crawler->getUri(), $this->crawler->getBaseHref());
        $tmp = [];

        $add_to_results = true;

        foreach ($items as $_idx => $item) {
          $row = new \stdClass();
          $this->processItem($row, $cc, $item);

          $required = ($item['options']['required'] ?? false);
          if ($req_skip_group && $required && empty(@$row->{$item['field']})) {
            // If a field is required and it is empty, skip the whole group construct.
            return;
          }

          if ($req_skip_child && $required && empty(@$row->{$item['field']})) {
            // If a fields is required, but empty, skip the current group child.
            $add_to_results = false;
          }

          $tmp[$item['field']] = @$row->{$item['field']};
        }

        $serialised = json_encode($tmp);
        $url = $this->crawler->getUri();

        if ($generate_uuid || !empty($output_filename)) {
          // NOTE: This key assumes that there are no identical groups anywhere on this page.
          $uuid_key = $url.$serialised;
          $uuid = MerlinUuid::getUuid($uuid_key);
          $tmp['uuid'] = $uuid;
        }

        if ($add_to_results) {
          $results[] = $tmp;
        }
      }//end foreach
    }//end foreach

    $sortField = ($options['sort_field'] ?? null);
    if (!empty($sortField)) {
      $sortDirection = ($options['sort_direction'] ?? 'asc');
      $this->sortBy($sortField, $results, $sortDirection);
    }

    if (empty($options['exclude_from_output'])) {
      $this->row->{$this->config['field']} = [
          'type'     => 'group',
          'children' => $results,
      ];
    }

    // NOTE: This does not currently support NESTED output generation.
    if (!empty($output_filename)) {
      // The output filename could be e.g. 'standard_page_paragraphs'.
      $this->output->mergeRow($output_filename, 'data', $results, true);

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
