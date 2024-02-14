<?php

declare(strict_types=1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\emr\Entity\EntityMetaRelationInterface;

/**
 * Interface for the storage handler of EntityMetaRelation entities.
 */
interface EntityMetaRelationStorageInterface extends EntityStorageInterface, RevisionableStorageInterface {

  /**
   * The target name for the relation field pointing to the content entity.
   *
   * @see EntityMetaRelationStorageInterface::getRelationFieldName()
   */
  const RELATION_FIELD_TARGET_CONTENT = 'content';

  /**
   * The target name for the relation field pointing to the meta entity.
   *
   * @see EntityMetaRelationStorageInterface::getRelationFieldName()
   */
  const RELATION_FIELD_TARGET_META = 'meta';

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
   * Returns the relation field name.
   *
   * This is the name of the field on a given entity meta relation entity that
   * points either to the entity meta or to the content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $target
   *   The relation direction: 'content' or 'meta'.
   *
   * @return string|null
   *   The field name.
   */
  public function getRelationFieldName(ContentEntityInterface $entity, string $target): ?string;

}
