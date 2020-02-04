<?php

namespace Merlin\Type;

use Merlin\Command\GenerateCommand;
use Merlin\Exception\ElementNotFoundException;
use Merlin\Exception\ElementException;
use Merlin\Exception\ValidationException;

/**
 * Generate a paragraph nested structure.
 *
 * @example
 *   field: field_content_components
 *   type: paragraph
 *   children:
 *      -
 *        field: field_body_text
 *        type: text
 */
class Paragraph extends TypeBase implements TypeInterface
{


    /**
     * Process the child rows of the paragraph.
     *
     * A paragraph is a grouping of other field types. This will iterate
     * through the children key on the configuration object and will create
     * continue creating new representations
     */
    public function processChildren(&$row)
    {

        $entity_type = $this->config['type'].'-'.$this->config['paragraph_type'];

        $children = isset($this->config['children']) ? $this->config['children'] : [];
        foreach ($children as $child) {
            $type = GenerateCommand::TypeFactory($child['type'], $this->crawler, $this->output, $row, $child);
            try {
                $type->process();
            } catch (ElementNotFoundException $e) {
                $this->output->mergeRow("{$entity_type}-".$e::FILE, $this->crawler->getUri(), [$e->getMessage()], true);
            } catch (ElementException $e) {
                $this->output->mergeRow("{$entity_type}-".$e::FILE, $this->crawler->getUri(), [$e->getMessage()], true);
            } catch (ValidationException $e) {
                $this->output->mergeRow("{$entity_type}-".$e::FILE, $this->crawler->getUri(), [$e->getMessage()], true);
            } catch (\Exception $e) {
                $this->output->mergeRow("{$entity_type}-error-unhandled", $this->crawler->getUri(), [$e->getMessage()], true);
            }
        }

    }//end processChildren()


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
        $row = new \stdClass;
        $this->processChildren($row);

        $row       = (array) $row;
        $empty_row = false;

        foreach ($row as $field => $value) {
            $empty_row = $empty_row || empty($value);
        }

        if (empty($this->config['options']['allow_null']) && $empty_row) {
            // If we not allowing null values and we have an empty row return
            // early so nothing is added to the output.
            return;
        }

        $result = [
            'type'     => $this->config['paragraph_type'],
            'children' => (array) $row,
        ];

        isset($this->config['field']) ? $this->row->{$this->config['field']} = (object) $result : $this->row = (object) $result;

    }//end processDom()


}//end class
