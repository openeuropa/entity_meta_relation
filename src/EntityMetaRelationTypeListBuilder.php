<?php

declare(strict_types = 1);

namespace Drupal\entity_meta_relation;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of entity meta relation type entities.
 *
 * @see \Drupal\entity_meta_relation\Entity\EntityMetaRelationType
 */
class EntityMetaRelationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No entity meta relation types available. <a href=":link">Add entity meta relation type</a>.',
      [':link' => Url::fromRoute('entity.entity_meta_relation_type.add_form')->toString()]
    );

    return $build;
  }

}
