<?php

namespace Drupal\unigen\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class UniqueSequenceForm.
 */
class UniqueSequenceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $unique_sequence = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sequence Name'),
      '#maxlength' => 255,
      '#default_value' => $unique_sequence->label(),
      '#description' => $this->t("Name for the Unique sequence."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $unique_sequence->id(),
      '#machine_name' => [
        'exists' => '\Drupal\unigen\Entity\UniqueSequence::load',
      ],
      '#disabled' => !$unique_sequence->isNew(),
    ];

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#maxlength' => 100,
      '#default_value' => $unique_sequence->getPrefix(),
      '#description' => $this->t("Prefix for the Unique sequence."),
      '#required' => FALSE,
    ];

    $form['sequence_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sequence Number'),
      '#maxlength' => 15,
      '#default_value' => $unique_sequence->getSequenceNumber(),
      '#description' => $this->t("Starting number for the Unique sequence."),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $unique_sequence = $this->entity;
    $status = $unique_sequence->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Unique sequence.', [
          '%label' => $unique_sequence->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Unique sequence.', [
          '%label' => $unique_sequence->label(),
        ]));
    }
    $form_state->setRedirectUrl($unique_sequence->toUrl('collection'));
  }

}
