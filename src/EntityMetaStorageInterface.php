<?php

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Interface for the storage handler of EntityMeta entities.
 */
interface EntityMetaStorageInterface {

  /**
   * Updates the related EntityMeta entities of a given entity.
   *
   * This method is called when a content entity is saved and it's responsible
   * for re-saving all the associated meta entities. That in turn will update
   * the relations to point to the new content entity revision if one was
   * created.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public function updateEntityMetaRelated(ContentEntityInterface $entity): void;

  /**
   * Returns the related meta entities of a content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface[]
   *   The EntityMeta entities
   */
  public function getRelatedMetaEntities(ContentEntityInterface $entity): array;

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
   * Returns the related content entities of an EntityMeta entity.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta.
   * @param string $entity_type
   *   The content entity type to look for.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The related entities.
   */
  public function getRelatedContentEntities(EntityMetaInterface $entity, string $entity_type): array;

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

}
