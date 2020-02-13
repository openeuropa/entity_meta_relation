<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\emr\Entity\EntityMetaRelationInterface;

/**
 * Interface for the storage handler of EntityMetaRelation entities.
 */
interface EntityMetaRelationStorageInterface extends EntityStorageInterface {

  /**
   * Gets a list of revision IDs for a specific entity meta relation.
   *
   * @param \Drupal\emr\Entity\EntityMetaRelationInterface $entity_meta_relation
   *   The entity meta relation.
   *
   * @return int[]
   *   The revision IDs (in ascending order).
   */
  public function revisionIds(EntityMetaRelationInterface $entity_meta_relation): array;

  /**
   * Return content field id used for this relation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return string|null
   *   The field name.
   */
  public function getContentFieldName(ContentEntityInterface $entity): ?string;

}
