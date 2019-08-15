<?php

namespace Drupal\unigen\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Plugin implementation of the 'unique_sequence' widget.
 *
 * @FieldWidget(
 *   id = "unique_sequence_widget",
 *   module = "unigen",
 *   label = @Translation("Unique sequence widget"),
 *   field_types = {
 *     "unique_sequence"
 *   }
 * )
 */
class UnigenWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Set the default settings
      'size' => 60,
      'title' => 'Sequence',
      'show_button' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 200,
    ];

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of textfield'),
      '#default_value' => $this->getSetting('title'),
      '#required' => FALSE,
    ];

    $element['show_button'] = [
      '#type' => 'checkbox',
      '#title' => t('Show generate button'),
      '#default_value' => $this->getSetting('show_button'),
      '#required' => FALSE,
      '#return_value' => 1,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', array('@size' => $this->getSetting('size')));
    $summary[] = t('Textbox title: @title', array('@title' => $this->getSetting('title')));
    $summary[] = t('Show generate button: @value', array('@value' => ($this->getSetting('show_button') ? t('Yes') : t('No')) ));

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
	$field_name = '';
	$fieldDefinition = $items[$delta]->getFieldDefinition();

	$generate_type = 0;
	if ($form_state->getFormObject() instanceof \Drupal\Core\Entity\EntityForm) {
      $entity = $form_state->getFormObject()->getEntity();
	  $entity_type = $entity->getEntityTypeId();
	  $bundle = $entity->bundle();
	  $field_name = $fieldDefinition->getName();

	  $generate_type = $fieldDefinition->getSetting('generate_type');
	}

	$field_name_class = str_ireplace('_','-',$field_name);

    $title = (!empty($this->getSetting('title')) ? t($this->getSetting('title')) : t('Sequence') );
    $description = '';
	if($generate_type == 1) {
      $description .= '<div class="description"><em>'.$title.' '.t('will be generated, if the textbox left empty.').'</em></div> ';
    }
	else if($generate_type == 2) {
      $description .= '<div class="description"><em>'.$title.' '.t('will be generated always on submit.').'</em></div> ';
    }

    $element['unitext'] = array(
      '#type' => 'textfield',
      '#title' => (!empty($this->getSetting('title')) ? t($this->getSetting('title')) : t('Sequence') ),
      '#default_value' => isset($items[$delta]->unitext) ? $items[$delta]->unitext : NULL,
      '#size' => 20,
      '#maxlength' => $this->getSetting('size'),
      '#prefix' => '<div class="unique-sequence-field-widget unique-sequence-field-widget-'.$field_name_class.'">',
      '#attributes' => ['field-name' => $field_name],
    );

    if ($form_state->getFormObject()->getOperation() == 'edit' && $this->getSetting('show_button')) {
	  $element['generate'] = array(
	    '#type' => 'submit',
	    '#value' => t('Generate'),
	    '#attributes' => [
	  	  'class' => [
	  	    'use-ajax',
	  	  ],
       	  'field-name' => $field_name,
	    ],
        '#id' => 'id-'.$field_name,
        '#name' => 'name-'.$field_name,
        '#ajax' => array(
	       'callback' => [$this, 'generateSequence'],
           'event' => 'click',
           'progress' => array(
             'type' => 'throbber',
             'message' => NULL,
           ),
        ),
        '#suffix' => $description.'</div>',
	  );
	  $element['sequence_generate_message'] = array(
	    '#markup' => '',
        '#prefix' => '<div class="unique-sequence-field-widget-message '.$field_name_class.'">',
        '#suffix' => '</div>',
	  );
    }
    else {
      $element['sequence_text']['#suffix'] = $description.'</div>';
    }

    $element['#attached']['library'][] = 'unigen/unigen_style';

    return $element;
  }


  /**
   * {@inheritdoc}
   */
  public function generateSequence(array &$form, FormStateInterface $form_state) {
    $ajax_response = new AjaxResponse();

	if ($form_state->getFormObject() instanceof \Drupal\Core\Entity\EntityForm) {
	  //get the entity
	  $entity = $form_state->getFormObject()->getEntity();
	  $entity_type = $entity->getEntityTypeId();
	  $bundle = $entity->bundle();

	  \Drupal::logger('unigen')->info('I m here-1');

	  //get field name
	  $element = $form_state->getTriggeringElement();
	  $field_name = $element['#attributes']['field-name'];
	  $field_name_class = str_ireplace('_','-',$field_name);

      $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
	  $fieldDefinition = $definitions[$field_name];
	  $sequence_id = $fieldDefinition->getSetting('sequence_id');
	  $generate_type = $fieldDefinition->getSetting('generate_type');

	  if(empty($entity_type) || empty($bundle) || empty($field_name) || empty($sequence_id)) {
	    \Drupal::logger('unigen')->error('Sequence generate failed: '.print_r(['entity_type' => $entity_type, 'bundle' => $bundle, 'field_name' => $field_name, 'sequence_id' => $sequence_id],true));

	    $ajax_response->addCommand(new InvokeCommand(".field--name-".$field_name_class." .unique-sequence-field-widget-message", 'html' , array(t('Failed to generate sequence.'))));
    	return $ajax_response;
	  }

	  //get the service
	  $sequenceService = \Drupal::service('unigen.helper');
	  $data = $sequenceService->entityNextSequence($entity_type, $bundle, $field_name, $sequence_id);
	  if($data['status'] == 1 && !empty($data['text']) && $entity->hasField($field_name)) {
	    $entity->set($field_name, ['unitext' => $data['text'], 'uninumber' => $data['number'], 'uniprefix' => $data['prefix'], 'ldate' > REQUEST_TIME]);
	    $entity->disable_presave = 1;
	    $entity->save();

	    $ajax_response->addCommand(new InvokeCommand("input[name='".$field_name."[0][unitext]'", 'val' , array($data['text'])));
	    $ajax_response->addCommand(new InvokeCommand(".field--name-".$field_name_class." .unique-sequence-field-widget-message", 'html' , array(t('Sequence generated.'))));
	  }
	  else {
	    \Drupal::logger('unigen')->error('Sequence generate failed: '.print_r($data,true));
	    $ajax_response->addCommand(new InvokeCommand(".field--name-".$field_name_class." .unique-sequence-field-widget-message", 'html' , array(t('Failed to generate sequence.'))));
	  }
	}

    return $ajax_response;
  }

}

