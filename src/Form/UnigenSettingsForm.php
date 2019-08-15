<?php

namespace Drupal\unigen\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;


class UnigenSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['unigen.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'unigen_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('unigen.settings');

    $form['credentials'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Credentials'),
      '#description' => $this->t('Manage RestAPI credentials.'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['credentials']['restapi_credentials'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('RestAPI Credentials'),
      '#description' => $this->t('Please enter the credentials in the following format: {remote ip address} | {Api Key}. Each row in new line.'),
      '#default_value' => $config->get('restapi_credentials'),
	  '#rows' => 10,
	  '#placeholder' => ' ',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('unigen.settings');
    $config->set('restapi_credentials', $form_state->getValue('restapi_credentials'));
    $config->save();

	drupal_set_message($this->t('Configurations have been updated successfully!'));

    parent::submitForm($form, $form_state);
  }

}
