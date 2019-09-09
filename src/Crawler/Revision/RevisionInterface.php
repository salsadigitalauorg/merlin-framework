<?php

namespace Migrate\Crawler\Revision;

use Psr\Http\Message\ResponseInterface;

/**
 * The revision interface.
 */
interface RevisionInterface
{


    /**
     * Get the identifier for this grouping.
     *
     * @return string
     */
    public function getId() : string;


    /**
     * Apply the rules to match the current URL.
     */
    public function match($url, ResponseInterface $response) : bool;


    /**
     * Return an option.
     *
     * @param string $key
     *   The key to find in the options array.
     *
     * @return mixed
     *   The configuration option.
     */
    public function getOption($key);


}//end interface
