<?php

namespace Migrate\Parser;

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
            throw new \Exception("Invalid source file: No content type found in the source file");
        }

        if (empty($data['mappings'])) {
            throw new \Exception("Invalid source file: No mappings found in the source file");
        }

        if (!empty($data['urls_file'])) {
            $urls_file = dirname($this->source).'/'.$data['urls_file'];

            if (!file_exists($urls_file)) {
                throw new \Exception("Invalid URLs file provided: cannot locate {$data['urls_file']}");
            }

            $urls_from_file = \Spyc::YAMLLoad($urls_file);
            if (!is_array($urls_from_file['urls'])) {
                $urls_from_file['urls'] = [$urls_from_file['urls']];
            }

            if (isset($data['urls']) && is_array($data['urls'])) {
                $data['urls'] = array_merge($data['urls'], $urls_from_file['urls']);
            } else {
                $data['urls'] = $urls_from_file['urls'];
            }
        }

        if (!is_array($data['urls'])) {
            $data['urls'] = [$data['urls']];
        }

        $this->data = $data;
        $this->totals['urls'] = count($data['urls']);
        $this->totals['mappings'] = count($data['mappings']);
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
        if ($this->totals['urls'] > 0) {
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


}//end class
