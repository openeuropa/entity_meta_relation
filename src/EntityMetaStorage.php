<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Storage handler for the entity meta entities.
 */
class EntityMetaStorage extends SqlContentEntityStorage implements EntityMetaStorageInterface {

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
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity */
    parent::doPostSave($entity, $update);

    // If we are saving this meta entity only for its status change, we don't
    // want to query for the associated relations because there is nothing there
    // to update. The content relation revision was updated in
    // self::updateEntityMetaRelated().
    if (isset($entity->status_change) && $entity->status_change) {
      $entity->status_change = FALSE;
      return;
    }

    // Create or updates the entity meta relations for a given entity.
    // When a new content entity is saved or updated, we need to create or
    // update the EntityMetaRelation entity that connects it to an
    // EntityMeta entity. This means updating the revisions that the
    // EntityMetaRelation points to on the EntityMeta.
    $content_entity = $entity->getHostEntity();
    if (!$content_entity) {
      return;
    }

    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $content_entity_type = $content_entity->getEntityType();

    $entity_meta_relation_content_field = $content_entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $content_entity_type->get('entity_meta_relation_meta_field');
    $entity_meta_relation_bundle = $content_entity_type->get('entity_meta_relation_bundle');

    // If we are editing an item, check if we have relations to this revision.
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition("{$entity_meta_relation_content_field}.target_revision_id", $content_entity->getRevisionId())
      ->condition("{$entity_meta_relation_meta_field}.target_id", $entity->id())
      ->execute();

    // If no relations, create new ones.
    if (empty($ids)) {
      $relation = $entity_meta_relation_storage->create([
        'bundle' => $entity_meta_relation_bundle,
        $entity_meta_relation_content_field => $content_entity,
        $entity_meta_relation_meta_field => $entity,
      ]);
    }
    // Otherwise update existing ones.
    else {
      $relation = $entity_meta_relation_storage->loadRevision(key($ids));
      $relation->set($entity_meta_relation_meta_field, $entity);
    }

