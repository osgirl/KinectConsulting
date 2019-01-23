<?php

namespace Drupal\wfs_gdpr\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the WFS GDPR entity.
 *
 * @ConfigEntityType(
 *   id = "wfs_gdpr",
 *   label = @Translation("WFS GDPR"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\wfs_gdpr\WfsGdprListBuilder",
 *     "form" = {
 *       "add" = "Drupal\wfs_gdpr\Form\WfsGdprForm",
 *       "edit" = "Drupal\wfs_gdpr\Form\WfsGdprForm",
 *       "delete" = "Drupal\wfs_gdpr\Form\WfsGdprDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\wfs_gdpr\WfsGdprHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "wfs_gdpr",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/wfs-settings/gdpr/wfs_gdpr/{wfs_gdpr}",
 *     "add-form" = "/admin/config/wfs-settings/gdpr/wfs_gdpr/add",
 *     "edit-form" = "/admin/config/wfs-settings/gdpr/wfs_gdpr/{wfs_gdpr}/edit",
 *     "delete-form" = "/admin/config/wfs-settings/gdpr/wfs_gdpr/{wfs_gdpr}/delete",
 *     "collection" = "/admin/config/wfs-settings/gdpr/wfs_gdpr"
 *   }
 * )
 */
class WfsGdpr extends ConfigEntityBase implements WfsGdprInterface {
  /**
   * The WFS GDPR ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The WFS GDPR label.
   *
   * @var string
   */
  protected $label;

  /**
   * The text to show in the banner.
   *
   * @var string
   */
  protected $description;

  /**
   * The script (code) will be used in the header section.
   *
   * @var string
   */
  protected $header_script;

  /**
   * The script (code) will be used in the body.
   *
   * @var string
   */
  protected $body_script;

  /**
   * The weight to show the tab order.
   *
   * @var integer
   */
  protected $weight;

  /**
   * The weight to show the tab order.
   *
   * @var string
   */
  protected $domain;

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    if ($description = $this->get('description')) {
      return $description['value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDescriptionFormat() {
    if ($description = $this->get('description')) {
      return $description['format'];
    }
  }

  /**
   * @return string
   */
  public function getHeaderScript() {
    return $this->header_script;
  }

  /**
   * @param string $header_script
   */
  public function setHeaderScript($header_script) {
    $this->header_script = $header_script;
  }

  /**
   * @return string
   */
  public function getBodyScript() {
    return $this->body_script;
  }

  /**
   * @param string $body_script
   */
  public function setBodyScript($body_script) {
    $this->body_script = $body_script;
  }

  /**
   * @return int
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * @param int $weight
   */
  public function setWeight($weight) {
    $this->weight = $weight;
  }

  /**
   * @return string
   */
  public function getDomain() {
    return $this->domain;
  }

  /**
   * @param string $domain
   */
  public function setDomain($domain) {
    $this->domain = $domain;
  }

  /**
   * @return boolean
   */
  public function hasScript() {
    if (empty($this->getHeaderScript()) && empty($this->getBodyScript())) {
      return FALSE;
    }
    return TRUE;
  }
}
