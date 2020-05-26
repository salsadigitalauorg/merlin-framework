<?php

namespace Merlin\Type;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Add basic text to the JSON output.
 *
 * @example:
 *   field: title
 *   selector: "#phbody_1_ctl01_h1Title"
 *   type: text
 */
class Text extends TypeBase implements TypeInterface
{


    /**
     * Retrieve the value from the crawler.
     */
    public function getValues()
    {
        $values = null;

        if ($this->crawler->count() === 0) {
            return '';
        }

        if ($this->crawler->count() > 1) {
            $values = $this->crawler->each(
                function (Crawler $node) {
                    return $node->text();
                }
            );

            foreach ($values as &$value) {
                $value = $this->processValue($value);
            }
        } else {
            $values = $this->processValue($this->crawler->eq(0)->text());
        }

        return $values;

    }//end getValues()


    /**
     * {@inheritdoc}
     */
    public function processXpath()
    {
        $this->addValueToRow($this->getValues());

    }//end processXpath()


    /**
     * {@inheritdoc}
     */
    public function processDom()
    {
        $this->addValueToRow($this->getValues());

    }//end processDom()


}//end class
