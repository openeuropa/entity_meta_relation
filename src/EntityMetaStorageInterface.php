<?php

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Interface for the storage handler of EntityMeta entities.
 */
interface EntityMetaStorageInterface extends EntityStorageInterface, RevisionableStorageInterface {

  /**
   * Gets a list of revision IDs for a specific entity meta.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity_meta
   *   The entity meta.
   *
   * @return int[]
   *   The revision IDs (in ascending order).
   */
  public function revisionIds(EntityMetaInterface $entity_meta): array;

  /**
   * Queries and returns for related entities.
   *
   * This can either be from the direction of an EntityMeta (returning related
   * content entities) or from that of a content entity (returning EntityMeta
   * entities).
   *
   * Note that this will return the last revision of the target entities
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to look for related entities.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The related entities.
   */
  public function getRelatedEntities(ContentEntityInterface $entity): array;

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
   * Deletes all the revisions of entity meta relation entities.
   *
   * These are the revisions that reference the current revision of the passed
   * host entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The content entity.
   */
  public function deleteAllRelatedEntityMetaRelationRevisions(ContentEntityInterface $content_entity): void;

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

  /**
   * Get a list of entity metas that should be attached by default.
   *
   * This will create a list of new EntityMeta entities whose plugins indicate
   * that some defaults need to be set on them whenever the host entity gets
   * created.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The host entity for which to determine the default metas.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface[]
   *   The list of default entity metas.
   */
  public function getDefaultEntityMetas(ContentEntityInterface $entity): array;

}
