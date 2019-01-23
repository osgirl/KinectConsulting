<?php

namespace Drupal\wfs_gdpr;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\domain\DomainNegotiatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a listing of WFS GDPR entities.
 */
class WfsGdprListBuilder extends ConfigEntityListBuilder {
  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The Form Builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The Domain negotiator.
   *
   * @var \Drupal\domain\DomainNegotiatorInterface
   */
  protected $negotiator;

  public function __construct(
        EntityTypeInterface $entity_type,
        EntityStorageInterface $storage,
        ActionManager $action_manager,
        FormBuilderInterface $form_builder,
        RequestStack $request_stack,
        DomainNegotiatorInterface $negotiator = null) {

    parent::__construct($entity_type, $storage);
    $this->actionManager = $action_manager;
    $this->formBuilder = $form_builder;
    $this->requestStack = $request_stack;
    $this->negotiator = $negotiator;

    $this->domain_id = isset($this->negotiator) ? $this->negotiator->getActiveId() : 'default';

    $request = $this->requestStack->getCurrentRequest();
    if (!empty($request->query->get('domain_config_ui_domain'))) {
      $this->domain_id = $request->query->get('domain_config_ui_domain');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('plugin.manager.action'),
      $container->get('form_builder'),
      $container->get('request_stack'),
        ($container->has('domain.negotiator') ? $container->get('domain.negotiator') : null)
    );
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $form = $this->formBuilder->getForm('\Drupal\wfs_gdpr\Form\GeneralForm');
    $build['settings'] = $form;
    $build['table'] = parent::render();
    return $build;
  }
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('WFS GDPR');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    if ($this->domain_id == $entity->getDomain()) {
      $row['label'] = $entity->label();
      $row['id'] = $entity->id();
      $row['status'] = ($entity->status() == 1) ? t('Published'): t('Not Published');
      return $row + parent::buildRow($entity);
    }
  }

}
