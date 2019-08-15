<?php

namespace Drupal\unigen;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Unique sequence entities.
 */
class UniqueSequenceListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Sequence ID');
    $header['label'] = $this->t('Sequence Name');
    $header['prefix'] = $this->t('Prefix');
    $header['number'] = $this->t('Last Sequence Number');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['label'] = $entity->label();
    $row['prefix'] = $entity->getPrefix();
    $row['number'] = $entity->getSequenceNumber();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['#empty'] = $this->t('There are sequence info available.');
    return $build;
  }

}
