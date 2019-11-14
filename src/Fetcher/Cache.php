<?php

/**
 * Basic file-based caching for storing the fetched HTML content.
 */

namespace Migrate\Fetcher;

class Cache
{

  /**
   * Path to the directory containing the files for this cached domain.
   * @var string
   */
  private $path;

  /** @var boolean  Debug function that writes a small file alongside cache file containing the url */
  private $storeUrls = false;


  /**
   * Cache constructor.
   *
   * @param        $domain
   * @param string $cacheDir
   *
   * @throws \Exception
   */
  public function __construct($domain, $cacheDir="/tmp/merlin_cache")
  {
    if (!empty($cacheDir) && !is_dir($cacheDir)) {
      if (!mkdir($cacheDir, 0744, true)) {
        throw new \Exception("Cannot initialise cache!  Could not create cache dir.");
      }
    }

    if (strstr($cacheDir, ".") || strstr($cacheDir, "..")) {
      throw new \Exception("Cannot initialise cache!  You must use an absolute path for the cache dir.");
    }

    if (!empty($cacheDir) && is_dir($cacheDir) && is_writable($cacheDir) && realpath($cacheDir) != "/") {
      $domain = rtrim($domain, '/');
      $domain = preg_replace('/[^a-z0-9]+/','-', strtolower($domain));
      $this->path = $cacheDir.DIRECTORY_SEPARATOR.$domain;
    } else {
      throw new \Exception("Cannot initialise cache!  Storage root isn't a dir or writable.");
    }

  }//end __construct()


  /**
   * Generates hash of the content data.
   * @param $data
   *
   * @return string
   */
  private function hash($data) {
    return sha1($data);

  }//end hash()


  /**
   * Returns the storage directory path.
   * @param $hash
   *
   * @return string
   */
  private function getStoreDir($hash) {
    $dir = strtolower(substr($hash, 0 , 2));
    return $dir;

  }//end getStoreDir()


  /**
   * Returns the full filename to this cache key.
   * @param $url
   *
   * @return string
   */
  public function getFilename($url) {
    $hash = $this->hash($url);
    $dir = $this->getStoreDir($hash);
    $file = $this->path.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$hash;
    return $file;

  }//end getFilename()


  /**
   * Writes the url content and to disk.
   * @param $url
   * @param $contents
   */
  public function put($url, $contents) {
    if (!empty($contents)) {
      $fileContents = $this->getFilename($url);
      self::fileForceContents($fileContents, $contents);

      if ($this->storeUrls) {
        $fileUrl = $this->getFilename($url).".url";
        self::fileForceContents($fileUrl, $url);
      }
    }

  }//end put()


  /**
   * Fetches url content from disk if it exists.
   * @param $url
   *
   * @return false|string|null
   */
  public function get($url) {
    $filename = $this->getFilename($url);

    if (is_file($filename)) {
      return file_get_contents($filename);
    }

    return null;

  }//end get()


  /**
   * Checks if an entry exists in the cache for given url.
   * @param $url
   *
   * @return bool
   */
  public function exists($url) {
    $filename = $this->getFilename($url);
    return is_file($filename) && filesize($filename) > 0;

  }//end exists()


  /**
   * Deletes a cache file from disk.
   *
   * @param $url
   *
   * @return bool
   */
  public function unlink($url) {

    $success = false;
    $filename = $this->getFilename($url);
    if (is_file($filename)) {
      $success = unlink($filename);
    }

    $fileUrl = $this->getFilename($url).".url";
    if (is_file($fileUrl)) {
      $success = unlink($fileUrl);
    }

    return $success;

  }//end unlink()


  /**
   * Sets option to store a .url file next to the content file,
   * which contains the url used to generate the hashed filename.
   * @param bool $store
   */
  public function setStoreUrls(bool $store) {
    $this->storeUrls = $store;

  }//end setStoreUrls()


  /**
   * @return bool
   */
  public function getStoreUrls() {
    return $this->storeUrls;

  }//end getStoreUrls()


  /**
   * Returns the absolute path of the top-level directory of
   * this cache (i.e. the dir with the name of the domain).
   * @return string
   */
  public function getPath() {
    return $this->path;

  }//end getPath()


  /**
   * Recursively deletes a directory contents and optionally the containing dir.
   *
   * @param string $dir The directory path.
   * @param bool   $removeRootDir Remove the top level directory.
   *
   * @return bool TRUE on success, otherwise FALSE.
   */
  private function unlinkDir(
    $dir,
    $removeRootDir=false)
  {

    if (empty($dir) || !file_exists($dir) || realpath($dir) == "/" || strstr("..", $dir)) {
      return false;
    }

    // /** @var SplFileInfo[] $files */
    $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileInfo) {
      if ($fileInfo->isDir()) {
        if (false === rmdir($fileInfo->getRealPath())) {
          return false;
        }
      } else {
        if (false === unlink($fileInfo->getRealPath())) {
          return false;
        }
      }
    }

    // Delete root dir?
    if ($removeRootDir && is_dir($dir)) {
      rmdir($dir);
    }

  }//end unlinkDir()


  /**
   * Removes the cache contents for this domain and, optionally, the
   * containing directory.
   *
   * @param bool $removeDomainDir
   */
  public function clearCache($removeDomainDir=false) {
    $this->unlinkDir($this->path, $removeDomainDir);

  }//end clearCache()


  /**
   * Creates the path to the file and writes the contents.  Returns
   * false on failure, bytes on true.  Use === to check for failed case.
   * @param $fullPathToFile
   * @param $contents
   * @return int|false
   */
  public static function fileForceContents($fullPathToFile, $contents){
    $parts = explode(DIRECTORY_SEPARATOR, $fullPathToFile);
    $file = array_pop($parts);
    $path = implode(DIRECTORY_SEPARATOR, $parts);
    if (!is_dir($path)) {
      if (mkdir($path, 0744, true)) {
        return file_put_contents($fullPathToFile, $contents);
      } else {
        return false;
      }
    } else {
      return file_put_contents($fullPathToFile, $contents);
    }

  }//end fileForceContents()


}//end class
