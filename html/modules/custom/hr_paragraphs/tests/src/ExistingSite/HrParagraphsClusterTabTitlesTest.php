<?php

// phpcs:ignoreFile

namespace Drupal\Tests\hr_paragraphs\ExistingSite;

use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests RSS feed.
 */
class HrParagraphsClusterTabTitlesTest extends ExistingSiteBase {

  /**
   * Test cluster titles.
   */
  public function testClusterTabTitles() {
    $site_name = \Drupal::config('system.site')->get('name');
    $operation_title = 'My operation ' . rand(99999, 9999999);
    $group_title = 'My cluster ' . rand(99999, 9999999);

    $contacts = Node::create([
      'type' => 'page',
      'title' => 'My contacts',
    ]);
    $contacts->setPublished()->save();

    $pages = Node::create([
      'type' => 'page',
      'title' => 'My pages',
    ]);
    $pages->setPublished()->save();

    $operation = Group::create([
      'type' => 'operation',
      'label' => $operation_title,
    ]);
    $operation->setPublished()->save();

    $group = Group::create([
      'type' => 'cluster',
      'label' => $group_title,
    ]);

    $group->set('field_ical_url', 'https://www.example.com/events');
    $group->set('field_offices_page', 'internal:' . $contacts->toUrl()->toString());
    $group->set('field_pages_page', 'internal:' . $pages->toUrl()->toString());
    $group->set('field_reliefweb_documents', 'https://reliefweb.int/updates?view=reports');
    $group->set('field_maps_infographics_link', 'https://reliefweb.int/updates?view=maps');
    $group->set('field_hdx_dataset_link', 'https://data.humdata.org/dataset');
    $group->set('field_reliefweb_assessments', 'https://reliefweb.int/updates?advanced-search=%28F5%29&view=reports');

    // Enable checkbox.
    $group->set('field_enabled_tabs', [
      ['value' => 'events'],
      ['value' => 'offices'],
      ['value' => 'pages'],
      ['value' => 'documents'],
      ['value' => 'maps'],
      ['value' => 'data'],
      ['value' => 'assessments'],
    ]);

    $group->setPublished()->save();

    // Add cluster to operation.
    $operation->addContent($group, 'subgroup:' . $group->bundle());

    // Add pages to cluster.
    $group->addContent($contacts, 'group_node:' . $contacts->bundle());
    $group->addContent($pages, 'group_node:' . $pages->bundle());

    // Check landing page.
    $this->drupalGet($group->toUrl());
    $this->assertSession()->titleEquals($operation_title . ': ' . $group_title . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $group_title);

    $tabs = [
      'events' => 'Events',
      'contacts' => 'Contacts',
      'pages' => 'Pages',
      'reports' => 'Reports',
      'maps' => 'Maps / Infographics',
      'data' => 'Data',
      'assessments' => 'Assessments',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $group->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->titleEquals($operation_title . ': ' . $group_title . ' - ' . $title . ' | ' . $site_name);
      $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $title);
    }

    $tabs = [
      'contacts' => 'My contacts',
      'pages' => 'My pages',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $group->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->pageTextNotContains($title);
    }
  }

}
