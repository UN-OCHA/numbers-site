<?php

namespace Drupal\hr_mailchimp\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manage subscription for user.
 */
class HrMailchimpAlertForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hr_mailchimp_alert_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $selected_countries = []) {
    $options = $this->getGroups();
    if (!empty($selected_countries)) {
      $options = array_intersect_key($options, $selected_countries);
    }

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#maxlength' => 2048,
      '#default_value' => '',
      '#required' => TRUE,
    ];

    $form['groups'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select the locations you want to get an alert for.'),
      '#options' => $options,
      '#default_value' => [],
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#theme_wrappers' => [
        'fieldset' => [
          '#id' => 'actions',
          '#title' => $this->t('Form actions'),
          '#title_display' => 'invisible',
        ],
      ],
      '#weight' => 99,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['actions']['reset'] = [
      '#type' => 'submit',
      '#name' => 'reset',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetForm'],
    ];

    return $form;
  }

  /**
   * Get groups.
   */
  protected function getGroups() {
    $groups = $this->entityTypeManager->getStorage('group')->loadByProperties([
      'status' => 1,
    ]);
    $options = [];

    foreach ($groups as $group) {
      $options[$group->id()] = $group->label();
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $email = $form_state->getValue('email');
    $groups = array_filter($form_state->getValue('groups'));
    $groups = array_values($groups);
    $group_options = $this->getGroups();
    $tags = [];

    // Add 2 tags for each group.
    foreach ($groups as $group_id) {
      $tags[] = 'Num - ' . $group_id;
      $tags[] = 'Num - ' . $group_options[$group_id];
    }

    if (hr_mailchimp_subscribe_to_alerts($email, $tags)) {
      $this->messenger()->addMessage($this->t('Succesfully subscribed, you will get an alert when numbers are updated. Do not forget to confirm your subscription.'));
    }
    else {
      $this->messenger()->addWarning($this->t('We were unable to subscribe you, please try again later.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setProgrammed(FALSE);
    $form_state->setRedirect('<current>');
  }

}
