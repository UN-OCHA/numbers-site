<?php

namespace Drupal\hr_paragraphs\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base controller for Key Figures.
 */
class BaseKeyFiguresController extends ControllerBase {

  /**
   * The HTTP client to fetch the files with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Cache Id.
   *
   * @var string
   */
  protected $cacheId = '';

  /**
   * API URL.
   *
   * @var string
   */
  protected $apiUrl = '';

  /**
   * API Key.
   *
   * @var string
   */
  protected $apiKey = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache) {
    $this->httpClient = $http_client;
    $this->cacheBackend = $cache;
  }

  /**
   * Fetch Key Figures.
   *
   * @param string $iso3
   *   ISO3 of the country we want Key Figures for.
   * @param string $year
   *   Optional year.
   *
   * @return array<string, mixed>
   *   Raw results.
   */
  public function getKeyFigures(string $iso3, $year) : array {
    $query = [
      'iso3' => $iso3,
    ];

    $grouped = FALSE;
    if ($year) {
      $query['year'] = $year;
    }
    else {
      $grouped = TRUE;
    }

    $data = $this->getData('', $query);

    // Sort the values by newest first.
    usort($data, function ($a, $b) {
      return strcmp($b['year'], $a['year']);
    });

    foreach ($data as &$row) {
      $row['date'] = new \DateTime($row['year'] . '-01-01');
    }

    if (!$grouped) {
      $results = [];
      foreach ($data as $row) {
        $results[$row['name']] = $row;
      }

      return $results;
    }

    $results = [];
    foreach ($data as $row) {
      if (!isset($results[$row['name']])) {
        $results[$row['name']] = $row;
        $results[$row['name']]['values'] = [$row];
      }
      else {
        $results[$row['name']]['values'][] = $row;
      }
    }

    return $results;
  }

  /**
   * Fetch Key Figures.
   *
   * @param string $path
   *   API path.
   * @param array $query
   *   Query options.
   *
   * @return array<string, mixed>
   *   Raw results.
   */
  public function getData(string $path, array $query = []) : array {
    $endpoint = $this->apiUrl;
    $api_key = $this->apiKey;

    if (empty($endpoint)) {
      return [];
    }

    $headers = [
      'API-KEY' => $api_key,
      'ACCEPT' => 'application/json',
    ];

    // Construct full URL.
    $fullUrl = $endpoint . $path;

    if (!empty($query)) {
      $fullUrl = $fullUrl . '?' . UrlHelper::buildQuery($query);
    }

    try {
      $this->getLogger('hr_paragraphs_fts_figures')->notice('Fetching data from @url', [
        '@url' => $fullUrl,
      ]);

      $response = $this->httpClient->request(
        'GET',
        $fullUrl,
        ['headers' => $headers],
      );
    }
    catch (RequestException $exception) {
      $this->getLogger('hr_paragraphs_fts_figures')->error('Fetching data from $url failed with @message', [
        '@url' => $fullUrl,
        '@message' => $exception->getMessage(),
      ]);

      if ($exception->getCode() === 404) {
        throw new NotFoundHttpException();
      }
      else {
        throw $exception;
      }
    }

    $body = $response->getBody() . '';
    $results = json_decode($body, TRUE);

    return $results;
  }

  /**
   * Build key figures.
   *
   * @param array<string, mixed> $results
   *   Raw results from API.
   * @param int $max
   *   Number of items to return.
   *
   * @return array<string, mixed>
   *   Results.
   */
  public function buildKeyFigures(array $results, int $max) : array {
    $figures = $this->parseKeyFigures($results);

    // Add the trend and sparkline.
    foreach ($figures as $index => $figure) {
      if (isset($figure['values'])) {
        $figure['trend'] = $this->getKeyFigureTrend($figure['values']);
        $figure['sparkline'] = $this->getKeyFigureSparkline($figure['values']);
      }
      $figures[$index] = $figure;
    }

    // Limit to the number specified by Editor, then return.
    return array_slice($figures, 0, $max);
  }

  /**
   * Parse the Key figures data, validating and sorting the figures.
   *
   * @param array $figures
   *   Figures data from the API.
   *
   * @return array
   *   Array of figures data prepared for display perserving the order but
   *   putting the "recent" ones at the top. Each figure contains the title,
   *   figure, formatted update date, source with a url to the latest source
   *   document and the value history to build the sparkline and the trend.
   */
  protected function parseKeyFigures(array $figures) {
    // Maximum number of days since the last update to still consider the
    // figure as recent.
    $number_of_days = 7;
    $now = new \DateTime();
    $recent = [];
    $standard = [];

    foreach ($figures as $item) {
      // Set the figure status and format its date.
      $item['status'] = 'standard';

      $days_ago = $item['date']->diff($now)->days;
      if ($item['updated']) {
        $item['updated'] = new \DateTime($item['updated']);
        $days_ago = $item['updated']->diff($now)->days;
      }

      if ($days_ago < $number_of_days) {
        $item['status'] = 'recent';
        if ($days_ago === 0) {
          $item['updated'] = $this->t('Updated today');
        }
        elseif ($days_ago === 1) {
          $item['updated'] = $this->t('Updated yesterday');
        }
        else {
          $item['updated'] = $this->t('Updated @days days ago', [
            '@days' => $days_ago,
          ]);
        }
        $recent[] = $item;
      }
      else {
        $item['updated'] = $this->t('Updated @date', [
          '@date' => $item['date']->format('j M Y'),
        ]);
        $standard[] = $item;
      }
    }

    // Preserve the figures order but put recently updated first.
    return array_merge($recent, $standard);
  }

  /**
   * Get the sparkline data for the given key figure history values.
   *
   * @param array $values
   *   Key figure history values.
   */
  protected function getKeyFigureSparkline(array $values) {
    if (empty($values)) {
      return NULL;
    }

    // Find max and min values.
    $numbers = array_column($values, 'value');
    $max = max($numbers);
    $min = min($numbers);

    // Skip if there was no change.
    if ($max === $min) {
      return NULL;
    }

    // The values are ordered by newest first. We retrieve the number of
    // days between the newest and oldest days for the x axis.
    $last = reset($values)['date'];
    $oldest = end($values)['date'];
    $span = $last->diff($oldest)->days;
    if ($span == 0) {
      return NULL;
    }

    // View box dimensions for the sparkline.
    $height = 40;
    $width = 120;

    // Calculate the coordinates of each value.
    $points = [];
    foreach ($values as $value) {
      $diff = $oldest->diff($value['date'])->days;
      $x = ($width / $span) * $diff;
      $y = $height - ((($value['value'] - $min) / ($max - $min)) * $height);
      $points[] = round($x, 2) . ',' . round($y, 2);
    }

    $sparkline = [
      'points' => $points,
    ];

    return $sparkline;
  }

  /**
   * Get the trend data for the given key figure history values.
   *
   * @param array $values
   *   Key figure history values.
   */
  protected function getKeyFigureTrend(array $values) {
    if (count($values) < 2) {
      return NULL;
    }

    // The values are ordered by newest first. We want the 2 most recent values
    // to compute the trend.
    $first = reset($values);
    $second = next($values);

    if ($second['value'] === 0) {
      $percentage = 100;
    }
    else {
      $percentage = (int) round((1 - $first['value'] / $second['value']) * 100);
    }

    if ($percentage === 0) {
      $message = $this->t('No change');
    }
    elseif ($percentage < 0) {
      $message = $this->t('@percentage% increase', [
        '@percentage' => abs($percentage),
      ]);
    }
    else {
      $message = $this->t('@percentage% decrease', [
        '@percentage' => abs($percentage),
      ]);
    }

    $trend = [
      'message' => $message,
      'since' => $this->t('since @date', [
        '@date' => $second['date']->format('j M Y'),
      ]),
    ];

    return $trend;
  }

  /**
   * Get countries.
   */
  public function getCountries() {
    $cid = $this->cacheId . ':countries';

    // Return cached data.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Fetch data.
    $countries = [];
    $data = $this->getData('countries');
    foreach ($data as $row) {
      $countries[$row['value']] = $row['label'];
    }

    // Cache data.
    $this->cacheBackend->set($cid, $countries, Cache::PERMANENT);

    return $countries;
  }

  /**
   * Get countries.
   */
  public function getYears() {
    $cid = $this->cacheId . ':years';

    // Return cached data.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Fetch data.
    $years = [];
    $data = $this->getData('years');
    foreach ($data as $row) {
      $years[$row['value']] = $row['label'];
    }

    // Cache data.
    $this->cacheBackend->set($cid, $years, Cache::PERMANENT);

    return $years;
  }

}
