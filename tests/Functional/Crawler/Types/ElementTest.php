<?php

namespace Merlin\Tests\Functional\Crawler\Type;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Merlin\Crawler\Group\Element;

/**
 * Class to represent the Psr7 Guzzle response object.
 */
class ElementResponseMock
{
    public function __toString()
    {
        return file_get_contents(__DIR__ . '/../../../test.html');
    }
}

/**
 * Ensure that element lookups work as expected.
 */
class ElementTest extends TestCase
{

    /**
     * Mock response object.
     *
     * @var Psr\Http\Message\ResponseInterface
     */
    protected $response;

    /**
     * Set up the tests.
     */
    public function setUp()
    {
        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->response->expects($this->once())
            ->method('getBody')
            ->willReturn(new ElementResponseMock());

    }

    /**
     * Provide configuration to the group type.
     */
    public function provider()
    {
        $host = "http://example.com.au";

        return [
            ["$host/", 'h1', TRUE],
            ["$host/", '//*[@id="wrapper"]/div/h2', TRUE],
            ["$host/", 'h7', FALSE],
            ["$host/", '//*[@id="wrapper"]/div/h2/span', FALSE],
            ["$host/", '//\*[@id="wrapper"]/div/h2/span', FALSE],
        ];
    }

    /**
     * Ensure that match works as expected.
     *
     * @dataProvider provider
     */
    public function testMatch($url, $selector, $expected)
    {
        $type = new Element([
            'id' => 'test',
            'options' => ['selector' => $selector],
        ]);

        $this->assertEquals($expected, $type->match($url, $this->response));
    }
}
