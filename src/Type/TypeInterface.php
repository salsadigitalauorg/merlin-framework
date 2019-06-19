<?php

namespace Migrate\Type;

interface TypeInterface {

  /**
   * The selectors that are supported by this type.
   *
   * @return array
   *   A list of supported selector types.
   */
  public function getSupportedSelectors();

  /**
   * If the process method can't find a selector what should be displayed.
   *
   * @return mixed
   */
  public function nullValue();

  /**
   * Get value for the field type from the DOM.
   *
   * @return void
   */
  public function process();

  /**
   * Get a value from the DOM using jQuery like selectors.
   *
   * @return void
   */
  public function processDom();

  /**
   * Get a value from the DOM using Xpath selectors.
   *
   * @return void
   */
  public function processXpath();

}
