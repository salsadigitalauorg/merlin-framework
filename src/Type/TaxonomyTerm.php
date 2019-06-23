<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Command\GenerateCommand;
use Ramsey\Uuid\Uuid;

/**
 * Filter taxonomy terms by attributes.
 *
 * The selector should be either a container or the node that specifies the
 * term name for the term. If a container is used it the children property
 * should be set so that we can correctly find the name.
 *
 * @example:
 *   field: field_fr_guide_category
 *   selector: ".tags.links li"
 *   type: taxonomy_term
 *   selector: .class // or xpath
 *   children:
 */
class TaxonomyTerm extends TypeBase implements TypeInterface
{


    /**
     * @TODO Maybe this should be
     */
    public function processChildren(Crawler $crawler, &$row, $xpath=false)
    {
        $children = isset($this->config['children']) ? $this->config['children'] : [];
        foreach ($children as $child) {
            $type = GenerateCommand::TypeFactory($child['type'], $crawler, $this->output, $row, $child);
            try {
                $type->process();
            } catch (\Exception $e) {
                // We should handle this sometime...
            }
        }

    }//end processChildren()


    /**
     * Return the text value of the node.
     */
    public function processChild(Crawler $crawler, &$row, $xpath=false)
    {
        $row->name = $crawler->text();
        $row->uuid = Uuid::uuid3(Uuid::NAMESPACE_DNS, $row->name);

    }//end processChild()


    /**
     * {@inheritdoc}
     */
    public function processXpath()
    {
        extract($this->config);

        if (empty($vocab)) {
            throw new \Exception('FiledType error: Missing option "vocab" for taxonomy_filter."');
        }

        $fields = array_column($children, 'field');
        if (!in_array('uuid', $fields)) {
            throw new \Exception('Taxonomy terms require the uuid field.');
        }

        $results  = [];
        $children = !empty($children) ? $children : [];

        $this->crawler->each(
            function ($node) use (&$results, $children) {
                $row = new \stdClass;
                if (isset($children)) {
                    $this->processChildren($node, $row, true);
                } else {
                    $this->processChild($node, $row, true);
                }

                $results[] = $row;
            }
        );

        $uuids = array_column($results, 'uuid');
        $this->row->{$field} = $uuids;
        $this->output->mergeRow($vocab, 'data', $results, true);

    }//end processXpath()


    /**
     * {@inheritdoc}
     */
    public function processDom()
    {
        extract($this->config);

        if (empty($vocab)) {
            throw new \Exception('FiledType error: Missing option "vocab" for taxonomy_filter."');
        }

        if (empty($children['uuid'])) {
            throw new \Exception('Taxonomy terms require the uuid field.');
        }

        $results  = [];
        $children = !empty($children) ? $children : [];

        $this->crawler->each(
            function ($node) use (&$results, $children) {
                $row = new \stdClass;
                if (isset($children)) {
                    $this->processChildren($node, $row, true);
                } else {
                    $this->processChild($node, $row, true);
                }

                $results[] = $row;
            }
        );

        $uuids = array_column($results, 'uuid');
        $this->row->{$field} = $uuids;
        $this->output->mergeRow($vocab, 'data', $results, true);

    }//end processDom()


}//end class
