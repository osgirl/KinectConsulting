<?php

namespace Drupal\wfs_gdpr\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WfsGdprPolicyForm.
 */
class WfsGdprPolicyForm extends FormBase {

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The database connection used to check the IP against.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  public function __construct(
      ConfigFactoryInterface $config_factory,
      DomainNegotiatorInterface $negotiator = null,
      QueryFactory $entityQuery,
      Connection $connection,
      EntityTypeManagerInterface $entity_type_manager) {

    $this->config = $config_factory->get('wfs_gdpr.general');
    $this->negotiator = $negotiator;
    $this->entityQuery = $entityQuery;
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
        ($container->has('domain.negotiator') ? $container->get('domain.negotiator') : null),
      $container->get('entity.query'),
      $container->get('database'),
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wfs_gdpr_policy_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Getting Settings from WFS GDPR (general) form.
    $general_settings = $this->config;
    // Domain Id
    $domain_id = isset($this->negotiator) ? $this->negotiator->getActiveId() : 'default';

    $logo = [
      '#theme' => 'image',
      '#uri' => file_url_transform_relative(file_create_url(theme_get_setting('logo.url'))),
      '#alt' => t('Site Logo'),
      '#title' => t('Site Logo'),
      '#width' => '150',
      '#attributes' => array('class' => 'cookie-logo-img'),
    ];

    $form['head'] = [
      '#type' => 'item',
      '#prefix' => '<div id="cookie-head">',
      '#suffix' => '</div>',
      '#attributes' => [
        'id' => 'cookie-head',
      ],
      'close_btn' => [
        '#prefix' => '<div class="cookie-button close">',
        '#suffix' => '</div>',
        '#markup' => t('Close'),
      ],
      'logo' => [
        '#prefix' => '<div class="cookie-logo">',
        '#suffix' => '</div>',
        'logo_markup' =>  $logo,
      ],
      'title' => [
        '#prefix' => '<div class="cookie-title">',
        '#suffix' => '</div>',
        '#markup' => t($general_settings->get($domain_id . '.title')),
      ],
    ];

    $groupTabs = 'wfs_cookies';
    $form[$groupTabs] = [
      '#type' => 'vertical_tabs',
      '#title' => '',
    ];

    // Get GDPRs enabled.
    $entity_type = 'wfs_gdpr';
    $query = $this->entityQuery->get($entity_type)
      ->condition('status', 1)
      ->condition('domain', $domain_id)
      ->sort('weight');
    $entity_ids = $query->execute();

    $gdpr_entities = $this->entityTypeManager
      ->getStorage($entity_type)
      ->loadMultiple($entity_ids);

    $form['general_text'] = [
      '#type' => 'details',
      '#title' => t($general_settings->get($domain_id . '.privacy_title')),
      '#group' => $groupTabs,
      '#attributes' => [
        'id' => 'general-text'
      ]
    ];

    $general_text = $general_settings->get($domain_id . '.privacy_msg');
    $form['general_text']['description'] = [
      '#type' => 'item',
      '#title' => t($general_settings->get($domain_id . '.privacy_title')),
      '#markup' => '<div class="description">' . $general_text['value'] . '</div>',
    ];

    // Build tabs.
    $visible_options = [];
    $chxBosTitle = '<span class="cookie-opt active">' . t('Active') . '</span>';
    $chxBosTitle .= '<span class="cookie-opt">' . t('Inactive') . '</span>';
    foreach ($gdpr_entities as $gdpr_id => $gdpr_entity) {
      $form[$gdpr_id] = [
        '#type' => 'details',
        '#title' => t($gdpr_entity->label()),
        '#group' => $groupTabs,
      ];

      $typeAccept = "{$gdpr_id}_accept_opt";
      if ($gdpr_entity->hasScript()) {
        $form[$gdpr_id][$typeAccept] = [
          '#type' => 'checkbox',
          '#default_value' => TRUE,
          '#title' => $chxBosTitle,
          // This code worked for kinect energy site.
          //'#title' => [
          //  '#markup' => $chxBosTitle,
          //]
        ];
        $visible_options[":input[name='{$typeAccept}']"] = ['checked' => TRUE];
      }

      $form[$gdpr_id]['description'] = [
        '#type' => 'item',
        '#title' => t($gdpr_entity->label()),
        '#markup' => '<div class="description">' . $gdpr_entity->getDescription() . '</div>',
      ];
    }

    $form['cookie_link'] = [
      '#type' => 'details',
      '#title' => t('More Information'),
      '#collapsible' => FALSE,
      '#group' => $groupTabs,
      '#attributes' => [
        'id' => 'wfs-cookie-link'
      ]
    ];

    $link_options= [
      'attributes' => [
        'id' => [
          'wfs-more-information-link'
        ],
      ],
    ];
    $url = Url::fromUri($general_settings->get($domain_id . '.link'), $link_options);
    $itemMarkup = Link::fromTextAndUrl(t('More Information'), $url)->toString();
    $form['cookie_link']['text'] = [
      '#markup' => $itemMarkup,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['accept_all'] = [
      '#type' => 'submit',
      '#value' => t('Accept All'),
      '#states' => [
        'invisible' => [
          $visible_options,
        ],
      ],
      '#name' => 'accept-all',
      '#id' => 'accept-all',
    ];
    $form['actions']['accept'] = [
      '#type' => 'submit',
      '#value' => t('Save Settings'),
      '#name' => 'accept',
      '#id' => 'accept',
    ];
    return $form;
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
    // Get Clicked Button ID.
    $triggerdElement = $form_state->getTriggeringElement();
    $clickedButtonId = $triggerdElement['#id'];
    // Initializing values.


    $ip_address = $this->getRequest()->getClientIP();
    $timestamp = time();
    $accept_opt = [];
    $domain_id = isset($this->negotiator)?$this->negotiator->getActiveId() :  'default';

    // Get values from Form.
    foreach ($form_state->getValues() as $key => $value) {
      $pos = strpos($key, '_accept_opt');
      if ($pos !== false) {
        if ($value || $clickedButtonId == 'accept-all') {
          $accept_opt[] = substr($key, 0, $pos);
        }
      }
    }
      // Saving to database.
      $keys = [
      'ip_address' => $ip_address,
      'domain' => $domain_id,
    ];
      $field  = [
      'domain' => $domain_id,
      'cookies_accepted' => serialize($accept_opt),
      'cookie_disable_banner' => 0,
      'timestamp' => $timestamp,
    ];
    $query = $this->connection;
    $query ->merge('wfs_gdpr')
      ->key($keys)
      ->fields($field)
      ->execute();
  }
}
