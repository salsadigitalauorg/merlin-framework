<?php

namespace Migrate\Exception;

/**
 * An exception if there is a problem with an element.
 */
class ElementNotFoundException extends \Exception {
  const FILE = 'error-not-found';
}
