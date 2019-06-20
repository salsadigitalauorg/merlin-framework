<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Ramsey\Uuid\Uuid;

/**
 * Defines menu structures in an iterative YML structure.
 *
 * @example:
 *   field: main_menu
 *   name: main-menu
 *   type: menu_link
 *   options:
 *     link: ./a/@href
 *     text: ./a/text()
 *   children:
 *     -
 *       type: menu_link
 *       selector: .links li
 *       options:
 *         link: ./a/@href
 *         text: ./a/text()
 */
class MenuLink extends TypeBase implements TypeInterface
{


    public function getSupportedSelectors()
    {
        return ['xpath'];

    }//end getSupportedSelectors()


    public function processChildren(Crawler $node, array $config=[], array &$result=[], $parent='')
    {
        $node->each(
            function (Crawler $item, $i) use ($config, &$result, $parent) {
                $text = $item->evaluate($config['options']['text']);
                $link = $item->evaluate($config['options']['link']);

                if ($text->count() === 0 && $link->count() === 0) {
                    return;
                }

                $link = $link->count() > 0 ? $link->text() : 'internal:/';
                // Menu uuid comprised of menu name, link text, link value.
                $uuid_text = $this->config['name'].$text->text().$link;
                $uuid      = Uuid::uuid3(Uuid::NAMESPACE_DNS, $uuid_text);

                if ($this->isRelativeUri($link)) {
                    $link = 'internal:'.$link;
                }

                $result[] = [
                    'uuid'   => $uuid,
                    'link'   => $link,
                    'text'   => $text->text(),
                    'parent' => $parent,
                    'weight' => $i,
                ];

                if (!empty($config['children'])) {
                    foreach ($config['children'] as $child) {
                        $item = $item->evaluate($child['selector']);
                        $this->processChildren($item, $child, $result, $uuid);
                    }
                }
            }
        );

    }//end processChildren()


    public function processXpath()
    {
        $result = [];
        $this->processChildren($this->crawler, $this->config, $result);
        $this->output->mergeRow($this->config['name'], 'data', $result, true);

    }//end processXpath()


    /**
     * Determine if a given path is relative or absolute.
     *
     * @param string $uri
     *   The uri to tet.
     *
     * @return bool
     *   If the uri is relative or not.
     */
    public function isRelativeUri($uri='')
    {
        return substr($uri, 0, 1) === '/';

    }//end isRelativeUri()


}//end class
