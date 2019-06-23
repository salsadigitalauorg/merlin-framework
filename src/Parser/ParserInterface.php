<?php

namespace Migrate\Parser;

interface ParserInterface
{


    /**
     * Set the source for the configuration.
     *
     * @param string $loc
     *   The location of the configuration file.
     *
     * @return this
     */
    public function setSource($loc);


    /**
     * Get a the processed source.
     */
    public function getSource();


    /**
     * Retrieves the next row for mapping.
     *
     * Access the mapping direction from the sorce this will
     * return the next in the iteration until it cannot return
     * another row and then it will return false.
     *
     * @return array|false
     *   The mapping row.
     */
    public function getMapping();


    /**
     * Retrieves the next URL form the config source.
     *
     * Access the URL directly and return the next in the iteration until it cannot return
     * another row and then it will return false.
     *
     * @return string|false
     *   The url.
     */
    public function getUrl();


    /**
     * Get the data array for this configuration object.
     *
     * @return array
     *   The configuration array.
     */
    public function getData();


    /**
     * Reset the configuration object pointers.
     *
     * @return this
     */
    public function reset();


    /**
     * Return a data key.
     *
     * @param string $key
     *   The key to access.
     *
     * @return mixed|false
     */
    public function get($key);


}//end interface
