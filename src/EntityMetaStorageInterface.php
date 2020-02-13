<?php

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Interface for the storage handler of EntityMeta entities.
 */
interface EntityMetaStorageInterface extends EntityStorageInterface {

  /**
   * Returns the related meta entities of a content entity, grouped by bundle.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface[]
   *   The EntityMeta entities
   */
  public function getBundledRelatedMetaEntities(ContentEntityInterface $entity): array;

  /**
   * Queries and returns for related entities.
   *
   * This can either be from the direction of an EntityMeta (returning related
   * content entities) or from that of a content entity (returning EntityMeta
   * entities).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to look for related entities.
   * @param int|null $revision_id
   *   Specific revision_id. -1 can be used to get relations to all revisions
   *   of the entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The related entities.
   */
  public function getRelatedEntities(ContentEntityInterface $entity, int $revision_id = NULL): array;

  /**
   * Deletes all the related meta entities.
   *
   * We don't need to delete the relation entities because those are deleted
   * in turn when an entity meta is deleted.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The content entity.
   *
   * @see emr_entity_delete()
   */
  public function deleteAllRelatedMetaEntities(ContentEntityInterface $content_entity): void;

  /**
   * Returns the fields that should indicate if the entity has changed.
   *
   * These are only the FieldConfigInterface fields because these are the ones
   * where we store data (since we are using bundles).
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity.
   *
   * @return array
   *   The field names.
   */
  public function getChangeFields(EntityMetaInterface $entity): array;

  /**
   * Checks whether it should make a new revision upon saving the EntityMeta.
   *
   * We make a new revision if there is a change in one of the relevant fields.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta entity.
   *
   * @return bool
   *   Whether it should make a new revision.
   */
  public function shouldMakeRevision(EntityMetaInterface $entity): bool;

}
