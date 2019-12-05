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
 *     parent:
 *       selector: '//*[@id="breadcrumb"]/ul/li[@last()]
 *       text: './a/text()'
 *       link: './a/@href'
 *   children:
 *     -
 *       type: menu_link
 *       selector: .links li
 *       options:
 *         link: ./a/@href
 *         text: ./a/text()
 */
class MenuLink extends TypeMultiComponent implements TypeInterface
{


    public function getSupportedSelectors()
    {
        return ['xpath'];

    }//end getSupportedSelectors()


    public function processChildren(Crawler $node, array $config=[], array &$result=[], $parent='')
    {

        // Parent override support.
        if (!empty($config['options']['parent']['selector'])) {
          $n = $this->crawler->evaluate($config['options']['parent']['selector']);

          if ($n->count() > 0) {
            $parentText = $n->evaluate($config['options']['parent']['text']);
            $parentLink = $n->evaluate($config['options']['parent']['link']);
            $processedLink = $this->processLink($parentText, $parentLink);
            $parent = $processedLink['uuid'];
          }
        }

        $node->each(
            function (Crawler $item, $i) use ($config, &$result, $parent) {
                $text = $item->evaluate($config['options']['text']);
                $link = $item->evaluate($config['options']['link']);

                if ($text->count() === 0 && $link->count() === 0) {
                    return;
                }

                $processedLink = $this->processLink($text, $link);
                $link = $processedLink['link'];
                $uuid = $processedLink['uuid'];

                $result_item = [
                    'uuid'   => $uuid,
                    'link'   => $link,
                    'text'   => $text->text(),
                    'parent' => $parent,
                    'weight' => $i,
                ];

                $result_item = $this->applyProcessors($result_item);
                $result[] = $result_item;

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


    /**
     * Generate processed link and uuid given text/link elements.
     *
     * @param Crawler $text
     * @param Crawler $link
     *
     * @return Array
     *   Associative array with processed link, uuid
     */
    private function processLink($text, $link)
    {
        $link = $link->count() > 0 ? $link->text() : 'internal:/';
        // Menu uuid comprised of menu name, link text, link value.
        $uuid_text = $this->config['name'].$text->text().$link;
        $uuid = Uuid::uuid3(Uuid::NAMESPACE_DNS, strtolower($uuid_text));

        if ($this->isRelativeUri($link)) {
          $link = 'internal:'.$link;
        }

        return [
            'link' => $link,
            'uuid' => $uuid,
        ];

    }//end processLink()


}//end class
