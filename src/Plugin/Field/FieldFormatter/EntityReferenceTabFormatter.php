<?php

namespace Drupal\entity_ref_tab_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Plugin implementation of the 'entity_reference_tab_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_tab_formatter",
 *   label = @Translation("Entity reference tab formatter"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class EntityReferenceTabFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
        'tab_title' => '',
        'tab_body' => '',
        'style' => '',
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Implements the settings form.
    $field_settings = $this->getFieldSettings();
    $entity_type_id = $field_settings['target_type'];
    $bundles = $field_settings['handler_settings']['target_bundles'];
    foreach ($bundles as $bundle) {
      $fields_title = $fields_body = array_keys($this->getEntityFields($entity_type_id, $bundle));
    }
    if (!empty($fields_title)) {
      array_unshift($fields_title, 'title');
      $fields_title = array_combine($fields_title, $fields_title);
      $fields_body = array_combine($fields_body, $fields_body);
    }
    $elements['entity_type_id'] = [
      '#value' => $entity_type_id,
    ];
    $elements['tab_title'] = [
      '#type' => 'select',
      '#options' => $fields_title,
      '#title' => $this->t('Selet the tab title field.'),
      '#default_value' => $this->getSetting('tab_title'),
      '#required' => TRUE,
    ];
    $elements['tab_body'] = [
      '#type' => 'select',
      '#options' => $fields_body,
      '#title' => $this->t('Selet the tab body field.'),
      '#default_value' => $this->getSetting('tab_body'),
    ];
    $elements['style'] = [
      '#type' => 'radios',
      '#options' => [
        'tab' => 'Tab',
        'accordion' => 'Accordion',
      ],
      '#title' => $this->t('Display Style'),
      '#default_value' => $this->getSetting('style'),
    ];
    return $elements + parent::settingsForm($form, $form_state);
  }

  /**
   * Helper function.
   */
  private function getEntityFields($entity_type_id, $bundle) {
    $entity_manager = \Drupal::service('entity_field.manager');
    $fields = [];
    if (!empty($entity_type_id)) {
      $fields = array_filter(
        $entity_manager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
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
    $style = $this->getSetting('style');
    $entity_type_id = $this->getFieldSettings()['target_type'];
    $tabs = [];
    foreach ($items as $delta => $item) {
      $id = $item->getValue()['target_id'];
      if (empty($id)) {
        continue;
      }
      $content = \Drupal::entityTypeManager()
        ->getStorage($entity_type_id)
        ->load($id);
      $title = $content->get($title_field)->getValue()[0]['value'];

      $builder = \Drupal::entityTypeManager()->getViewBuilder('paragraph');
      $body = [];
      if (!$content->get($body_field)->isEmpty()) {
        $body_field_definition = $content->getFieldDefinitions()[$body_field];
        $body_field_values = $content->get($body_field)->getValue();
        if ($body_field_definition->getType() == 'entity_reference_revisions') {
          foreach ($body_field_values as $element) {
            $paragraph = Paragraph::load($element['target_id']);
            $paragraph_view = $builder->view($paragraph, 'default');
            $body[] = $paragraph_view;
          }
        } elseif ($body_field_definition->getType() == 'text_long') {
          $body[] = [
            '#type' => 'processed_text',
            '#text' => $content->get($body_field)->getValue()[0]['value'],
            '#format' => 'full_html',
          ];
        }
      }

      $tabs[$id] = [
        'title' => $title,
        'body' => $body,
      ];
    }
    switch ($style) {
      case 'tab':
        $theme = 'entity_ref_tab_formatter';
        $library = 'entity_ref_tab_formatter/tab_formatter';
        break;

      case 'accordion':
        $theme = 'entity_ref_accordion_formatter';
        $library = 'entity_ref_tab_formatter/accordion_formatter';
        break;
    }
    $elements[$delta] = [
      '#theme' => $theme,
      '#tabs' => $tabs,
      '#attached' => [
        'library' => [
          $library,
        ],
      ],
    ];

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
