<?php

declare(strict_types=1);

namespace Drupal\emr;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of entity meta type entities.
 *
 * @see \Drupal\emr\Entity\EntityMetaType
 */
class EntityMetaTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No entity meta types available. <a href=":link">Add entity meta type</a>.',
      [':link' => Url::fromRoute('entity.entity_meta_type.add_form')->toString()]
    );

    return $build;
  }

}
