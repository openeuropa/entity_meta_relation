<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\emr\Entity\EntityMetaRelationInterface;

/**
 * Storage handler for the entity meta relation entities.
 */
class EntityMetaRelationStorage extends SqlContentEntityStorage implements EntityMetaRelationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EntityMetaRelationInterface $entity_meta_relation): array {
    return $this->database->query(
      'SELECT revision_id FROM {' . $this->getRevisionTable() . '} WHERE id=:id ORDER BY revision_id',
      [':id' => $entity_meta_relation->id()]
    )->fetchCol();
  }

}
