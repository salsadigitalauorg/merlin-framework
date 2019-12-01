<?php

namespace Migrate\Parser;

use function DeepCopy\deep_copy;

abstract class ConfigBase implements ParserInterface
{

    /**
     * The path to the configuration source.
     *
     * @var string
     */
    protected $source;

    /**
     * The parsed data for the configuration object.
     *
     * @var array
     */
    protected $data;

    /**
     * A list of totals.
     *
     * @var array
     */
    protected $totals;


    /**
     * Build a configuration object.
     */
    public function __construct($source)
    {
      $this->source = $source;
      $this->parse();

    }//end __construct()


    /**
     * {@inheritdoc}
     */
    protected function parse()
    {
        if (!file_exists($this->source)) {
            throw new \Exception("Invalid source file provided: Cannot locate $this->source");
        }

        $data = \Spyc::YAMLLoad($this->source);

        if (empty($data['entity_type'])) {
            throw new \Exception("Invalid source file: No content type defined in the source file");
        }

        if (empty($data['mappings'])) {
            throw new \Exception("Invalid source file: No mappings defined in the source file");
        }

        /*
         * If a URLs file is provided, add the URLs to the URL array
         * otherwise use the URL array.
         */

        if (!empty($data['urls_file'])) {
            // If urls_files is provided as a string, make it a single item array to make it easier to handle.
            $urls_files = is_array($data['urls_file']) ? $data['urls_file'] : [$data['urls_file']];
            $urls_files_count = count($urls_files);

            $urls_from_files = ['urls' => []];

            for ($i = 0; $i < $urls_files_count; $i++) {
              $urls_file = dirname($this->source).'/'.$urls_files[$i];

              if (!file_exists($urls_file)) {
                  throw new \Exception("Invalid URLs file provided: cannot locate {$urls_files[$i]}");
              }

              $urls_from_current_file = \Spyc::YAMLLoad($urls_file);

              if (!is_array($urls_from_current_file['urls'])) {
                  $urls_from_current_file['urls'] = [$urls_from_current_file['urls']];
              }

              $urls_from_files['urls'] = array_merge($urls_from_current_file['urls'], $urls_from_files['urls']);
            }

            $this->totals['urls_from_file'] = count($urls_from_files['urls']);

            if (isset($data['urls'])) {
                $data_urls_array = is_array($data['urls']) ? $data['urls'] : [$data['urls']];
                $this->totals['urls_from_config'] = count($data_urls_array);
                $data['urls'] = array_merge($data_urls_array, $urls_from_files['urls']);
            } else {
                $data['urls'] = $urls_from_files['urls'];
            }

            unset($data['urls_file']);
        } else {
            if (!isset($data['urls'])) {
                $this->totals['urls_from_config'] = 0;
            } else {
                $data_urls_array = is_array($data['urls']) ? $data['urls'] : [$data['urls']];
                $this->totals['urls_from_config'] = count($data_urls_array);
            }
        }//end if

        if (!is_array($data['urls'])) {
            $data['urls'] = [$data['urls']];
        }

        // $data = $this->inflateMappings($data);
        $this->data = $data;
        $this->totals['mappings'] = count($data['mappings']);
        $this->totals['urls'] = count($data['urls']);

        return $this;

    }//end parse()


    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        return $this->parse();

    }//end reset()


    /**
     * {@inheritdoc}
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;

    }//end setSource()


    /**
     * {@inheritdoc}
     */
    public function getSource()
    {
        return $this->source;

    }//end getSource()


    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if (empty($this->data)) {
            $this->parse();
        }

        return $this->data;

    }//end getData()


    /**
     * {@inheritdoc}
     */
    public function getMapping()
    {
        if ($this->totals['mappings'] >= 0) {
            $this->totals['mappings']--;
            return array_shift($this->data['mappings']);
        }

        return false;

    }//end getMapping()


    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if (!empty($this->totals['urls']) && $this->totals['urls'] > 0) {
            $this->totals['urls']--;
            return $this->data['domain'].array_shift($this->data['urls']);
        }

    }//end getUrl()


    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return !empty($this->data[$key]) ? $this->data[$key] : false;

    }//end get()


  /**
   * This checks for field and/or selectors that are arrays in a config mapping.
   * If found, it deep clones the config map and appends it ready to process.
   * This allows you to re-use a config mapping on content that may need the
   * same processing but appears with different classes on certain pages etc.
   *
   * Field and selector can be any combination of a string or array.
   *
   * The behaviour is such that:
   *
   * field (array), selector (array):
   *    field[n] will use selector[n] to build results.  Must be the same size.
   *
   * field (array), selector (string):
   *    Multiple fields will appear in results with the result of the selector.
   *
   * field (string), selector (array):
   *    The field will contain the result from the *first* matched selector.
   *    NOTE: This case is now handled in Type/TypeBase.
   *
   *
   * @param $data
   *
   * @return mixed
   * @throws \Exception
   */
    protected function inflateMappings($data) {

      // Expand array maps.
      $mappings =& $data['mappings'];

      for ($i = count($mappings); --$i >= 0;) {
        $currentMap = $mappings[$i];

        $field = ($currentMap['field'] ?? null);
        $selector = ($currentMap['selector'] ?? null);

        if (!empty($field) && !empty($selector)) {
          if (is_string($field) && is_string($selector)) {
            // Default case.
            continue;
          } else if (is_array($field) && is_array($selector)) {
            if (count($field) !== count($selector)) {
              $msg = "If both 'field' and 'selector' are both arrays, their lengths must match.\n";
              $msg .= " - field: \n".print_r($field,1)." - selector: \n".print_r($selector ,1);
              throw new \Exception($msg);
            }

            foreach ($field as $idx => $newField) {
              $newSelector = $selector[$idx];
              $mappings[] = self::cloneMap($currentMap, $newField, $newSelector);
            }

            unset($mappings[$i]);
          } else if (is_array($field) && is_string($selector)) {
            // Reverse is here for first match since we are looping backwards.
            $field = array_reverse($field);
            foreach ($field as $idx => $newField) {
              $mappings[] = self::cloneMap($currentMap, $newField, $selector);
            }

            unset($mappings[$i]);
          }//end if
        }//end if
      }//end for

      $mappings = array_values($mappings);

      return $data;

    }//end inflateMappings()


  /**
   * Returns a deep clone of a config mapping, optionally replacing
   * @param $existingMap
   * @param $newField
   * @param $newSelector
   *
   * @return mixed
   */
  protected static function cloneMap(array $existingMap, ?string $newField, ?string $newSelector) {
    $clone = deep_copy($existingMap);

    if (!empty($newField)) {
      unset($clone['field']);
      $clone['field'] = $newField;
    }

    if (!empty($newSelector)) {
      unset($clone['selector']);
      $clone['selector'] = $newSelector;
    }

    return $clone;

  }//end cloneMap()


}//end class
