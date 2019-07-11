<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Handles relationship logic between content and meta entities.
 */
class EntityMetaRelationManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the EntityRelationManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

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
  public function createEntityMetaRelation(string $bundle, EntityInterface $content_entity, EntityInterface $meta_entity): void {

    $metaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $metaRelationStorage->create([
      'bundle' => $bundle,
      'emr_meta_revision' => $meta_entity,
      'emr_node_revision' => $content_entity,
    ])->save();
  }

  /**
   * Gets related entities meta revisions ids.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The content_entity.
   *
   * @return array
   *   The list of meta entities related with this content revision.
   */
  public function getRelatedEntityMeta(ContentEntityInterface $content_entity): array {
    $referencedEntities = [];
    $metaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $metaRelationsRevisionIds = $metaRelationStorage->getQuery()->condition('emr_node_revision.target_revision_id', $content_entity->getRevisionId())->execute();
    $metaRelations = $metaRelationStorage->loadMultiple($metaRelationsRevisionIds);

    // Get all referenced entities.
    if (!empty($metaRelations)) {
      foreach ($metaRelations as $relation) {
        $referencedEntities[] = $relation->get('emr_meta_revision')->referencedEntities()[0];
      }
    }

    return $referencedEntities;
  }

  /**
   * Loads the associated meta entities with this content entity.
   *
   * @param Drupal\Core\Entity\ContentEntityInterface $content_entity
   *   The content_entity.
   *
   * @return array
   *   The list of meta entities related with this content revision.
   */
  public function loadBundledEntityMetaRelations(ContentEntityInterface $content_entity): array {
    $relations = $referencedEntities = [];
    $referencedEntities = $this->getRelatedEntityMeta($content_entity);

    // Groups referenced entities per bundle.
    if (!empty($referencedEntities)) {
      foreach ($referencedEntities as $referencedEntity) {
        $relations[$referencedEntity->bundle()][] = $referencedEntity;
      }
    }

    return $relations;
  }

  /**
   * Copies previously relations referencing entity meta.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|\Drupal\Core\Entity\EntityInterface $entity_meta
   *   The Entity Meta.
   * @param string $relation_field
   *   The relation field name to use.
   */
  public function copyEntityMetaRelations(ContentEntityInterface $entity_meta, string $relation_field): void {
    $entityMetaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');

    $previousRevisionId = $entity_meta->getLoadedRevisionId();
    $entityMetaRelationsRevisionIds = $entityMetaRelationStorage->getQuery()->condition($relation_field . '.target_revision_id', $previousRevisionId)->execute();

    if (empty($entityMetaRelationsRevisionIds)) {
      return;
    }

    foreach ($entityMetaRelationsRevisionIds as $entityMetaRelationRevisionId) {
      $entityMetaRelation = $entityMetaRelationStorage->loadRevision($entityMetaRelationRevisionId);
      $entityMetaRelation->set($relation_field, $entity_meta);
      $entityMetaRelation->save();
    }
  }

}
