<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\emr\Entity\EntityMetaRelationInterface;

/**
 * Storage handler for the entity meta relation entities.
 */
class EntityMetaRelationStorage extends SqlContentEntityStorage implements EntityMetaRelationStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EntityMetaRelationInterface $entity_meta_relation): array {
    return $this->database->query(
      'SELECT revision_id FROM {' . $this->getRevisionTable() . '} WHERE id=:id ORDER BY revision_id',
      [':id' => $entity_meta_relation->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function getRelationFieldName(ContentEntityInterface $entity, string $target): ?string {
    $fields = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $field */
    foreach ($fields as $field) {
      if ($field->getType() == 'entity_reference_revisions') {
        if ($target === EntityMetaRelationStorageInterface::RELATION_FIELD_TARGET_CONTENT && $field->getSetting('target_type') !== 'entity_meta') {
          return $field->getName();
        }
        if ($target === EntityMetaRelationStorageInterface::RELATION_FIELD_TARGET_META && $field->getSetting('target_type') === 'entity_meta') {
          return $field->getName();
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * Drupal core does not allow the deletion of default revisions. But in some
   * cases, we need to delete a revision that is marked as default. So before
   * we can do that, we need to make the previous revision the default one to
   * allow the deletion.
   */
  public function deleteRevision($revision_id) {
    /** @var \Drupal\Core\Entity\RevisionableInterface $revision */
    $revision = $this->loadRevision($revision_id);
    if (!$revision->isDefaultRevision()) {
      // If it's not the default revision we just defer to the parent to delete
      // it.
      parent::deleteRevision($revision_id);
      $this->deleteOrphanEntityMetaRevision($revision);
      return;
    }

    // Check to see if there are more than 1 revisions of this entity. If there
    // is only one, delete the entire revision.
    $revision_ids = $this->revisionIds($revision);
    if (count($revision_ids) === 1) {
      $this->delete($this->loadMultipleRevisions($revision_ids));
      $this->deleteOrphanEntityMetaRevision($revision);
      return;
    }

    // Mark the previous revision as the default and then defer to the parent
    // to perform the deletion.
    array_pop($revision_ids);
    $revision_id_to_default = end($revision_ids);
    $revision_to_default = $this->loadRevision($revision_id_to_default);
    $revision_to_default->isDefaultRevision(TRUE);
    $revision_to_default->setNewRevision(FALSE);
    $revision_to_default->save();

    parent::deleteRevision($revision_id);
    $this->deleteOrphanEntityMetaRevision($revision);
  }

  /**
   * {@inheritdoc}
   *
   * Whenever a relation is deleted we need to check if the entity meta it was
   * pointing towards still has any any other relations pointing towards it. If
   * not, we delete the entity meta to prevent it remaining an orphan.
   */
  public function delete(array $entities) {
    parent::delete($entities);

    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface $relation */
    foreach ($entities as $relation) {
      $field_name = $this->getRelationFieldName($relation, EntityMetaRelationStorageInterface::RELATION_FIELD_TARGET_META);
      $target_revision_id = $relation->get($field_name)->target_revision_id;
      $entity_meta = \Drupal::entityTypeManager()->getStorage('entity_meta')->loadRevision($target_revision_id);
      if (!$entity_meta instanceof EntityMetaInterface) {
        continue;
      }

      $results = $this->getQuery()->condition("{$field_name}.target_id", $entity_meta->id())->allRevisions()->execute();
      if ($results) {
        // If we find revisions, we don't do anything.
        continue;
      }

      $entity_meta->delete();
    }
  }

  /**
   * Deletes orphan EntityMeta revisions.
   *
   * Whenever we delete an EntityMetaRelation revision, we need to check that
   * the EntityMeta revision it was pointing to was not left orphaned (no other
   * relations point to it). If it did, we need to delete it.
   *
   * @param \Drupal\emr\Entity\EntityMetaRelationInterface $revision
   *   The EntityMetaRelation revision ID.
   */
  protected function deleteOrphanEntityMetaRevision(EntityMetaRelationInterface $revision): void {
    $field_name = $this->getRelationFieldName($revision, EntityMetaRelationStorageInterface::RELATION_FIELD_TARGET_META);
    $entity_meta_revision_id = $revision->get($field_name)->target_revision_id;

    $results = $this->getQuery()->condition("{$field_name}.target_revision_id", $entity_meta_revision_id)->allRevisions()->execute();
    if ($results) {
      return;
    }

    // If there are no more relation revisions pointing to that entity meta
    // revision, we need to delete it.
    $this->entityTypeManager->getStorage('entity_meta')->deleteRevision($entity_meta_revision_id);
    // If the revision we are deleting is marked as default, un-track it from
    // the tracking table.
    $this->database->delete('entity_meta_default_revision')
      ->condition('default_revision_id', $entity_meta_revision_id)
      ->execute();
  }

}
