<?php

namespace Merlin\Parser;

class WebConfig extends ConfigBase
{


    /**
     * {@inheritdoc}
     */
    protected function parse()
    {
        if (!file_exists($this->source)) {
            throw new \Exception("Invalid source file provided: Cannot locate $this->source");
        }

        $data = \Spyc::YAMLLoad($this->source);

        if (empty($data['domain'])) {
            throw new \Exception("Invalid source file: No domain found in the source file");
        }

        if (!array_key_exists('urls', $data) && !array_key_exists('urls_file', $data)) {
            throw new \Exception("Need to supply one or both of: urls, urls_file");
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

        if (empty($data['urls'])) {
            throw new \Exception("Invalid source file: No URLs found in the source file");
        }

        if (empty($data['entity_type'])) {
            throw new \Exception("Invalid source file: No content type found in the source file");
        }

        if (empty($data['mappings'])) {
            throw new \Exception("Invalid source file: No mappings found in the source file");
        }

        $data = $this->inflateMappings($data);

        $this->data = $data;
        $this->totals['mappings'] = count($data['mappings']);
        $this->totals['urls']     = count($data['urls']);
        return $this;

    }//end parse()


    /**
     * {@inheritdoc}
     */
    public function getUrl()
    {
        if ($this->totals['urls'] > 0) {
            $this->totals['urls']--;
            return $this->data['domain'].array_shift($this->data['urls']);
        }

    }//end getUrl()


    /**
     * Forces cache disable.
     */
    public function disableCache() {
        $this->data['fetch_options']['cache_enabled'] = false;

    }//end disableCache()


}//end class
