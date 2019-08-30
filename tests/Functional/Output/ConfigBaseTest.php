<?php

namespace Migrate\Tests\Functional\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Migrate\Parser\ConfigBase;

/**
 * Ensure that the config base can correctly instantiate.
 */
class ConfigBaseTest extends TestCase
{

  /**
   * Get a standard sample configuration array.
   *
   * @return array
   *   The configuration array.
   */
  public function getBaseConfig()
  {
    return [
      'domain' => 'http://example.com',
      'urls' => [
        '/',
      ],
      'entity_type' => 'test',
      'mappings' => [
        [
          'type' => 'text',
          'selector' => 'h1',
        ]
      ],
    ];
  }

  /**
   * Create a tmp file to use as the configuration source.
   *
   * @see https://www.php.net/manual/en/function.tmpfile.php
   */
  public function setUp()
  {
    $this->fh = tmpfile();
    $meta_data = stream_get_meta_data($this->fh);
    $this->file = $meta_data["uri"];
  }

  /**
   * Delete the tmp file.
   *
   * @see https://www.php.net/manual/en/function.tmpfile.php
   */
  public function tearDown()
  {
    fclose($this->fh);
  }

  /**
   * Write a configuration array to the tmpfile.
   */
  public function writeConfig(array $config) {
    fwrite($this->fh, Yaml::dump($config));
  }

  /**
   * Ensure that non-array URLs are correctly mapped.
   */
  public function testInvalidUrl()
  {
    $config = array_merge($this->getBaseConfig(), ['urls' => '/']);
    $this->writeConfig($config);

    $config = $this->getMockForAbstractClass(ConfigBase::class, [$this->file]);

    $this->assertTrue(is_array($config->get('urls')));
  }

  /**
   * Ensure that entity type is required.
   */
  public function testInvalidEntityType()
  {
    $config = $this->getBaseConfig();
    unset($config['entity_type']);
    $this->writeConfig($config);

    $this->expectException(\Exception::class);

    $this->getMockForAbstractClass(ConfigBase::class, [$this->file]);
  }

  /**
   * Ensure that mappings are required.
   */
  public function testInvalidMappings()
  {
    $config = $this->getBaseConfig();
    unset($config['mappings']);
    $this->writeConfig($config);

    $this->expectException(\Exception::class);

    $this->getMockForAbstractClass(ConfigBase::class, [$this->file]);
  }

  /**
   * Ensure the iterator correctly finishes the sequence.
   */
  public function testGetMappings()
  {
    $config = $this->getBaseConfig();
    $this->writeConfig($config);
    $base = $this->getMockForAbstractClass(ConfigBase::class, [$this->file]);

    $total = 0;

    while ($base->getMapping())
    {
      $total++;
    }

    $this->assertEquals(count($config['mappings']), $total);

  }

  /**
   * Ensure the reset method can return the object to the default state.
   */
  public function testReset()
  {
    $config = $this->getBaseConfig();
    $this->writeConfig($config);
    $base = $this->getMockForAbstractClass(ConfigBase::class, [$this->file]);

    while ($base->getMapping());
    while ($base->getUrl());

    $base->reset();

    $this->assertEquals(count($config['urls']), count($base->get('urls')));
    $this->assertEquals(count($config['mappings']), count($base->get('mappings')));
  }

}
