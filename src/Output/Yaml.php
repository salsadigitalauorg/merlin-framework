<?php

namespace Merlin\Output;

/**
 * Manage the output in yaml.
 */
class Yaml extends OutputBase
{

    /**
     * The extension to use when writing the file.
     *
     * @var string
     */
    protected $ext = 'yml';


    /**
     * {@inheritdoc}
     */
    public function toString(array $data=[])
    {
        return \Spyc::YAMLDump($data,2,0);

    }//end toString()


}//end class
