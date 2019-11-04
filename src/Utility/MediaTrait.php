<?php

namespace Migrate\Utility;

use Ramsey\Uuid\Uuid;
use GuzzleHttp\Psr7;

/**
 * A trait to be used for media representations throughout the project.
 *
 * CMS' typically allow a lot of flexibility with media so the framework needs
 * to try and cater for all scenarios. This means that media can be scraped
 * from in fields and also as a field of its own. To allow for reuse between
 * Type and Processor this trait abstracts out the common methods that we
 * would need to build a generic media reprensetation.
 */
trait MediaTrait
{


    /**
     * Accessor for data attributes with default values.
     */
    public function getEmbeddedAttributes()
    {
        $defaults = [
            'data_embed_button'         => 'tide_media',
            'data_entity_embed_display' => 'view_mode:media.embedded',
            'data_entity_type'          => 'media',
        ];
        $attributes = isset($this->config['attributes']) ? $this->config['attributes'] : [];

        return array_merge($defaults, $attributes);

    }//end getEmbeddedAttributes()


    /**
     * Get a repeatable UUID for the media item.
     *
     * Attempts to match a UUID in the name or filename of the found media item.
     * If a valid uuid is found it will be used otherwise one will be generated
     * that can be repeated based on the file name.
     *
     * @param string $name
     *   The media item name.
     * @param string $filename
     *   The media item filename.
     *
     * @return string
     *   A valid Uuid3.
     */
    public function getUuid($name, $filename)
    {
        $pattern = '/[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}/';

        if (preg_match($pattern, $name, $matches) !== false) {
            $uuid = reset($matches);
        }

        if (preg_match($pattern, $filename, $matches) !== false) {
            $uuid = reset($matches);
        }

        return empty($uuid) ? Uuid::uuid3(Uuid::NAMESPACE_DNS, $filename) : $uuid;

    }//end getUuid()


    /**
     * Build the asset URL.
     *
     * @param string $uri
     *   The media asset URI.
     *
     * @return string
     *   The absolute asset URL.
     */
    protected function getFileUrl($uri)
    {

        if (isset($this->config['extra']['filename_callback'])) {
            $url = Callback::getResult($this->config['extra']['filename_callback'], $this, $uri);
            return $url;
        }

        // Return absolute URLs without further processing.
        if (strncasecmp($uri, "http", 4) === 0) {
            return urldecode($uri);
        }

        // Resolve relative paths.
        try {
            $uri = Psr7\uri_for($uri);
            $uri = Psr7\UriResolver::resolve(Psr7\uri_for($this->crawler->getUri()), $uri);
        } catch (Exception $e) {
            throw new \Exception('Invalid file URL for media.');
        }

        return urldecode((string) $uri);

    }//end getFileUrl()


    /**
     * Get the Drupal entity embed markup string.
     *
     * @return string
     *   A Drupal entity embed markup.
     */
    protected function getDrupalEntityEmbed($uuid)
    {
        $data = '';

        foreach ($this->getEmbeddedAttributes() as $attr => $value) {
            $attr  = strtolower(str_replace('_', '-', $attr));
            $data .= " {$attr}=\"$value\"";
        }

        $data .= " data-entity-uuid=\"{$uuid}\"";
        $data  = trim($data);

        return "<drupal-entity {$data}></drupal-entity>";

    }//end getDrupalEntityEmbed()


    /**
     * Get the Drupal linkit markup string.
     *
     * @param \DomCrawler\Crawler $node
     *   The Dom node for the media entity to link
     * @param String $url
     *   URL of the original asset
     * @param String $uuid
     *   uuid value of the media entity
     * @return string
     *   A Drupal linkit tag to the entity.
     */
    protected function getDrupalLinkitEmbed($node, $url, $uuid)
    {

        $linkText = !empty($node->textContent) ? $node->textContent : basename($url);
        $defaultLink = "/sites/default/files/".basename($url);

        $data = " data-entity-uuid=\"{$uuid}\"";
        $data .= " data-entity-substitution=\"media\"";
        $data .= " data-entity-type=\"media\"";
        $data  = trim($data);

        return "<a href=\"{$defaultLink}\" {$data}>{$linkText}</a>";

    }//end getDrupalLinkitEmbed()


    /**
     * Determine whether media asset is on an external domain.
     *
     * @param String $url
     *   URL of the original asset
     */
    protected function checkExternalUrl($url) {

      // Relative urls are ok.
      if (substr($url, 0, 4) != "http") {
        return false;
      }

      $mainConfig = $this->output->getConfig();
      $domain = $mainConfig->get('domain');

      $mediaDomain = str_ireplace('www.', '', parse_url($url, PHP_URL_HOST));
      if (!strpos($domain, $mediaDomain)) {
        return true;
      }

      return false;

    }//end checkExternalUrl()


}
