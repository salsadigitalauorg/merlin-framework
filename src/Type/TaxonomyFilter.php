<?php

namespace Migrate\Type;

use Symfony\Component\DomCrawler\Crawler;
use Migrate\Utility\RegexValidatorTrait;
use Migrate\Exception\ValidationException;

/**
 * Filter taxonomy terms by attributes.
 *
 * @example:
 *   field: field_fr_guide_category
 *   selector: ".tags.links li"
 *   type: taxonomy_filter
 *   options:
 *     pattern: "/[A-z]+/"
 *     vocab: guide_category
 *     exclude_duplicates: true
 */
class TaxonomyFilter extends TypeBase implements TypeInterface
{

    use RegexValidatorTrait;


    /**
     * Filter the list by the given pattern.
     *
     * This will perform a regex match against the text of the element to
     * try and filter elements outside of what is provided by the DOM
     * crawler library.
     *
     * @TOOD this could be expanded to pass in an attr or text so that
     * we can filter on more than just the text of an element.
     *
     * @param string $pattern
     *   The regex pattern used to filter the elements by.
     * @param array  &$results
     *   The results array.
     *
     * @return function
     *   A callback which is passed to the each method of Crawler.
     */
    public static function filter($pattern, &$results)
    {
        return function (Crawler $node, $i) use ($pattern, &$results) {
            $text = trim($node->text());
            if (preg_match($pattern, $text) > 0) {
                $tid           = crc32($text);
                $results[$tid] = [
                    'term_id' => $tid,
                    'name'    => $text,
                ];
            }
        };

    }//end filter()


    /**
     * {@inheritdoc}
     */
    public function getSupportedSelectors()
    {
        return ['dom'];

    }//end getSupportedSelectors()


    /**
     * {@inheritdoc}
     */
    public function processDom()
    {
        try {
            /*
             * @var string $pattern
             * @var string $vocab
             */

            extract($this->config['options']);
        } catch (\Exception $e) {
            throw new ValidationException('Missing "options" for taxonomy term filter.');
        }

        $results = [];

        if (empty($vocab)) {
            throw new ValidationException('FieldType error: Missing option "vocab" for taxonomy_filter');
        }

        if (empty($pattern)) {
            throw new ValidationException('FieldType error: Missing option "pattern" for taxonomy_filter');
        }

        if (!$this->isValidRegex($pattern)) {
            throw new ValidationException("$pattern is invalid.");
        }

        $this->crawler->each(self::filter($pattern, $results));

        if (empty($results)) {
            $this->addValueToRow($this->nullValue());
        }

        // Add a reference to the terms.
        $this->addValueToRow(array_keys($results));
        $this->output->mergeRow($vocab, 'data', $results);

    }//end processDom()


}//end class
