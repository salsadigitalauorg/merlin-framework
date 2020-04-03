<?php

namespace Merlin\Tests\Functional\Crawler\Type;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Merlin\Crawler\Group\Value;

/**
 * Class to represent the Psr7 Guzzle response object.
 */
class ValueResponseMock
{
    public function __toString()
    {
        return file_get_contents(__DIR__ . '/../../../test.html');
    }
}

/**
 * Ensure that value lookups work as expected.
 */
class ValueTest extends TestCase
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
            ->willReturn(new ValueResponseMock());

    }

    /**
     * Provide configuration to the group type.
     */
    public function provider()
    {
        $host = "http://example.com.au";

        return [
            ["$host/", [
                'selector' => 'h1',
                'pattern' => '/(primary)/',
            ], TRUE],
            ["$host/", [
                'selector' => '//*[@id="wrapper"]/div/table',
                'attribute' => 'summary',
                'pattern' => '/(downloaded)/',
            ], TRUE],
            ["$host/", [
                'selector' => '//*[@id="wrapper"]/div/h1',
                'pattern' => '/(\d+)/'
            ], FALSE],
            ["$host/", [
                'selector' => '//*[@id="wrapper"]/div/h2/span',
                'pattern' => '/(\w+)/',
            ], FALSE],
            ["$host/", [
                'selector' => '//\*[@id="wrapper"]/div/h2/span',
                'pattern' => '/(\w+)/',
            ], FALSE],
        ];
    }

    /**
     * Ensure that match works as expected.
     *
     * @dataProvider provider
     */
    public function testMatch($url, $options, $expected)
    {
        $type = new Value([
            'id' => 'test',
            'options' => $options,
        ]);

        $this->assertEquals($expected, $type->match($url, $this->response));
    }
}
