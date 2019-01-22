<?php

namespace Drupal\wfs_gdpr\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class WfsGdprForm.
 */
class WfsGdprForm extends EntityForm {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  public function __construct(ConfigFactoryInterface $config_factory, PrivateTempStoreFactory $temp_store_factory) {

    $this->config_factory = $config_factory;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('user.private_tempstore')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $wfs_gdpr = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $wfs_gdpr->label(),
      '#description' => $this->t("Label for the WFS GDPR."),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $wfs_gdpr->id(),
      '#machine_name' => [
        'exists' => '\Drupal\wfs_gdpr\Entity\WfsGdpr::load',
      ],
      '#disabled' => !$wfs_gdpr->isNew(),
    ];
    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'text_format',
      '#default_value' => $wfs_gdpr->getDescription(),
      '#format' => $wfs_gdpr->getDescriptionFormat(),
      '#description' => t('This text will be displayed on the <em>Popup</em> of the GDPR acceptence.'),
    ];
    $form['header_script'] = [
      '#title' => t('Header Script'),
      '#type' => 'textarea',
      '#default_value' => $wfs_gdpr->getHeaderScript(),
      '#description' => t('This script will be added on the <strong><code>&lt;header&gt;&lt;/header&gt;</code></strong> for all pages.'),
    ];
    $form['body_script'] = [
      '#title' => t('Body Script'),
      '#type' => 'textarea',
      '#default_value' => $wfs_gdpr->getBodyScript(),
      '#description' => t('This text will be included on the <strong><code>&lt;body&gt;&lt;/body&gt;</code></strong> for all pages.'),
    ];
    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#delta' => 10,
      '#default_value' => $wfs_gdpr->getWeight(),
      '#description' => $this->t('Use to give and order to show the tabs.'),
    ];
    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('When content reaches this state it should be published.'),
      '#default_value' => $wfs_gdpr->status(),
    ];

    $session = $this->tempStoreFactory->get('wfs_gdpr');
    $domain = (!empty($wfs_gdpr->getDomain()))? $wfs_gdpr->getDomain() : $session->get('wfs_gdpr_config_domain_id');
    $form['domain'] = array(
      '#type' => 'hidden',
      '#value' => $domain,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $wfs_gdpr = $this->entity;
    $status = $wfs_gdpr->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label WFS GDPR.', [
          '%label' => $wfs_gdpr->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label WFS GDPR.', [
          '%label' => $wfs_gdpr->label(),
        ]));
    }
    $form_state->setRedirectUrl($wfs_gdpr->toUrl('collection'));
  }

}
