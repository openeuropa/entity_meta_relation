<?php

declare(strict_types = 1);

namespace Drupal\emr\Field;

use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Represents a field item list for entity meta entities.
 */
interface EntityMetaItemListInterface {

  /**
   * Attach an entity meta.
   *
   * EntityMeta entities are added to the existing list or replaced if they are
   * already there.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta.
   */
  public function attach(EntityMetaInterface $entity): void;

  /**
   * Dettach an entity meta.
   *
   * EntityMeta entities are removed from the existing list. When this happens,
   * they can either be marked to have their EntityMetaRelation revision skipped
   * or deleted.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta.
   *
   * @see self::entitiesToSkipRelations
   * @see self::entitiesToDeleteRelations
   */
  public function dettach(EntityMetaInterface $entity): void;

  /**
   * Get the first entity meta of the defined type attached in this field.
   *
   * If one does not exist, a new one is instantiated. This can then later be
   * attached to the list.
   *
   * @param string $bundle
   *   The EntityMeta type.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The entity meta or NULL if none found.
   */
  public function getEntityMeta(string $bundle): ?EntityMetaInterface;

}
