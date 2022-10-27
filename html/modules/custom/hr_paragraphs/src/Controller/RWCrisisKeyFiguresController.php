<?php

namespace Drupal\hr_paragraphs\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\ClientInterface;

/**
 * Page controller for Key Figures.
 */
class RWCrisisKeyFiguresController extends BaseKeyFiguresController {

  /**
   * {@inheritdoc}
   */
  protected $cacheId = 'rw_crisis';

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache) {
    parent::__construct($http_client, $cache);

    $this->apiUrl = $this->config('hr_paragraphs.settings')->get('rw_crisis_api_url');
    $this->apiKey = $this->config('hr_paragraphs.settings')->get('rw_crisis_api_key');
  }

}
