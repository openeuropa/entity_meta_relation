<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\emr\Exception\EntityMetaEmptyException;

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
   * Called to verify if entity meta values did change or are empty.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity_meta
   *   The entity meta.
   */
  public function presaveEntityMeta(ContentEntityInterface $entity_meta) {
    $emptyEntity = TRUE;

    // Only act in case save was triggered by emr content entity form.
    if (empty($entity_meta->emrFieldsToCheck())) {
      return;
    }

    // Compare with previous revision.
    if (!$entity_meta->isNew()) {
      $latestRevision = \Drupal::entityTypeManager()
        ->getStorage($entity_meta->getEntityTypeId())
        ->loadUnchanged($entity_meta->id());
    }

    $emrFieldsToCheck = $entity_meta->emrFieldsToCheck();
    foreach ($emrFieldsToCheck as $field) {

      if (!is_string(($field))) {
        continue;
      }

      // This field is not empty.
      if (!$entity_meta->get($field)->isEmpty()) {
        $emptyEntity = FALSE;
      }

      // Only save a new revision if important fields changed.
      // If we encounter a change, we save a new revision.
      if (!empty($latestRevision) && $entity_meta->get($field)->hasAffectingChanges($latestRevision->get($field)->filterEmptyItems(), $entity_meta->language()->getId())) {
        $entity_meta->setNewRevision(TRUE);
      }
    }

    // If all fields were empty, do not save the entity.
    if ($emptyEntity) {
      throw new EntityMetaEmptyException('Entity fields are empty');
    }
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
