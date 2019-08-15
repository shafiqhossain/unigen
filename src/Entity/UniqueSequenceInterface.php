<?php

namespace Drupal\unigen\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Unique sequence entities.
 */
interface UniqueSequenceInterface extends ConfigEntityInterface {

  /**
   * Return the prefix which will be added before the sequence
   */
  public function getPrefix();

  /**
   * Set the prefix which will be added before the sequence
   */
  public function setPrefix($text);

  /**
   * Return the last sequence number generated
   */
  public function getSequenceNumber();

  /**
   * Set the new sequence number
   */
  public function setSequenceNumber($val);

}
