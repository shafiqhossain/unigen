<?php

namespace Drupal\unigen\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'unique_sequence' field type.
 *
 * @FieldType(
 *   id = "unique_sequence",
 *   label = @Translation("Unique Sequence Generator"),
 *   module = "unigen",
 *   description = @Translation("Create a field which will generate a unique sequence based on unique sequence entity."),
 *   default_widget = "unique_sequence_widget",
 *   default_formatter = "unique_sequence_formatter",
 *   cardinality = 1
 * )
 */
class UnigenItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'unitext' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'uninumber' => array(
          'type' => 'varchar',
          'length' => 200,
          'not null' => FALSE,
        ),
        'uniprefix' => array(
          'type' => 'varchar',
          'length' => 40,
          'not null' => FALSE,
        ),
        'ldate' => array(
          'type' => 'int',
          'not null' => FALSE,
          'default' => 0,
        ),
      ],
    ];
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      // Declare the setting of sequence_id, with a default
      // value of 'general'
      'sequence_id' => 'general',
      'generate_type' => 2,
      'generate_event_type' => 1,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    $options = [];
    $ids = \Drupal::entityQuery('unique_sequence')
       ->sort('id', 'asc')
       ->execute();

    if(is_array($ids) && count($ids)) {
      foreach($ids as $id) {
        $sequence = \Drupal::entityTypeManager()->getStorage('unique_sequence')->load($id);
        $options[$id] = $sequence->label();
      }
    }

    $form['seq'] = [
      '#type' => 'details',
      '#title' => $this->t('Sequence info'),
      '#open' => TRUE,
    ];
    $element['sequence_id'] = [
      '#title' => $this->t('Sequene Type'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('sequence_id'),
      '#required' => TRUE,
      '#description' => t('Please select a squence, which will be attached with this field.'),
    ];

    $element['generate_type'] = [
      '#type' => 'select',
      '#title' => t('When to generate sequence'),
      '#default_value' => $this->getSetting('generate_type'),
      '#required' => TRUE,
      '#options' => [
        1 => t('Generate sequence, only when empty'),
        2 => t('Generate sequence, always'),
      ],
      '#description' => t('Please select option, when the sequence will be generated.'),
    ];

    $element['generate_event_type'] = [
      '#type' => 'select',
      '#title' => t('Generate event'),
      '#default_value' => $this->getSetting('generate_event_type'),
      '#required' => TRUE,
      '#options' => [
        1 => t('New entity'),
        2 => t('Old entity'),
        3 => t('New and Old entity'),
      ],
      '#description' => t('Please select the even, when the sequence generation will be trigger.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['unitext'] = DataDefinition::create('string')
      ->setLabel(t('Sequence text'));

    $properties['uninumber'] = DataDefinition::create('string')
      ->setLabel(t('Sequence number'));

    $properties['uniprefix'] = DataDefinition::create('string')
      ->setLabel(t('Sequence prefix'));

    $properties['ldate'] = DataDefinition::create('integer')
      ->setLabel(t('Last update time'));

    return $properties;
  }


  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

	//get the entity
    $entity = $this->getEntity();

	//when update from generate button or manually update the entity, restrict with this flag "disable_presave"
	$disable_presave = isset($entity->disable_presave) ? $entity->disable_presave : 0;
	if($disable_presave) return;

	//get the service
	$sequenceService = \Drupal::service('unigen.helper');

	//get the definition
	$fieldDefinition = $this->getFieldDefinition();

	$entity_type = $fieldDefinition->getTargetEntityTypeId();
	$bundle = $fieldDefinition->getTargetBundle();
	$field_name = $fieldDefinition->getName();
	$field_type = $fieldDefinition->getType();
	$sequence_id = $fieldDefinition->getSetting('sequence_id');
	$generate_type = $fieldDefinition->getSetting('generate_type');
	$generate_event_type = $fieldDefinition->getSetting('generate_event_type');

	//get the value
	$unitext = (isset($this->properties['unitext']) ? trim($this->properties['unitext']->getValue()) : '');
	$uninumber = '';
	$uniprefix = '';

    if (($generate_event_type == 1 || $generate_event_type == 3) && $entity->isNew()) {  //generate only for new entity
	  if($generate_type == 1 && empty($unitext)) {  //only if empty
  	    $data = $sequenceService->entityNextSequence($entity_type, $bundle, $field_name, $sequence_id);
	    if($data['status']==1) {
	      $unitext = $data['text'];
	      $uninumber = $data['number'];
	      $uniprefix = $data['prefix'];
	    }
	  }
	  else {  //always
  	    $data = $sequenceService->entityNextSequence($entity_type, $bundle, $field_name, $sequence_id);
	    if($data['status']==1) {
	      $unitext = $data['text'];
	      $uninumber = $data['number'];
	      $uniprefix = $data['prefix'];
	    }
	  }

      $this->get('unitext')->setValue($unitext);
      $this->get('uninumber')->setValue($uninumber);
      $this->get('uniprefix')->setValue($uniprefix);
      $this->get('ldate')->setValue(REQUEST_TIME);
    }
    else if (($generate_event_type == 2 || $generate_event_type == 3) && !$entity->isNew()) {  //generate only for old entity
	  if($generate_type == 1 && empty($unitext)) {  //only if empty
  	    $data = $sequenceService->entityNextSequence($entity_type, $bundle, $field_name, $sequence_id);
	    if($data['status']==1) {
	      $unitext = $data['text'];
	      $uninumber = $data['number'];
	      $uniprefix = $data['prefix'];
	    }
	  }
	  else {  //always
  	    $data = $sequenceService->entityNextSequence($entity_type, $bundle, $field_name, $sequence_id);
	    if($data['status']==1) {
	      $unitext = $data['text'];
	      $uninumber = $data['number'];
	      $uniprefix = $data['prefix'];
	    }
	  }

      $this->get('unitext')->setValue($unitext);
      $this->get('uninumber')->setValue($uninumber);
      $this->get('uniprefix')->setValue($uniprefix);
      $this->get('ldate')->setValue(REQUEST_TIME);
    }
  }

}
