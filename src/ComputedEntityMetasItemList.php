<?php

namespace Drupal\emr;

use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;

/**
 * Item list for a computed field that stores related entity metas.
 */
class ComputedEntityMetasItemList extends EntityReferenceRevisionsFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $entity_metas = $entity_type_manager->getStorage('entity_meta')->getRelatedEntities($entity);
    /** @var Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    foreach ($entity_metas as $entity_meta_id => $entity_meta) {
      $id = $entity_meta->uuid();
      $this->list[$id] = $this->createItem(count($this->list), $entity_meta);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (!isset($values) || $values === []) {
      $this->list = [];
    }
    else {
      // Only arrays are supported.
      if (!is_array($values)) {
        $values = [0 => $values];
      }

      foreach (array_values($values) as $delta => $value) {

        if (!$value instanceof EntityMetaInterface) {
          continue;
        }

        $id = $value->uuid();

        if (!isset($this->list[$delta])) {
          $this->list[$id] = $this->createItem($delta, $value);
        }
        else {
          $this->list[$id]->setValue($value, FALSE);
        }
      }
    }
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update): bool {

    // We don't have entity metas, we need to carry over from published ones.
    if (empty($this->list)) {
      $entity_type_manager = \Drupal::service('entity_type.manager');
      $entity_metas = $entity_type_manager->getStorage('entity_meta')->getRelatedEntities($this->getEntity(), $this->getEntity()->getLoadedRevisionId());
      foreach ($entity_metas as $entity_meta_id => $entity_meta) {
        $this->list[] = $this->createItem(count($this->list), $entity_meta);
      }
    }

    foreach ($this->list as $item) {
      if (!$item->entity instanceof EntityMetaInterface) {
        continue;
      }

      // Copy status from the host entity.
      $this->getEntity()->isPublished() ? $item->entity->enable() : $item->entity->disable();
      $item->entity->setHostEntity($this->getEntity());
      $item->entity->save();
    }

    return parent::postSave($update);
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $entity = $this->getEntity();
    /** @var \Drupal\emr\EntityMetaStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_meta');
    $storage->deleteAllRelatedMetaEntities($entity);
  }

}
