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
 * Page controller for Key Figures.
 */
class FtsKeyFiguresController extends ControllerBase {

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
   * {@inheritdoc}
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache) {
    $this->httpClient = $http_client;
    $this->cacheBackend = $cache;
  }

  /**
   * Load multiple plans.
   */
  public function getMultipleKeyFigures(array $plan_ids, string $iso3, bool $combined): array {
    $results = [];

    foreach ($plan_ids as $plan_id) {
      if ($combined) {
        if (empty($results)) {
          $results = $this->getKeyFigures($plan_id, $iso3, $combined);
        }
        else {
          $data = $this->getKeyFigures($plan_id, $iso3, $combined);
          foreach ($data as $key => $values) {
            $results[$key]['value'] += $values['value'] ?? 0;
          }
        }
      }
      else {
        $results = $results + $this->getKeyFigures($plan_id, $iso3, $combined);
      }
    }

    return $results;
  }

  /**
   * Fetch Key Figures.
   *
   * @param string $plan_id
   *   Plan Id.
   * @param string $iso3
   *   ISO3 of the country we want Key Figures for.
   * @param bool $combined
   *   If TRUE data will be using the same keys so they can be aggregated.
   *
   * @return array<string, mixed>
   *   Raw results.
   */
  public function getKeyFigures(string $plan_id, string $iso3, bool $combined) : array {
    $plans = $this->getPlanCodesByIso3($iso3);
    $data_total_requirement = $this->getData('public/plan/id/' . $plan_id);
    $data = $this->getData('public/fts/flow', ['planId' => $plan_id]);

    // Extract years from the plan.
    $years = [];
    if (isset($data_total_requirement['years']) && is_array($data_total_requirement['years'])) {
      foreach ($data_total_requirement['years'] as $year_info) {
        $years[] = $year_info['year'];
      }
    }
    $year = implode(', ', $years);

    $total_requirement = $data_total_requirement['revisedRequirements'];
    $funding_total = $data['incoming']['fundingTotal'];
    $unmet_requirement = ($total_requirement - $funding_total);

    $url = 'https://fts.unocha.org/appeals/' . $plan_id . '/summary';
    $key_suffix = '_' . $plan_id;
    $title_suffix = ' (' . $year . ')';

    if ($combined) {
      $key_suffix = '';
      $title_suffix = '';
    }

    return [
      'total_requirement' . $key_suffix => [
        'name' => $this->t('Total requirements') . $title_suffix,
        'value' => $total_requirement,
        'date' => $data_total_requirement['updatedAt'],
        'url' => $url,
        'source' => 'FTS - ' . $plans[$plan_id],
      ],
      'funding_total' . $key_suffix => [
        'name' => $this->t('Funding total') . $title_suffix,
        'value' => $funding_total,
        'date' => $data_total_requirement['updatedAt'],
        'url' => $url,
        'source' => 'FTS - ' . $plans[$plan_id],
      ],
      'unmet_requirement' . $key_suffix => [
        'name' => $this->t('Unmet requirements') . $title_suffix,
        'value' => $unmet_requirement,
        'date' => $data_total_requirement['updatedAt'],
        'url' => $url,
        'source' => 'FTS - ' . $plans[$plan_id],
      ],
    ];
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
    $endpoint = $this->config('hr_paragraphs.settings')->get('fts_api_url');
    if (empty($endpoint)) {
      return [];
    }

    if (strpos($endpoint, '@') !== FALSE) {
      $auth = substr($endpoint, 8, strpos($endpoint, '@') - 8);
      $endpoint = substr_replace($endpoint, '', 8, strpos($endpoint, '@') - 7);
      $headers['Authorization'] = 'Basic ' . base64_encode($auth);
    }

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

    return $results['data'];
  }

  /**
   * Build reliefweb objects.
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
    $options = ['options' => ['min_range' => 0]];

    foreach ($figures as $item) {
      // Validate url.
      if (empty($item['url']) || !filter_var($item['url'], FILTER_VALIDATE_URL)) {
        continue;
      }

      // Validate name.
      if (empty($item['name']) || ctype_space($item['name'])) {
        continue;
      }
      $item['name'] = trim($item['name']);

      // Validate value (integer > 0).
      // Currently, the key figures are for population in need etc. so there is
      // no interest in showing a card with a value of '0' so we skip it.
      if (empty($item['value']) || !filter_var($item['value'], FILTER_VALIDATE_FLOAT, $options)) {
        continue;
      }

      // Validate date.
      $item['date'] = !empty($item['date']) ? date_create($item['date']) : FALSE;
      if ($item['date'] === FALSE) {
        continue;
      }

      // Validate source.
      if (empty($item['source']) || ctype_space($item['source'])) {
        continue;
      }
      $item['source'] = trim($item['source']);

      // Validate list of past figures.
      if (isset($item['values']) && is_array($item['values'])) {
        // Sanitize and sort the past figures for the sparkline and trend.
        $values = [];
        foreach ($item['values'] as $value) {
          // A value of '0' is acceptable to construct the sparkline as opposed
          // to the main figure so we don't use 'empty()'.
          if (!isset($value['value'], $value['date']) || !filter_var($value['value'], FILTER_VALIDATE_INT, $options)) {
            continue;
          }
          $date = date_create($value['date']);
          // Skip if the date is invalid.
          if ($date === FALSE) {
            continue;
          }
          $iso = $date->format('c');
          // Skip if there is already a more recent value for the same date.
          if (!isset($values[$iso])) {
            $values[$iso] = [
              'value' => (int) $value['value'],
              'date' => $date,
            ];
          }
        }
        // Sort the past values by newest first.
        krsort($values);
        $item['values'] = $values;
      }

      // Set the figure status and format its date.
      $item['status'] = 'standard';
      $days_ago = $item['date']->diff($now)->days;

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
   * Get plan codes by country iso3 code.
   */
  public function getPlanCodesByIso3($iso3) {
    $cid = 'fts_plans:codes:' . $iso3;

    // Return cached data.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    // Cache gets filled by this.
    $this->getPlansByIso3($iso3);

    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    return [];
  }

  /**
   * Get plans by country iso3 code.
   */
  public function getPlansByIso3($iso3) {
    $cid = 'fts_plans:country:' . $iso3;
    $cid_codes = 'fts_plans:codes:' . $iso3;

    // Return cached data.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $result = [];
    $codes = [];

    $plans = $this->getData('public/plan/country/' . $iso3);
    foreach ($plans as $plan) {
      $result[$plan['id']] = $plan['planVersion']['name'] . ' [' . $plan['planVersion']['code'] . ']';
      $codes[$plan['id']] = $plan['planVersion']['code'];
    }

    asort($result, SORT_NATURAL | SORT_FLAG_CASE);

    $this->cacheBackend->set($cid, $result, Cache::PERMANENT);
    $this->cacheBackend->set($cid_codes, $codes, Cache::PERMANENT);

    return $result;
  }

  /**
   * Get plans by country iso3 code and year.
   */
  public function getPlansByIso3AndYear($iso3, $year) {
    $cid = 'fts_plans:country:' . $iso3 . ':year:' . $year;

    // Return cached data.
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data;
    }

    $result = [];

    $plans = $this->getData('public/plan/country/' . $iso3);
    foreach ($plans as $plan) {
      if (strpos($plan['planVersion']['startDate'], $year) === 0) {
        $result[$plan['id']] = [
          'name' => $plan['planVersion']['name'],
          'code' => $plan['planVersion']['code'],
          'country_id' => $plan['locations'][0]['id'] ?? '',
        ];
      }
    }

    asort($result, SORT_NATURAL | SORT_FLAG_CASE);

    $this->cacheBackend->set($cid, $result, Cache::PERMANENT);

    return $result;
  }

  /**
   * Get all plans.
   */
  public function getAllPlans($countries) {
    $result = [];

    foreach ($countries as $iso3) {
      $result = $result + $this->getPlansByIso3($iso3);
    }

    return $result;
  }

}
