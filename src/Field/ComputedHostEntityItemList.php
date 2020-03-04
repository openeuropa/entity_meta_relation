<?php

namespace Drupal\emr\Field;

use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;

/**
 * Item list for a computed field that stores related host entity.
 */
class ComputedHostEntityItemList extends EntityReferenceRevisionsFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    $entity_type_manager = \Drupal::entityTypeManager();
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $entity_type_manager->getStorage('entity_meta');
    $content_entities = $entity_meta_storage->getRelatedEntities($entity);
    foreach ($content_entities as $content_entity_id => $content_entity) {
      $this->list[] = $this->createItem(count($this->list), $content_entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    // We don't have entity metas, we need to carry over from published ones.
    if (empty($this->list)) {
      $entity_type_manager = \Drupal::entityTypeManager();

      $entity = $this->getEntity();
      // Load up the revision of the content which represents the loaded one,
      // instead of the new attempted one.
      $revision_id = $entity->getLoadedRevisionId() ?? $entity->getRevisionId();
      if (!$revision_id) {
        return;
      }

      $revision = $entity_type_manager->getStorage('entity_meta')->loadRevision($revision_id);
      /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
      $entity_meta_storage = $entity_type_manager->getStorage('entity_meta');
      $content_entities = $entity_meta_storage->getRelatedEntities($revision);
      foreach ($content_entities as $content_entity_id => $content_entity) {
        $this->list[] = $this->createItem(count($this->list), $content_entity);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    // We don't want to validate any constraints related with entity reference.
    return [];
  }

}
