<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Handles relationship logic between content and meta entities.
 */
class EntityMetaRelationManager implements EntityMetaRelationManagerInterface {

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
   * {@inheritdoc}
   */
  public function createEntityMetaRelation(string $bundle, EntityInterface $content_entity, EntityInterface $meta_entity): void {

    $metaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');

    // If we are editing an item, check if we have relations to this revision.
    $metaRelationsRevisionIds = $metaRelationStorage->getQuery()
      ->condition('emr_node_revision.target_revision_id', $content_entity->getRevisionId())
      ->condition('emr_meta_revision.target_id', $meta_entity->id())
      ->execute();

    // If no relations, create new ones.
    if (empty($metaRelationsRevisionIds)) {
      $relation = $metaRelationStorage->create([
        'bundle' => $bundle,
        'emr_meta_revision' => $meta_entity,
        'emr_node_revision' => $content_entity,
      ]);
    }
    // Otherwise update existing ones.
    else {
      $relation = $metaRelationStorage->load(key($metaRelationsRevisionIds));
      $relation->set('emr_meta_revision', $meta_entity);
    }

    $relation->save();
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
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
   * {@inheritdoc}
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
