<?php

namespace Merlin\Tests\Functional\Crawler\Type;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Merlin\Crawler\Group\Path;

class PathTest extends TestCase
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
    }


    /**
     * Provide a list of cases to test.
     */
    public function provider()
    {
        $host = 'http://example.com.au';

        // URL path, pattern, expected.
        return [
            ["$host/many/leaves/on/path", "/many/*", TRUE],
            ["$host/many2/leaves/on/path", "/many/*", FALSE],
            ["$host/part/wildcard/match", "/*/wildcard/match", TRUE],
            ["$host/part/wildcard/mismatch", "/*/wildcard/match", FALSE],
            ["$host/part/example/match", "/part/*/part", FALSE],
            ["$host/part/wildcard/mismatch", "/*/*/match", FALSE],
            ["$host/part/wildcard/match", "/*/*/match", TRUE],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testMatch($url, $pattern, $expected)
    {
        $type = new Path([
            'id' => 'test',
            'options' => ['pattern' => $pattern],
        ]);

        $this->assertEquals($expected, $type->match($url, $this->response));
    }


}
