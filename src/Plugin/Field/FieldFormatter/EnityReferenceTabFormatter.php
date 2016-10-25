<?php

/**
 * @file
 * Contains \Drupal\entity_ref_tab_formatter\Plugin\Field\FieldFormatter\EnityReferenceTabFormatter.
 */

namespace Drupal\entity_ref_tab_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'enity_reference_tab_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "enity_reference_tab_formatter",
 *   label = @Translation("Enity reference tab formatter"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EnityReferenceTabFormatter extends FormatterBase {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      // Implement default settings.
      'tab_title' => '',
      'tab_body' => '',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Implements the settings form.
    $fieldSettings = $this->getFieldSettings();
    $entity_type_id = $fieldSettings['target_type'];
    $bundles = $fieldSettings['handler_settings']['target_bundles'];
    foreach($bundles as $bundle) {
      $fields_title = $fields_body = array_keys($this->getEntityFields($entity_type_id, $bundle));
    }
    if (!empty($fields_title)) {
      array_unshift($fields_title, 'title');
      $fields_title = array_combine($fields_title, $fields_title);
      $fields_body = array_combine($fields_body, $fields_body);
    }
    $elements['entity_type_id'] = array(
      '#value' => $entity_type_id,
    );
    $elements['tab_title'] = array(
      '#type' => 'select',
      '#options' => $fields_title,
      '#title' => $this->t('Selet the tab title field.'),
      '#default_value' => 'title',
      '#required' => TRUE,
    );
    $elements['tab_body'] = array(
      '#type' => 'select',
      '#options' => $fields_body,
      '#title' => $this->t('Selet the tab body field.'),
      '#default_value' => 'body',
      //'#required' => TRUE,
    );
    $elements['style'] = array(
      '#type' => 'radios',
      '#options' => array(
        0 => 'Tab',
        1 => 'Accordion'
      ),
      '#title' => $this->t('Display Style'),
      '#default_value' => 0,
    );
    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * Helper function.
   */
   private function getEntityFields($entity_type_id, $bundle) {
    $entityManager = \Drupal::service('entity.manager');
    $fields = [];
    if (!empty($entity_type_id)) {
      $fields = array_filter(
        $entityManager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
        return $field_definition instanceof FieldConfigInterface;
      }
      );
    }
    return $fields;
  }
  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $title_field = $this->getSetting('tab_title');
    $body_field = $this->getSetting('tab_body');
    $entity_type_id = $this->getFieldSettings()['target_type'];
    $tabs = array();
    foreach ($items as $delta => $item) {
      $id = $item->getValue()['target_id'];
      $content = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($id);
      $title = $content->get($title_field)->getValue()[0]['value'];
      $body = $content->get($body_field)->getValue()[0]['value'];
      $tabs[$id] = array(
        'title' => $title,
        'body' => $body
      );
      //$elements[$delta] = ['#markup' => "Hell0"];
    }
    $elements[$delta] = array(
      '#theme' => 'entity_ref_tab_formatter',
      '#tabs' => $tabs,
      '#attached' => array(
        'library' =>  array(
          'entity_ref_tab_formatter/tab_formatter'
        ),
      ),
    );

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
