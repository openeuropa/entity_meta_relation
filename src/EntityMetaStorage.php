<?php

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangesDetectionTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Storage handler for the entity meta entities.
 */
class EntityMetaStorage extends SqlContentEntityStorage implements EntityMetaStorageInterface {

  use EntityChangesDetectionTrait {
    getFieldsToSkipFromTranslationChangesCheck as getFieldsToSkipFromEntityChangesCheck;
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if ($this->shouldMakeRevision($entity)) {
      $entity->setNewRevision(TRUE);
    }

    parent::save($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function doPostSave(EntityInterface $entity, $update) {
    parent::doPostSave($entity, $update);

    $host_entity = $entity->getEmrHostEntity();
    if ($host_entity) {
      $this->createEntityMetaRelation($host_entity, $entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createEntityMetaRelation(EntityInterface $content_entity, EntityInterface $meta_entity): void {
    $metaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $content_entity_type = $content_entity->getEntityType();

    $entity_meta_relation_content_field = $content_entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $content_entity_type->get('entity_meta_relation_meta_field');
    $entity_meta_relation_bundle = $content_entity_type->get('entity_meta_relation_bundle');

    // If we are editing an item, check if we have relations to this revision.
    $metaRelationsRevisionIds = $metaRelationStorage->getQuery()
      ->condition("{$entity_meta_relation_content_field}.target_revision_id", $content_entity->getRevisionId())
      ->condition("{$entity_meta_relation_meta_field}.target_id", $meta_entity->id())
      ->execute();

    // If no relations, create new ones.
    if (empty($metaRelationsRevisionIds)) {
      $relation = $metaRelationStorage->create([
        'bundle' => $entity_meta_relation_bundle,
        $entity_meta_relation_content_field => $content_entity,
        $entity_meta_relation_meta_field => $meta_entity,
      ]);
    }
    // Otherwise update existing ones.
    else {
      $relation = $metaRelationStorage->load(key($metaRelationsRevisionIds));
      $relation->set($entity_meta_relation_meta_field, $meta_entity);
    }

    $relation->save();
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityMetaRelated(ContentEntityInterface $contentEntity) {
    // Here we expect the entity to be the new revision so we need to load
    // the "loaded" revision and pass it in order to get the meta entity
    // references of the previous revision.
    $revision = $this->entityTypeManager->getStorage($contentEntity->getEntityTypeId())->loadRevision($contentEntity->getLoadedRevisionId());
    $referencedEntities = $this->getRelatedMetaEntities($revision);

    // Change entity meta status depending on content entity status.
    if (!empty($referencedEntities)) {
      foreach ($referencedEntities as $referencedEntity) {
        $contentEntity->isPublished() ? $referencedEntity->enable() : $referencedEntity->disable();
        $referencedEntity->setEmrHostEntity($contentEntity);
        $referencedEntity->save();
      }
    }
  }

  public function getRelatedMetaEntities(ContentEntityInterface $entity) {
    return $this->getRelatedEntities($entity);
  }

  public function getRelatedContentEntities(ContentEntityInterface $entity, string $entity_type) {
    return $this->getRelatedEntities($entity, $entity_type);
  }

  protected function getRelatedEntities(ContentEntityInterface $entity, string $entity_type = NULL) {
    if (!$entity_type) {
      $entity_type = $entity->getEntityType();
    }
    else {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type);
    }

    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $entity_type->get('entity_meta_relation_meta_field');

    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition($entity_meta_relation_content_field . '.target_revision_id', $entity->getRevisionId())
      ->execute();

    if (!$ids) {
      return [];
    }

    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface[] $entity_meta_relations */
    $entity_meta_relations = $entity_meta_relation_storage->loadMultiple($ids);

    $entity_meta_entities = [];
    foreach ($entity_meta_relations as $relation) {
      $entity = $relation->get($entity_meta_relation_meta_field)->entity;
      $entity_meta_entities[$entity->id()] = $entity;
    }

    return $entity_meta_entities;
  }


  /**
   * {@inheritdoc}
   */
  public function getBundledRelatedMetaEntities(ContentEntityInterface $content_entity): array {
    $relations = [];

    $related_meta_entities = $this->getRelatedMetaEntities($content_entity);
    if (!$related_meta_entities) {
      return [];
    }

    // Group referenced entities per bundle.
    foreach ($related_meta_entities as $meta_entity) {
      $relations[$meta_entity->bundle()][] = $meta_entity;
    }

    return $relations;
  }


  /**
   * Returns the fields that should indicate if the entity has changed.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *
   * @return array
   */
  public function getChangeFields(EntityMetaInterface $entity) {
    $fields = array_keys($entity->toArray());

    $field_blacklist = $this->getFieldsToSkipFromEntityChangesCheck($entity);
    return array_diff($fields, $field_blacklist);
  }

  /**
   * Checks whether it should make a new revision upon saving.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *
   * @return bool
   */
  protected function shouldMakeRevision(EntityMetaInterface $entity) {
    if ($entity->isNew()) {
      return TRUE;
    }

    $change_fields = $this->getChangeFields($entity);

    // In case there are revisions, load the latest revision to compare against.
    $revision_id = $this->getLatestRevisionId($entity->id());
    /** @var ContentEntityInterface $revision */
    $revision = $this->loadRevision($revision_id);

    foreach ($change_fields as $field) {
      // Only save a new revision if important fields changed.
      // If we encounter a change, we save a new revision.
      if (!empty($revision) && $entity->get($field)->hasAffectingChanges($revision->get($field)->filterEmptyItems(), $entity->language()->getId())) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
