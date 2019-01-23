<?php

namespace Drupal\wfs_gdpr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GdprEnabledSettingForm.
 */
class GdprEnabledSettingForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wfs_gdpr.gdprenabledsetting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gdpr_enabled_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('wfs_gdpr.gdprenabledsetting');
    $form['cookie_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Policy title'),
      '#description' => $this->t('Add the Cookie Policy title. By default &quot;Cookies Policy Settings&quot;.'),
      '#maxlength' => 150,
      '#size' => 64,
      '#default_value' => $config->get('cookie_title'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('wfs_gdpr.gdprenabledsetting')
      ->set('cookie_title', $form_state->getValue('cookie_title'))
      ->save();
  }

}
