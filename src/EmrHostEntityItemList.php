<?php

namespace Drupal\emr;

use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;

/**
 * Item list for a computed field that stores related host entity.
 */
class EmrHostEntityItemList extends EntityReferenceRevisionsFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $content_entities = $entity_type_manager->getStorage('entity_meta')->getRelatedEntities($entity);
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
      $entity_type_manager = \Drupal::service('entity_type.manager');
      $content_entities = $entity_type_manager->getStorage('entity_meta')->getRelatedEntities($this->getEntity(), $this->getEntity()->getLoadedRevisionId());
      foreach ($content_entities as $content_entity_id => $content_entity) {
        $this->list[] = $this->createItem(count($this->list), $content_entity);
      }
    }
  }

}
