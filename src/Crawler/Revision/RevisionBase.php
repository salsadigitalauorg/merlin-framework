<?php

namespace Migrate\Crawler\Revision;

/**
 * Standard methods for a Revision option.
 */
abstract class RevisionBase implements RevisionInterface
{


    /**
     * Construct a revision by rule.
     */
    public function __construct(array $config=[])
    {
        $this->id = isset($config['id']) ? $config['id'] : NULL;
        $this->options = isset($config['options']) ? $config['options'] : [];

    }//end __construct()


    /**
     * {@inheritdoc}
     */
    public function getId() : string
    {

        $id = empty($this->id) ? md5(rand(1, 1000)) : $this->id;
        return strtolower(str_replace('_', '-', $id));

    }//end getId()


    /**
     * {@inheritdoc}
     */
    public function getOption($key)
    {

        return isset($this->options[$key]) ? $this->options[$key] : NULL;

    }//end getOption()


}//end class
