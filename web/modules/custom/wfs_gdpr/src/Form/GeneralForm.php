<?php

namespace Drupal\wfs_gdpr\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class GeneralForm.
 */
class GeneralForm extends ConfigFormBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  public function __construct(
      ConfigFactoryInterface $config_factory,
      RequestStack $request_stack,
      PrivateTempStoreFactory $temp_store_factory,
      DomainNegotiatorInterface $negotiator = null) {

    parent::__construct($config_factory);
    $this->requestStack = $request_stack;
    $this->negotiator = $negotiator;
    $this->tempStoreFactory = $temp_store_factory;

    $this->domain_id = isset($this->negotiator) ? $this->negotiator->getActiveId() : 'default';

    $request = $this->requestStack->getCurrentRequest();
    if (!empty($request->query->get('domain_config_ui_domain'))) {
      $this->domain_id = $request->query->get('domain_config_ui_domain');
    }

    $session = $this->tempStoreFactory->get('wfs_gdpr');
    $session->set('wfs_gdpr_config_domain_id', $this->domain_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('user.private_tempstore'),
        ($container->has('domain.negotiator') ? $container->get('domain.negotiator') : null)
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'wfs_gdpr.general',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'general_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('wfs_gdpr.general');

    $form['gdpr'] = [
      '#type' => 'details',
      '#title' => t('General Settings'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => FALSE,
    ];
    $title = !empty($config->get($this->domain_id . '.title')) ? $config->get($this->domain_id . '.title') : 'Cookies Policy Settings';
    $form['gdpr']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cookie Policy title.'),
      '#default_value' => $title,
      '#description' => t("Add the Cookie Policy title. By default %title.", ['%title' => '"Cookies Policy Settings"']),
    ];
    $form['gdpr']['popup_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Cookies for the current domain.'),
      '#default_value' => $config->get($this->domain_id . '.popup_enabled'),
    ];
    $form['gdpr']['show_once'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Check this if want to show the banner only on the first page visited by the user .'),
      '#default_value' => $config->get($this->domain_id . '.show_once'),
      '#description' => t('By default (no checked), the banner will be shown on all pages until user accepted cookies.'),
    ];
    $form['gdpr']['popup'] = [
      '#type' => 'details',
      '#title' => t('Popup Message'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $form['gdpr']['popup']['confirmation'] = [
      '#type' => 'checkbox',
      '#title' => t('Consent by clicking'),
      '#description' => t('By default by clicking any link or button on
      the website the visitor accepts the cookie policy. Uncheck this box if
      you do not require this functionality. You may want
      to edit the pop-up message below accordingly.'),
      '#default_value' => $config->get($this->domain_id . '.confirmation'),
    ];
    $message = $config->get($this->domain_id . '.message');
    $form['gdpr']['popup']['message'] = [
      '#type' => 'text_format',
      '#title' => t('Popup message - requests consent'),
      '#default_value' => $message['value'],
      '#required' => TRUE,
      '#format' => $message['format'],
    ];
    $button_label = $config->get($this->domain_id . '.button_label');
    $form['gdpr']['popup']['button_label'] = [
      '#type' => 'textfield',
      '#title' => t('Agree button label'),
      '#default_value' => !empty($button_label) ? $button_label : t('OK, I agree'),
      '#required' => TRUE,
    ];
    $form['gdpr']['your_privacy'] = [
      '#type' => 'details',
      '#title' => t('General Text'),
      '#collapsible' => TRUE,
      '#tree' => TRUE,
      '#open' => TRUE,
    ];
    $privacy_title = $config->get($this->domain_id . '.privacy_title');
    $form['gdpr']['your_privacy']['privacy_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('General title.'),
      '#default_value' => !empty($privacy_title) ? $privacy_title : t('Your Privacy'),
      '#description' => t("Add a general title. By default %title.", ['%title' => '"Your Privacy"']),
    ];
    $privacy_msg = $config->get($this->domain_id . '.privacy_msg');
    $form['gdpr']['your_privacy']['privacy_msg'] = [
      '#type' => 'text_format',
      '#title' => t('Your Privacy'),
      '#default_value' => $privacy_msg['value'],
      '#required' => TRUE,
      '#format' => $privacy_msg['format'],
    ];
    $form['gdpr']['link'] = [
      '#type' => 'url',
      '#title' => t('More information link'),
      '#default_value' => $config->get($this->domain_id . '.link'),
      '#description' => t('Provide a Link. By default the title will be %title', ['%title' => 'More Information']),
      '#required' => TRUE,
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

    $this->config('wfs_gdpr.general')
      ->set($this->domain_id . '.title', $form_state->getValue(['gdpr', 'title']))
      ->set($this->domain_id . '.popup_enabled', $form_state->getValue(['gdpr', 'popup_enabled']))
      ->set($this->domain_id . '.show_once', $form_state->getValue(['gdpr', 'show_once']))
      ->set($this->domain_id . '.confirmation', $form_state->getValue(['gdpr', 'popup', 'confirmation']))
      ->set($this->domain_id . '.message', $form_state->getValue(['gdpr', 'popup', 'message']))
      ->set($this->domain_id . '.button_label', $form_state->getValue(['gdpr', 'popup', 'button_label']))
      ->set($this->domain_id . '.privacy_title', $form_state->getValue(['gdpr', 'your_privacy', 'privacy_title']))
      ->set($this->domain_id . '.privacy_msg', $form_state->getValue(['gdpr', 'your_privacy', 'privacy_msg']))
      ->set($this->domain_id . '.link', $form_state->getValue(['gdpr', 'link']))
      ->save();
  }

  /**
   * Validate callback for URL field.
   */
  public function validateURL($element, FormStateInterface $form_state) {
    $url = $element['#value'];
    if (!UrlHelper::isValid($url)) {
      $form_state->setError(
        $element,
        t('The %element-title is not a valid URL. Please enter a internal o external link',
          ['%element-title' => $element['#title']]
        )
      );
    }

  }

}
