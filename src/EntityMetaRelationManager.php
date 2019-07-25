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

    if (empty($entity_meta->getEmrHostEntity())) {
      $referencedEntities = $this->getRelated('node', $entity_meta->getLoadedRevisionId());

      // Nothing to inject as references.
      if (!empty($referencedEntities)) {
        $entity_meta->setEmrHostEntity($referencedEntities[0]);
      }
    }

    // Only act in case save was triggered by emr content entity form.
    if (empty($entity_meta->getEmrFieldsToCheck())) {
      return;
    }

    // Compare with previous revision.
    if (!$entity_meta->isNew()) {
      $latestRevision = \Drupal::entityTypeManager()
        ->getStorage($entity_meta->getEntityTypeId())
        ->loadUnchanged($entity_meta->id());
    }

    $emrFieldsToCheck = $entity_meta->getEmrFieldsToCheck();
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
  public function getRelated(string $entity_type, string $revision_id): array {

    if ($entity_type == 'entity_meta') {
      $ownField = 'emr_node_revision';
      $relatedField = 'emr_meta_revision';
    }
    else {
      $ownField = 'emr_meta_revision';
      $relatedField = 'emr_node_revision';
    }

    $referencedEntities = [];
    $metaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $metaRelationsRevisionIds = $metaRelationStorage->getQuery()->condition($ownField . '.target_revision_id', $revision_id)->execute();
    $metaRelations = $metaRelationStorage->loadMultiple($metaRelationsRevisionIds);

    // Get all referenced entities.
    if (!empty($metaRelations)) {
      foreach ($metaRelations as $relation) {
        $entity_meta = $relation->get($relatedField)->referencedEntities()[0];
        $entity_meta->entity_meta_relation_bundle = $relation->bundle();
        $referencedEntities[] = $entity_meta;
      }
    }

    return $referencedEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function loadBundledEntityMetaRelations(ContentEntityInterface $content_entity): array {
    $relations = $referencedEntities = [];

    // New entity.
    $revision_id = $content_entity->getRevisionId();
    if (empty($revision_id)) {
      return [];
    }

    $referencedEntities = $this->getRelated('entity_meta', $revision_id);

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
  public function updateEntityMetaRelated(ContentEntityInterface $contentEntity) {
    $referencedEntities = $this->getRelated('entity_meta', $contentEntity->getLoadedRevisionId());

    // Change entity meta status depending on content entity status.
    if (!empty($referencedEntities)) {
      foreach ($referencedEntities as $referencedEntity) {
        $contentEntity->isPublished() ? $referencedEntity->enable() : $referencedEntity->disable();
        $contentEntity->entity_meta_relation_bundle = $referencedEntity->entity_meta_relation_bundle;
        $referencedEntity->setEmrHostEntity($contentEntity);
        $referencedEntity->save();
      }
    }
  }

}
