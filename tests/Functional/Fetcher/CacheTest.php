<?php


use Merlin\Fetcher\Cache;
use PHPUnit\Framework\TestCase;

class CacheTest  extends TestCase
{

  private $cache;
  private $domain = 'merlin-test-domain.com';
  private $url = '/test-page.html';
  private $cacheDir = '/tmp';
  private $content = null;
  private $contentHash = null;

  public function setUp() {
    $this->cache = new Cache($this->domain, $this->cacheDir);
    $this->content = '<html><body><h1>SOME MARKUP</h1></body></html>';
  }//end setUp()


  /**
   * @group cache
   */
  public function testBasicCacheWrite() {
      $url = $this->domain . $this->url;
      $this->cache->put($url, $this->content);
      $filename = $this->cache->getFilename($url);
      $this->assertFileExists($filename);
      $this->cache->unlink($url);
      $this->assertFileNotExists($filename);
  }//end testBasicCacheWrite()


  /**
   * @depends testBasicCacheWrite
   * @group cache
   */
  public function testBasicCacheRead() {
    $url = $this->domain . $this->url;
    $this->cache->put($url, $this->content);
    $contents = $this->cache->get($url);
    $filename = $this->cache->getFilename($url);
    $this->assertEquals($this->content, $contents);
    $this->cache->unlink($url);
    $this->assertFileNotExists($filename);
  }//end testBasicCacheRead()


}//end class
