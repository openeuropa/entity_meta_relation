<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for EntityMetaRelationManager services.
 */
interface EntityMetaRelationManagerInterface {

  /**
   * Creates new entity meta relation.
   *
   * @param string $bundle
   *   The bundle to create.
   * @param \Drupal\Core\Entity\EntityInterface $content_entity
   *   The content entity.
   * @param \Drupal\Core\Entity\EntityInterface $meta_entity
   *   The meta emtity.
   */
  public function createEntityMetaRelation(string $bundle, EntityInterface $content_entity, EntityInterface $meta_entity): void;

  /**
   * Gets related entities meta revisions ids.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The content_entity.
   *
   * @return array
   *   The list of meta entities related with this content revision.
   */
  public function getRelatedEntityMeta(ContentEntityInterface $content_entity): array;

  /**
   * Loads the associated meta entities with this content entity.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The content_entity.
   *
   * @return array
   *   The list of meta entities related with this content revision.
   */
  public function loadBundledEntityMetaRelations(ContentEntityInterface $content_entity): array;

  /**
   * Copies previously relations referencing entity meta.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\EntityInterface $entity_meta
   *   The Entity Meta.
   * @param string $relation_field
   *   The relation field name to use.
   */
  public function copyEntityMetaRelations(ContentEntityInterface $entity_meta, string $relation_field): void;

}
