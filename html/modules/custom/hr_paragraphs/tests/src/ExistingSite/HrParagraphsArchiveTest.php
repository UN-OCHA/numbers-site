<?php

// phpcs:ignoreFile

namespace Drupal\Tests\hr_paragraphs\ExistingSite;

use Drupal\Core\Url;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\theme_switcher\Entity\ThemeSwitcherRule;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests sidebar feed.
 */
class HrParagraphsArchiveTest extends ExistingSiteBase {

  protected function renderIt($entity_type, $entity) {
    $theme_rule = ThemeSwitcherRule::load('operation_management');

    // Disable the rule, throws an error on line 184 in
    // html/core/modules/path_alias/src/AliasManager.php.
    $theme_rule->disable()->save();

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity_type);
    $build = $view_builder->view($entity);
    $output = \Drupal::service('renderer')->renderRoot($build);

    $theme_rule->enable()->save();
    return $output->__toString();
  }

  /**
   * Test archived operation.
   */
  public function testWithoutArchived() {
    $site_name = \Drupal::config('system.site')->get('name');
    $operation_title = 'My operation';
    $archive_message = 'This group has been archived.';

    $op_sidebar_content = Paragraph::create([
      'type' => 'group_pages',
    ]);
    $op_sidebar_content->isNew();
    $op_sidebar_content->save();

    $op_sidebar_children = Paragraph::create([
      'type' => 'child_groups',
    ]);
    $op_sidebar_children->isNew();
    $op_sidebar_children->save();

    $op_contacts = Node::create([
      'type' => 'page',
      'title' => 'My contacts',
    ]);
    $op_contacts->setPublished()->save();

    $op_pages = Node::create([
      'type' => 'page',
      'title' => 'My pages',
    ]);
    $op_pages->setPublished()->save();

    $op_about = Node::create([
      'type' => 'page',
      'title' => 'About this operation',
    ]);
    $op_about->setPublished()->save();

    $operation = Group::create([
      'type' => 'operation',
      'label' => $operation_title,
    ]);

    $operation->set('field_offices_page', 'internal:' . $op_contacts->toUrl()->toString());
    $operation->set('field_pages_page', 'internal:' . $op_pages->toUrl()->toString());
    $operation->set('field_pages_page', 'internal:' . $op_pages->toUrl()->toString());
    $operation->set('field_ical_url', 'https://www.example.com/events');
    $operation->set('field_sidebar_menu', [
      $op_sidebar_content,
      $op_sidebar_children,
    ]);

    $operation->setPublished()->save();

    // Add pages to group.
    $operation->addContent($op_contacts, 'group_node:' . $op_contacts->bundle());
    $operation->addContent($op_pages, 'group_node:' . $op_pages->bundle());
    $operation->addContent($op_about, 'group_node:' . $op_about->bundle());

    $cluster_title = 'My cluster';

    $cl_contacts = Node::create([
      'type' => 'page',
      'title' => 'My contacts',
    ]);
    $cl_contacts->setPublished()->save();

    $cl_pages = Node::create([
      'type' => 'page',
      'title' => 'My pages',
    ]);
    $cl_pages->setPublished()->save();

    $cl_about = Node::create([
      'type' => 'page',
      'title' => 'About this cluster',
    ]);
    $cl_about->setPublished()->save();

    $cluster = Group::create([
      'type' => 'cluster',
      'label' => $cluster_title,
    ]);

    $cluster->set('field_offices_page', 'internal:' . $cl_contacts->toUrl()->toString());
    $cluster->set('field_pages_page', 'internal:' . $cl_pages->toUrl()->toString());
    $cluster->set('field_ical_url', 'https://www.example.com/events');

    // Enable checkbox.
    $cluster->set('field_enabled_tabs', [
      ['value' => 'offices'],
      ['value' => 'pages'],
      ['value' => 'events'],
    ]);

    $cluster->setPublished()->save();

    // Add pages to group.
    $cluster->addContent($cl_contacts, 'group_node:' . $cl_contacts->bundle());
    $cluster->addContent($cl_pages, 'group_node:' . $cl_pages->bundle());
    $cluster->addContent($cl_about, 'group_node:' . $cl_about->bundle());

    // Add cluster to operation.
    $operation->addContent($cluster, 'subgroup:' . $cluster->bundle());

    // Check operation on landing page.
    $this->drupalGet($operation->toUrl());
    $this->assertSession()->titleEquals($operation_title . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $operation_title);
    $this->assertSession()->pageTextNotContains($archive_message);

    // Same checks on about page.
    $this->drupalGet($op_about->toUrl());
    $this->assertSession()->titleEquals($op_about->getTitle() . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $op_about->getTitle());
    $this->assertSession()->pageTextNotContains($archive_message);

    // Same checks on cluster landing page.
    $this->drupalGet($cluster->toUrl());
    $this->assertSession()->titleEquals($operation_title . ': ' . $cluster_title . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $cluster_title);
    $this->assertSession()->pageTextNotContains($archive_message);

    // Same checks on about page.
    $this->drupalGet($cl_about->toUrl());
    $this->assertSession()->titleEquals($cl_about->getTitle() . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $cl_about->getTitle());
    $this->assertSession()->pageTextNotContains($archive_message);

    // Check operation tabs.
    $tabs = [
      'contacts' => 'Contacts',
      'pages' => 'Pages',
      'events' => 'Events',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $operation->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->titleEquals($operation_title . ' - ' . $title . ' | ' . $site_name);
      $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $title);
      $this->assertSession()->pageTextNotContains($archive_message);
    }

    // Check cluster tabs.
    $tabs = [
      'contacts' => 'Contacts',
      'pages' => 'Pages',
      'events' => 'Events',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $cluster->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->titleEquals($operation_title . ': ' . $cluster_title . ' - ' . $title . ' | ' . $site_name);
      $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $title);
      $this->assertSession()->pageTextNotContains($archive_message);
    }

    // Render all content in code.
    $this->renderIt('group', $operation);
    $this->renderIt('node', $op_about);
    $this->renderIt('node', $op_contacts);
    $this->renderIt('node', $op_pages);

    $this->renderIt('group', $cluster);
    $this->renderIt('node', $cl_about);
    $this->renderIt('node', $cl_contacts);
    $this->renderIt('node', $cl_pages);
  }

  /**
   * Test archived operation.
   */
  public function testOperationArchived() {
    $site_name = \Drupal::config('system.site')->get('name');
    $operation_title = 'My operation';
    $archive_message = 'Mark operation as archived';

    $op_sidebar_content = Paragraph::create([
      'type' => 'group_pages',
    ]);
    $op_sidebar_content->isNew();
    $op_sidebar_content->save();

    $op_sidebar_children = Paragraph::create([
      'type' => 'child_groups',
    ]);
    $op_sidebar_children->isNew();
    $op_sidebar_children->save();

    $op_contacts = Node::create([
      'type' => 'page',
      'title' => 'My contacts',
    ]);
    $op_contacts->setPublished()->save();

    $op_pages = Node::create([
      'type' => 'page',
      'title' => 'My pages',
    ]);
    $op_pages->setPublished()->save();

    $op_about = Node::create([
      'type' => 'page',
      'title' => 'About this operation',
    ]);
    $op_about->setPublished()->save();

    $operation = Group::create([
      'type' => 'operation',
      'label' => $operation_title,
    ]);

    $operation->set('field_offices_page', 'internal:' . $op_contacts->toUrl()->toString());
    $operation->set('field_pages_page', 'internal:' . $op_pages->toUrl()->toString());
    $operation->set('field_pages_page', 'internal:' . $op_pages->toUrl()->toString());
    $operation->set('field_ical_url', 'https://www.example.com/events');
    $operation->set('field_sidebar_menu', [
      $op_sidebar_content,
      $op_sidebar_children,
    ]);

    $operation->set('field_archive_group', TRUE);
    $operation->set('field_archive_message', [
      'value' => $archive_message,
      'format' => 'basic_html',
    ]);

    $operation->setPublished()->save();

    // Add pages to group.
    $operation->addContent($op_contacts, 'group_node:' . $op_contacts->bundle());
    $operation->addContent($op_pages, 'group_node:' . $op_pages->bundle());
    $operation->addContent($op_about, 'group_node:' . $op_about->bundle());

    $cluster_title = 'My cluster';

    $cl_contacts = Node::create([
      'type' => 'page',
      'title' => 'My contacts',
    ]);
    $cl_contacts->setPublished()->save();

    $cl_pages = Node::create([
      'type' => 'page',
      'title' => 'My pages',
    ]);
    $cl_pages->setPublished()->save();

    $cl_about = Node::create([
      'type' => 'page',
      'title' => 'About this cluster',
    ]);
    $cl_about->setPublished()->save();

    $cluster = Group::create([
      'type' => 'cluster',
      'label' => $cluster_title,
    ]);

    $cluster->set('field_offices_page', 'internal:' . $cl_contacts->toUrl()->toString());
    $cluster->set('field_pages_page', 'internal:' . $cl_pages->toUrl()->toString());
    $cluster->set('field_ical_url', 'https://www.example.com/events');

    // Enable checkbox.
    $cluster->set('field_enabled_tabs', [
      ['value' => 'offices'],
      ['value' => 'pages'],
      ['value' => 'events'],
    ]);

    $cluster->setPublished()->save();

    // Add pages to group.
    $cluster->addContent($cl_contacts, 'group_node:' . $cl_contacts->bundle());
    $cluster->addContent($cl_pages, 'group_node:' . $cl_pages->bundle());
    $cluster->addContent($cl_about, 'group_node:' . $cl_about->bundle());

    // Add cluster to operation.
    $operation->addContent($cluster, 'subgroup:' . $cluster->bundle());

    // Check operation on landing page.
    $this->drupalGet($operation->toUrl());
    $this->assertSession()->titleEquals($operation_title . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $operation_title);
    $this->assertSession()->pageTextContainsOnce($archive_message);

    // Same checks on about page.
    $this->drupalGet($op_about->toUrl());
    $this->assertSession()->titleEquals($op_about->getTitle() . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $op_about->getTitle());
    $this->assertSession()->pageTextContainsOnce($archive_message);

    // Same checks on cluster landing page.
    $this->drupalGet($cluster->toUrl());
    $this->assertSession()->titleEquals($operation_title . ': ' . $cluster_title . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $cluster_title);
    $this->assertSession()->pageTextContainsOnce($archive_message);

    // Same checks on about page.
    $this->drupalGet($cl_about->toUrl());
    $this->assertSession()->titleEquals($cl_about->getTitle() . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $cl_about->getTitle());
    $this->assertSession()->pageTextContainsOnce($archive_message);

    // Check operation tabs.
    $tabs = [
      'contacts' => 'Contacts',
      'pages' => 'Pages',
      'events' => 'Events',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $operation->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->titleEquals($operation_title . ' - ' . $title . ' | ' . $site_name);
      $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $title);
      $this->assertSession()->pageTextContainsOnce($archive_message);
    }

    // Check cluster tabs.
    $tabs = [
      'contacts' => 'Contacts',
      'pages' => 'Pages',
      'events' => 'Events',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $cluster->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->titleEquals($operation_title . ': ' . $cluster_title . ' - ' . $title . ' | ' . $site_name);
      $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $title);
      $this->assertSession()->pageTextContainsOnce($archive_message);
    }

    // Render all content in code.
    $this->renderIt('group', $operation);
    $this->renderIt('node', $op_about);
    $this->renderIt('node', $op_contacts);
    $this->renderIt('node', $op_pages);

    $this->renderIt('group', $cluster);
    $this->renderIt('node', $cl_about);
    $this->renderIt('node', $cl_contacts);
    $this->renderIt('node', $cl_pages);
  }

  /**
   * Test archived operation.
   */
  public function testClusterArchived() {
    $site_name = \Drupal::config('system.site')->get('name');
    $operation_title = 'My operation';
    $archive_message = 'Mark cluster as archived';

    $op_sidebar_content = Paragraph::create([
      'type' => 'group_pages',
    ]);
    $op_sidebar_content->isNew();
    $op_sidebar_content->save();

    $op_sidebar_children = Paragraph::create([
      'type' => 'child_groups',
    ]);
    $op_sidebar_children->isNew();
    $op_sidebar_children->save();

    $op_contacts = Node::create([
      'type' => 'page',
      'title' => 'My contacts',
    ]);
    $op_contacts->setPublished()->save();

    $op_pages = Node::create([
      'type' => 'page',
      'title' => 'My pages',
    ]);
    $op_pages->setPublished()->save();

    $op_about = Node::create([
      'type' => 'page',
      'title' => 'About this operation',
    ]);
    $op_about->setPublished()->save();

    $operation = Group::create([
      'type' => 'operation',
      'label' => $operation_title,
    ]);

    $operation->set('field_offices_page', 'internal:' . $op_contacts->toUrl()->toString());
    $operation->set('field_pages_page', 'internal:' . $op_pages->toUrl()->toString());
    $operation->set('field_pages_page', 'internal:' . $op_pages->toUrl()->toString());
    $operation->set('field_ical_url', 'https://www.example.com/events');
    $operation->set('field_sidebar_menu', [
      $op_sidebar_content,
      $op_sidebar_children,
    ]);

    $operation->setPublished()->save();

    // Add pages to group.
    $operation->addContent($op_contacts, 'group_node:' . $op_contacts->bundle());
    $operation->addContent($op_pages, 'group_node:' . $op_pages->bundle());
    $operation->addContent($op_about, 'group_node:' . $op_about->bundle());

    $cluster_title = 'My cluster';

    $cl_contacts = Node::create([
      'type' => 'page',
      'title' => 'My contacts',
    ]);
    $cl_contacts->setPublished()->save();

    $cl_pages = Node::create([
      'type' => 'page',
      'title' => 'My pages',
    ]);
    $cl_pages->setPublished()->save();

    $cl_about = Node::create([
      'type' => 'page',
      'title' => 'About this cluster',
    ]);
    $cl_about->setPublished()->save();

    $cluster = Group::create([
      'type' => 'cluster',
      'label' => $cluster_title,
    ]);

    $cluster->set('field_offices_page', 'internal:' . $cl_contacts->toUrl()->toString());
    $cluster->set('field_pages_page', 'internal:' . $cl_pages->toUrl()->toString());
    $cluster->set('field_ical_url', 'https://www.example.com/events');

    // Enable checkbox.
    $cluster->set('field_enabled_tabs', [
      ['value' => 'offices'],
      ['value' => 'pages'],
      ['value' => 'events'],
    ]);

    $cluster->set('field_archive_group', TRUE);
    $cluster->set('field_archive_message', [
      'value' => $archive_message,
      'format' => 'basic_html',
    ]);

    $cluster->setPublished()->save();

    // Add pages to group.
    $cluster->addContent($cl_contacts, 'group_node:' . $cl_contacts->bundle());
    $cluster->addContent($cl_pages, 'group_node:' . $cl_pages->bundle());
    $cluster->addContent($cl_about, 'group_node:' . $cl_about->bundle());

    // Add cluster to operation.
    $operation->addContent($cluster, 'subgroup:' . $cluster->bundle());

    // Check operation on landing page.
    $this->drupalGet($operation->toUrl());
    $this->assertSession()->titleEquals($operation_title . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $operation_title);
    $this->assertSession()->pageTextNotContains($archive_message);

    // Same checks on about page.
    $this->drupalGet($op_about->toUrl());
    $this->assertSession()->titleEquals($op_about->getTitle() . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $op_about->getTitle());
    $this->assertSession()->pageTextNotContains($archive_message);

    // Same checks on cluster landing page.
    $this->drupalGet($cluster->toUrl());
    $this->assertSession()->titleEquals($operation_title . ': ' . $cluster_title . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $cluster_title);
    $this->assertSession()->pageTextContainsOnce($archive_message);

    // Same checks on about page.
    $this->drupalGet($cl_about->toUrl());
    $this->assertSession()->titleEquals($cl_about->getTitle() . ' | ' . $site_name);
    $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $cl_about->getTitle());
    $this->assertSession()->pageTextContainsOnce($archive_message);

    // Check operation tabs.
    $tabs = [
      'contacts' => 'Contacts',
      'pages' => 'Pages',
      'events' => 'Events',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $operation->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->titleEquals($operation_title . ' - ' . $title . ' | ' . $site_name);
      $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $title);
      $this->assertSession()->pageTextNotContains($archive_message);
    }

    // Check cluster tabs.
    $tabs = [
      'contacts' => 'Contacts',
      'pages' => 'Pages',
      'events' => 'Events',
    ];

    foreach ($tabs as $tab => $title) {
      $url = Url::fromRoute('hr_paragraphs.operation.' . $tab, [
        'group' => $cluster->id(),
      ]);

      $this->drupalGet($url);
      $this->assertSession()->titleEquals($operation_title . ': ' . $cluster_title . ' - ' . $title . ' | ' . $site_name);
      $this->assertSession()->elementTextEquals('css', 'h1.cd-page-title', $title);
      $this->assertSession()->pageTextContainsOnce($archive_message);
    }

    // Render all content in code.
    $this->renderIt('group', $operation);
    $this->renderIt('node', $op_about);
    $this->renderIt('node', $op_contacts);
    $this->renderIt('node', $op_pages);

    $this->renderIt('group', $cluster);
    $this->renderIt('node', $cl_about);
    $this->renderIt('node', $cl_contacts);
    $this->renderIt('node', $cl_pages);
  }

}