    $relation->save();
  }

  /**
   * {@inheritdoc}
   */
  public function unlinkRelation(EntityMetaInterface $entity_meta, ContentEntityInterface $content_entity): void {
    $content_entity_type = $content_entity->getEntityType();
    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $entity_meta_relation_content_field = $content_entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $content_entity_type->get('entity_meta_relation_meta_field');

    $ids = $entity_meta_relation_storage->getQuery()
      ->condition("{$entity_meta_relation_content_field}.target_revision_id", $content_entity->getRevisionId())
      ->condition("{$entity_meta_relation_meta_field}.target_id", $entity_meta->id())
      ->execute();

    // There should normally only be one result, the last revision ID of the
    // entity meta relation that links the content entity to the meta entity.
    if (!$ids) {
      return;
    }

    $revision_id = key($ids);
    /** @var \Drupal\Core\Entity\RevisionableInterface $entity_meta_relation */
    $entity_meta_relation = $entity_meta_relation_storage->loadRevision($revision_id);

    // Load all the revision IDs of this entity meta relation.
    $revision_ids = $entity_meta_relation_storage->revisionIds($entity_meta_relation);

    // Keep track of the last revision ID because this is the one we want
    // to delete.
    $delete_revision_id = array_pop($revision_ids);
    // Get the previous revision ID because we want to make this one the
    // default.
    $revision_id = array_pop($revision_ids);
    /** @var \Drupal\Core\Entity\RevisionableInterface $revision */
    $revision = $entity_meta_relation_storage->loadRevision($revision_id);
    $revision->isDefaultRevision(TRUE);
    $revision->save();
    $entity_meta_relation_storage->deleteRevision($delete_revision_id);
  }

  /**
   * {@inheritdoc}
   */
  public function updateEntityMetaRelated(ContentEntityInterface $entity): void {
    // Here we expect the entity to be the new revision so we need to load
    // the "loaded" revision and pass it in order to get the meta entity
    // references of the previous revision.
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $entity_type = $entity->getEntityType();
    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');

    // If we are editing an item, check if we have relations to this revision.
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition("{$entity_meta_relation_content_field}.target_revision_id", $entity->getLoadedRevisionId())
      ->execute();

    if (!$ids) {
      // If no IDs are found, it means the entity does not have any relations.
      return;
    }

    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface[] $entity_meta_relations */
    $entity_meta_relations = $entity_meta_relation_storage->loadMultiple($ids);
    foreach ($entity_meta_relations as $entity_meta_relation) {
      $entity_meta_relation->setNewRevision(TRUE);
      $entity_meta_relation->set($entity_meta_relation_content_field, $entity);
      $entity_meta_relation->save();
    }

    $entity_meta_entities = $this->getRelatedMetaEntities($entity);

    foreach ($entity_meta_entities as $entity_meta) {
      // Update the status based on the one of the host entity.
      $entity->isPublished() ? $entity_meta->enable() : $entity_meta->disable();
      // We set a marker on the entity so that in the postSave() it can return
      // early and it doesn't have to query for the relations as there is no
      // meta relation needed to update.
      $entity_meta->status_change = TRUE;
      // Set the host entity so that self::postSave() can know which entity is
      // actually being updated.
      $entity_meta->setHostEntity($entity);
      $entity_meta->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedMetaEntities(ContentEntityInterface $entity): array {
    return $this->getRelatedEntities($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedContentEntities(EntityMetaInterface $entity, string $entity_type): array {
    return $this->getRelatedEntities($entity, $entity_type);
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
   * {@inheritdoc}
   */
  public function deleteAllRelatedMetaEntities(ContentEntityInterface $content_entity): void {
    $entity_type = $content_entity->getEntityType();

    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $entity_type->get('entity_meta_relation_meta_field');

    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition($entity_meta_relation_content_field . '.target_id', $content_entity->id())
      ->allRevisions()
      ->execute();

    if (!$ids) {
      return;
    }

    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface[] $entity_meta_relations */
    $entity_meta_relations = $entity_meta_relation_storage->loadMultiple($ids);
    foreach ($entity_meta_relations as $relation) {
      $entity = $relation->get($entity_meta_relation_meta_field)->entity;
      if ($entity instanceof EntityMetaInterface) {
        $entity->delete();
      }
    }

    foreach ($entity_meta_relations as $relation) {
      $relation->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getChangeFields(EntityMetaInterface $entity): array {
    $all_fields = $entity->getFieldDefinitions();
    $fields = [];
    foreach ($all_fields as $field => $definition) {
      if ($definition instanceof FieldConfigInterface) {
        $fields[] = $field;
      }
    }

    return $fields;
  }

  /**
   * Queries and returns for related entities.
   *
   * This can either be from the direction of an EntityMeta (returning related
   * content entities) or from that of a content entity (returning EntityMeta
   * entities).
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to look for related entities.
   * @param string|null $entity_type_id
   *   The entity type in case we're looking for content entities.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface[]
   *   The related entities.
   */
  protected function getRelatedEntities(ContentEntityInterface $entity, string $entity_type_id = NULL): array {
    if (!$entity_type_id) {
      $entity_type = $entity->getEntityType();
    }
    else {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    }

    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $entity_type->get('entity_meta_relation_meta_field');

    $relation_field = (empty($entity_type_id) || $entity_type_id === 'entity_meta') ? $entity_meta_relation_content_field : $entity_meta_relation_meta_field;

    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition($relation_field . '.target_revision_id', $entity->getRevisionId())
      ->allRevisions()
      ->execute();

    if (!$ids) {
      return [];
    }

    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface[] $entity_meta_relations */
    $entity_meta_relations = $entity_meta_relation_storage->loadMultipleRevisions(array_keys($ids));

    $related_entities = [];
    // If we are looking for related EntityMeta entities, we use the field that
    // relate to the content entities and vice-versa.
    $relation_field = $entity instanceof EntityMetaInterface ? $entity_meta_relation_content_field : $entity_meta_relation_meta_field;
    foreach ($entity_meta_relations as $relation) {
      $entity = $relation->get($relation_field)->entity;
      $related_entities[$entity->id()] = $entity;
    }

    return $related_entities;
  }

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
  protected function shouldMakeRevision(EntityMetaInterface $entity): bool {
    if ($entity->isNew()) {
      return TRUE;
    }

    $change_fields = $this->getChangeFields($entity);

    // In case there are revisions, load the latest revision to compare against.
    $revision_id = $this->getLatestRevisionId($entity->id());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
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
