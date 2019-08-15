<?php

namespace Drupal\unigen\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Unique sequence entity.
 *
 * @ConfigEntityType(
 *   id = "unique_sequence",
 *   label = @Translation("Unique sequence"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\unigen\UniqueSequenceListBuilder",
 *     "form" = {
 *       "add" = "Drupal\unigen\Form\UniqueSequenceForm",
 *       "edit" = "Drupal\unigen\Form\UniqueSequenceForm",
 *       "delete" = "Drupal\unigen\Form\UniqueSequenceDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\unigen\UniqueSequenceHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "unique_sequence",
 *   admin_permission = "administer unigen",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/user-interface/unigen/unique_sequence/{unique_sequence}",
 *     "add-form" = "/admin/config/user-interface/unigen/unique_sequence/add",
 *     "edit-form" = "/admin/config/user-interface/unigen/unique_sequence/{unique_sequence}/edit",
 *     "delete-form" = "/admin/config/user-interface/unigen/unique_sequence/{unique_sequence}/delete",
 *     "collection" = "/admin/config/user-interface/unigen/unique_sequences"
 *   }
 * )
 */
class UniqueSequence extends ConfigEntityBase implements UniqueSequenceInterface {

  /**
   * The Unique sequence ID.
   *
   * @var string
   */
  public $id;

  /**
   * The Unique sequence label.
   *
   * @var string
   */
  public $label;

  /**
   * The Sequence prefix
   *
   * @var string
   */
  public $prefix = '';

  /**
   * The Sequence number.
   *
   * @var int
   */
  public $sequence_no;

  /**
   * {@inheritdoc}
   */
  public function getPrefix() {
    return $this->prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrefix($text) {
    $this->prefix = $text;
  }

  /**
   * {@inheritdoc}
   */
  public function getSequenceNumber() {
    return $this->sequence_no;
  }

  /**
   * {@inheritdoc}
   */
  public function setSequenceNumber($val) {
    $this->sequence_no = $val;
  }

}
