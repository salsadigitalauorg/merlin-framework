<?php

namespace Merlin\Tests\Functional\Type;

use Merlin\Tests\Functional\CrawlerTestCase;
use Merlin\Type\Media;
use Merlin\Exception\ElementNotFoundException;
use Merlin\Output\OutputBase;

class MediaTest extends CrawlerTestCase
{

    /**
     * Ensure that the media can be xtract with DOM selectors.
     */
    public function testMediaDOM()
    {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '#with-image img',
            'options' => [],
        ];

        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $this->getOutput(),
            $row,
            $config
        );

        $media->process();

        $this->assertTrue(\property_exists($row, $config['field']));
        $this->assertEquals(1, count($media->entities));
        $this->assertEquals((string) $row->field_media[0], (string) $media->entities[0]['uuid']);
    }

    /**
     * Ensure the row can select media with xpath.
     */
    public function testMediaXpath()
    {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '//*[@id="with-image"]/img',
            'options' => [],
        ];
        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $this->getOutput(),
            $row,
            $config
        );

        $media->process();

        $this->assertTrue(\property_exists($row, $config['field']));
        $this->assertEquals(1, count($media->entities));

    }

    /**
     * Ensure DOM selector performs expectedly if element not found.
     */
    public function testInvalidDOMSelector()
    {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '#not-exist img',
            'options' => [],
        ];
        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $this->getOutput(),
            $row,
            $config
        );

        $this->expectException(ElementNotFoundException::class);

        $media->process();
    }

    /**
     * Ensure the correct exception is thrown if not found.
     */
    public function testInvalidXpathSelector() {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '//*[@id="not-exist-image"]/img',
            'options' => [],
        ];
        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $this->getOutput(),
            $row,
            $config
        );

        $this->expectException(ElementNotFoundException::class);

        $media->process();
    }

    /**
     * Test valid custom options work as expected.
     */
    public function testCustomOptions()
    {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '#with-image img',
            'options' => [
                'name' => 'data-name',
                'file' => 'data-file',
                'alt' => 'data-alt',
            ],
        ];
        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $this->getOutput(),
            $row,
            $config
        );

        $media->process();

        $this->assertEquals('Test', $media->entities[0]['name']);
        $this->assertEquals('http://localhost/real-path-to-file', $media->entities[0]['file']);
        $this->assertEquals('Alternative text', $media->entities[0]['alt']);
    }

    /**
     * Ensure thata processors apply when using DOM.
     */
    public function testProcessorsDOM() {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '//*[@id="with-image"]/img',
            'options' => [
                'name' => './@data-name',
                'file' => './@data-file',
                'alt' => './@data-alt',
            ],
            'processors' => [
                'name' => [
                    'replace' => [
                        'pattern' => '^.*',
                        'replace' => 'name',
                    ]
                ],
                'alt' => [
                    'replace' => [
                        'pattern' => '^.*',
                        'replace' => 'alt',
                    ]
                ],
                'file' => [
                    'replace' => [
                        'pattern' => '^.*',
                        'replace' => 'file',
                    ]
                ]
            ]
        ];
        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $this->getOutput(),
            $row,
            $config
        );

        $media->process();

        $this->assertEquals('name', $media->entities[0]['name']);
        $this->assertEquals('file', $media->entities[0]['file']);
        $this->assertEquals('alt', $media->entities[0]['alt']);
    }

    /**
     * Ensure that processors apply when using Xpath.
     */
    public function testProcessorXpath()
    {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '#with-image img',
            'options' => [
                'name' => 'data-name',
                'file' => 'data-file',
                'alt' => 'data-alt',
            ],
            'processors' => [
                'name' => [
                    'replace' => [
                        'pattern' => '^.*',
                        'replace' => 'name',
                    ]
                ],
                'alt' => [
                    'replace' => [
                        'pattern' => '^.*',
                        'replace' => 'alt',
                    ]
                ],
                'file' => [
                    'replace' => [
                        'pattern' => '^.*',
                        'replace' => 'file',
                    ]
                ]
            ]
        ];
        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $this->getOutput(),
            $row,
            $config
        );

        $media->process();

        $this->assertEquals('name', $media->entities[0]['name']);
        $this->assertEquals('file', $media->entities[0]['file']);
        $this->assertEquals('alt', $media->entities[0]['alt']);
    }

    /**
     * Ensure that media types passed as options build the filename.
     */
    public function testFileNames()
    {
        $config = [
            'field' => 'field_media',
            'type' => 'media',
            'selector' => '#with-image img',
            'options' => [
                'name' => 'data-name',
                'file' => 'data-file',
                'alt' => 'data-alt',
                'type' => 'custom-media-type',
            ]
        ];

        $output = $this->getOutput(['mergeRow']);
        $output->expects($this->exactly(2))
          ->method('mergeRow')
          ->withConsecutive(['media-custom-media-type'], ['media-custom-media-type-tracked']);

        $row = new \stdClass;
        $media = new Media(
            $this->getCrawler(),
            $output,
            $row,
            $config
        );

        $media->process();
    }

}
