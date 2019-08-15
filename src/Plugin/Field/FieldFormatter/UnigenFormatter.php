<?php

namespace Drupal\unigen\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;

/**
 * Plugin implementation of the 'unique_sequence' formatter.
 *
 * @FieldFormatter(
 *   id = "unique_sequence_formatter",
 *   module = "unigen",
 *   label = @Translation("Unique sequence formatter"),
 *   field_types = {
 *     "unique_sequence"
 *   }
 * )
 */
class UnigenFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $display_type = $this->getSetting('display_type');
	if($display_type == 1) {
      $summary[] = $this->t('Display type: Display sequence with prefix');
    }
    else {
      $summary[] = $this->t('Display type: Display sequence without prefix');
    }

    $summary[] = $this->t('Display update time: %status', ['%status' => ($this->getSetting('display_update_time') ? $this->t('Yes') : $this->t('No')) ]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Declare the settings
      'display_type' => 1,
      'display_update_time' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['display_type'] = [
      '#title' => $this->t('Display type'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('display_type'),
      '#options' => [
        '1' => $this->t('Display sequence with prefix'),
        '2' => $this->t('Display sequence without prefix')
      ],
      '#description' => $this->t('Options for how the unique sequence will be displayed.'),
    ];

    $element['display_update_time'] = [
      '#title' => $this->t('Display last sequence update time'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('display_update_time'),
      '#return_value' => 1,
      '#description' => $this->t('Select if you want to display sequence last update time.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

	//get configuration
    $display_type = !empty($this->getSetting('display_type')) ? intval($this->getSetting('display_type')) : 1;
    $display_update_time = !empty($this->getSetting('display_update_time')) ? $this->getSetting('display_update_time') : 0;

    foreach ($items as $delta => $item) {
	  $sequence_text = $item->unitext;
	  $sequence_number = $item->uninumber;
	  $sequence_prefix = $item->uniprefix;
	  $sequence_time = date('F j, Y h:i:s A', $item->ldate);

	  $html  = "<div class='unique-sequence-item'>";

	  //with or without prefix
	  if($display_type == 1) {
	    $html .= "	<div class='unique-sequence-text'>".$sequence_text."</div>";
	  }
	  else {
	    $html .= "	<div class='unique-sequence-text'>".$sequence_number."</div>";
	  }

	  //time
	  if($display_update_time) {
	    $html .= "	<div class='unique-sequence-time'>".$sequence_time."</div>";
	  }

	  $html .= "</div>";

      $elements[$delta] = [
        '#markup' => Markup::create($html),
      ];
    }

    return $elements;
  }

}
