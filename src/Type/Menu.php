<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Ramsey\Uuid\Uuid;

/**
 * The Menu field type processor.
 *
 * This will attempt to look through the configured selector a
 * attempt to locate an "a" tag. From the "a" tag the href and
 * link text will be inherited.
 *
 * @example:
 *   field: main_menu
 *   name: main-menu
 *   type: menu
 *   selector: ".header-nav .navbar ul > li"
 *   options:
 *     children: ".dropdown li"
 *     link: href
 *     text: h3
 *     remove_duplicates: true
 */
class Menu extends TypeBase implements TypeInterface {

  /**
   * Access a nested menu structure to retrieve links.
   *
   * This is a recursive function that will iterate through a node list
   * using the configuration provided to build a JSON representaiton of
   * the menu structures the site has.
   *
   * It will preserve the menu heirarchy and will attempt to link children
   * to their parents by using the text value of the link.
   *
   * @param string $parent
   *   The parent menu item.
   * @param array $options
   *   The options for this menu parser.
   * @param array &$return
   *   A reference to the return object.
   *
   * @return function
   *   Returns a callback to be used with Crawler->each.
   */
  public static function getMenuItem($parent = '', array $options = [], array &$return) {
    return function(Crawler $node, $i) use ($options, $parent, &$return) {
      $link_el = $node->filter('a');

      // Find the link element in the current iterator.
      if ($link_el->count() == 0) {
        return;
      }

      $link_attr = isset($options['link']) ? $options['link'] : 'href';

      if (isset($options['text'])) {
        $text_el = $link_el->first()->filter($options['text']);
        $text = $text_el->count() > 0 ? $text_el->first()->text() : FALSE;
      }

      if (empty($text)) {
        // If we haven't built the text from the options configuration we
        // should just attempt to use the text of the menu item directly.
        $text = $link_el->text();
      }

      $link = 'internal:' . $link_el->first()->attr($link_attr);
      // Menu uuid comprised of menu name, link text, link value.
      $uuid_text = $text . $link;
      $uuid = Uuid::uuid3(Uuid::NAMESPACE_DNS, $uuid_text);

      $return[] = [
        'uuid' => $uuid,
        'link' => $link,
        'text' => $text,
        'parent' => $parent,
        'weight' => $i,
      ];

      if (isset($options['children']) && $node->filter($options['children'])->count() > 0) {
        // $parent = strtolower(preg_replace('/\s+/', '-', $text));
        $node->filter($options['children'])->each(Menu::getMenuItem($uuid, $options, $return));
      }

      return $return;
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedSelectors() {
    return ['dom'];
  }

  /**
   * {@inheritdoc}
   */
  public function processDom() {
    $links = [];
    $options = isset($this->config['options']) ? $this->config['options'] : [];
    $this->crawler->each(self::getMenuItem('', $options, $links));

    $name = isset($this->config['name']) ? $this->config['name'] : 'menu-' . md5(date('yy-mm-dd'));

    $result = [
      'name' => $name,
      'links' => $links,
    ];

    if (!empty($options['remove_duplicates'])) {
      $filter = [];
      foreach ($result['links'] as $key => $link) {
        // Generate a hash of link text and href, we assume that if the link text
        // and the href are the same this is a duplicate and shouldn't be
        // included in the output.
        $hash = md5($link['text'] . $link['href']);
        if (in_array($hash, $filter)) {
          unset($result['links'][$key]);
          continue;
        }
        $filter[] = $hash;
      }
    }

    // Ensure that we have rebased the array indecies.
    $result['links'] = array_values($result['links']);
    $this->output->mergeRow($name, 'data', $result, FALSE);
  }

}
