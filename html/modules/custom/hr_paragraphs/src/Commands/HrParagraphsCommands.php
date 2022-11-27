<?php

namespace Drupal\hr_paragraphs\Commands;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Yaml;

/**
 * Drush commandfile.
 *
 * @property \Consolidation\Log\Logger $logger
 */
class HrParagraphsCommands extends DrushCommands {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleExtensionList $module_extensionList, UuidInterface $uuid_service) {
    $this->configFactory = $config_factory;
    $this->moduleExtensionList = $module_extensionList;
    $this->uuidService = $uuid_service;
  }

  /**
   * Create paragraph type for keyfigures.
   *
   * @command hr_paragraphs:add-keyfigures
   * @validate-module-enabled hr_paragraphs
   * @usage hr_paragraphs:import-operations idps_key_figures "Figures - IDPs" "Description" "Default title"
   *   Create paragraph type.
   */
  public function importOperations(string $machine_name, string $label, string $description, string $title) {

    $config = [
      'core.entity_form_display.paragraph.idps_key_figures.default.yml',
      'core.entity_view_display.paragraph.idps_key_figures.default.yml',
      'field.field.paragraph.idps_key_figures.field_country.yml',
      'field.field.paragraph.idps_key_figures.field_figures.yml',
      'field.field.paragraph.idps_key_figures.field_title.yml',
      'field.field.paragraph.idps_key_figures.field_year.yml',
      'paragraphs.paragraphs_type.idps_key_figures.yml',
    ];

    foreach ($config as $filename) {
      $config_file = $this->moduleExtensionList->getPath('hr_paragraphs') . '/config/optional/' . $filename;
      $yaml = file_get_contents($config_file);

      // Rename.
      $yaml = str_replace('idps_key_figures', $machine_name, $yaml);
      $yaml = str_replace('Figures - IDPs', $label, $yaml);
      $yaml = str_replace('IDPs figures', $description, $yaml);
      $yaml = str_replace('Internally displaced persons', $title, $yaml);

      $data = Yaml::parse($yaml);
      $data['uuid'] = $this->uuidService->generate();

      $new_name = str_replace('idps_key_figures', $machine_name, $filename);
      $new_name = str_replace('.yml', '', $new_name);
      $this->configFactory->getEditable($new_name)->setData($data)->save(TRUE);

      // Grant permissions.
      $read = [
        'view paragraph content inform_key_figures',
      ];
      $write = [
        'create paragraph content inform_key_figures',
        'delete paragraph content inform_key_figures',
        'update paragraph content inform_key_figures',
        'view paragraph content inform_key_figures',
      ];

      user_role_grant_permissions('anonymous', $read);
      user_role_grant_permissions('authenticated', $read);
      user_role_grant_permissions('administrator', $write);
      user_role_grant_permissions('global_editor', $write);
    }

  }

}
