<?php

namespace Migrate\Crawler\Group;

use Psr\Http\Message\ResponseInterface;

/**
 * The group interface.
 */
interface GroupInterface
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
