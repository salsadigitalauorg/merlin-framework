<?php

namespace Migrate\Type;

use Ramsey\Uuid\Uuid;
use Symfony\Component\DomCrawler\Crawler;
use Migrate\Utility\ElementTrait;

/**
 * The long text processor.
 *
 * Attempt to locate WYSIWYG content in a page and create the necessary JSON
 * files to represent that data for a consistent Drupal migration.
 *
 * Example config:
 * ```
 *   field: field_body
 *   type: long_text
 *   options:
 *     findDocuments: .doc
 *   processors:
 *     nl2br: { }
 * ```
 */
class LongText extends TypeBase implements TypeInterface
{

    use ElementTrait;


    /**
     * Attempt to locate embedded downloadable documents.
     *
     * This will locate all references of a defined document embedded in the
     * markup and will pull them out. It will generate a list of media entites
     * which will be migrated seperately to the content and will replace the
     * content with a drupal media embed link.
     *
     * Option values:
     * - findDocuments: DOM Selector with which to find documents in the markup.
     * - documentSelector: The selector with which to find documents.
     * - documentName: Attribute to use to find the document title
     * - documentFile: Attribute to use to find a link to the document
     *
     * @param string &$markup
     *   The HTML output of the parent selector.
     */
    public function findDocumentAttachments(&$markup)
    {
        $docs = $this->crawler->filter($this->config['options']['findDocuments']);
        if ($docs->count() == 0) {
            return;
        }

        $files = [];
        $document_selector = isset($this->config['options']['documentSelector']) ? $this->config['options']['documentSelector'] : 'a' ;
        $document_name     = isset($this->config['options']['documentName']) ? $this->config['options']['documentName'] : 'data-tracker-label';
        $document_file     = isset($this->config['options']['documentFile']) ? $this->config['options']['documentFile'] : 'href';

        $docs->each(
            function (Crawler $node) use (&$markup, &$files, $document_selector, $document_name, $document_file) {
                $link = $node->filter($document_selector);
                $url  = parse_url($this->crawler->getUri());
                if ($link->count() == 0) {
                    return;
                }

                // Sometimes a URL might have a UUID in the link, we should look to use that for the uuid
                // of the media entity when migrating to Drupal for consistency.
                $matches = [];
                $uuid    = Uuid::uuid3(Uuid::NAMESPACE_DNS, $link->attr($document_name));
                if (preg_match('/[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}/', $link->attr($document_file), $matches) !== false) {
                    $uuid = reset($matches);
                }

                $files[] = [
                    'file' => "{$url['scheme']}://{$url['host']}{$link->attr($document_file)}",
                    'name' => $link->attr($document_name),
                    'uuid' => $uuid,
                ];

                $node       = $link->getNode(0);
                $outer_html = $node->ownerDocument->saveHtml($node);

                $markup = str_replace($outer_html, '<drupal-entity data-embed-button="tide_media" data-entity-embed-display="view_mode:media.embedded" data-entity-type="media" data-entity-uuid="'.$uuid.'"></drupal-entity>', $markup);
            }
        );

        $this->output->mergeRow('media-documents', 'data', $files, true);

    }//end findDocumentAttachments()


    public function processXpath()
    {
        extract($this->config);

        $markup = '';

        $this->crawler->each(
            function (Crawler $node) use (&$markup) {
                $markup .= $node->html();
            }
        );

        if (!empty($options['findDocuments'])) {
            $this->findDocumentAttachments($markup);
        }

        $results = [
            'format' => isset($options['format']) ? $options['format'] : 'rich_text',
            'value'  => $this->processValue($markup),
        ];

        $this->row->{$field} = $results;

    }//end processXpath()


    /**
     * {@inheritdoc}
     */
    public function processDom()
    {
        extract($this->config);
        $markup = '';

        if (!$this->isValidElement($this->crawler)) {
            $this->addValueToRow('');
            return;
        }

        $this->crawler->each(
            function (Crawler $node) use (&$markup) {
                $markup .= $node->html();
            }
        );

        if (!empty($options['findDocuments'])) {
            $this->findDocumentAttachments($markup);
        }

        $results[] = [
            'format' => isset($options['format']) ? $options['format'] : 'rich_text',
            'value'  => $this->processValue($markup),
        ];

        $this->addValueToRow($results);

    }//end processDom()


}//end class
