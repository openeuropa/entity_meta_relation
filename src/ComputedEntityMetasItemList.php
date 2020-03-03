<?php

namespace Drupal\emr;

use Drupal\Component\Uuid\Uuid;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;

/**
 * Item list for a computed field that stores related entity metas.
 */
class ComputedEntityMetasItemList extends EntityReferenceRevisionsFieldItemList {

  use ComputedItemListTrait;

  /**
   * Entities to be marked to skip relations.
   *
   * @var array
   */
  protected $entitiesToSkipRelations = [];

  /**
   * Entities to be marked to delete relations.
   *
   * @var array
   */
  protected $entitiesToDeleteRelations = [];

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    // No need to compute again.
    if (!empty($this->list)) {
      return;
    }

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
   * Attach entity meta.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta.
   */
  public function attach(EntityMetaInterface $entity): void {
    // If there are no changes, don't attach.
    if (!$this->shouldAttach($entity)) {
      return;
    }

    $values = $this->list;
    $id = $entity->uuid();
    $values[$id] = $entity;
    $this->setValue($values, TRUE);
  }

  /**
   * Dettach entity meta.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta.
   */
  public function dettach(EntityMetaInterface $entity): void {
    $id = $entity->uuid();
    // Reset item because of changes in host entity.
    if ($this->getEntity()->isNewRevision()) {
      $this->entitiesToSkipRelations[] = $id;
    }
    else {
      $this->entitiesToDeleteRelations[] = $id;
    }
  }

  /**
   * Checks whether the meta entity should be attached or not.
   *
   * Entity is only attached in case it has changes.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta entity.
   *
   * @return bool
   *   Whether it should save or not.
   */
  protected function shouldAttach(EntityMetaInterface $entity): bool {
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $entity_type_manager = \Drupal::service('entity_type.manager')->getStorage('entity_meta');

    if ($entity === NULL) {
      return FALSE;
    }

    $change_fields = $entity_meta_storage->getChangeFields($entity);
    foreach ($change_fields as $field) {
      if (!$entity->get($field)->isEmpty()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the first entity meta of the defined type attached in this field.
   *
   * @param string $entity_meta_bundle
   *   The entity meta type.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The entity meta.
   */
  public function getEntityMeta(string $entity_meta_bundle): EntityMetaInterface {

    if (empty($this->list)) {
      $this->computeValue();
    }

    $entity = $this->getEntity();
    $entity_meta_storage = $entity_type_manager = \Drupal::service('entity_type.manager')->getStorage('entity_meta');

    foreach ($this->list as $item) {
      if (!$item->entity instanceof EntityMetaInterface) {
        continue;
      }

      if ($item->entity->bundle() == $entity_meta_bundle) {
        $entity_meta = $item->entity;
        break;
      }
    }

    if (empty($entity_meta)) {
      /** @var \Drupal\emr\EntityMetaWrapper $entity_meta */
      $entity_meta = $entity_meta_storage->create([
        'bundle' => $entity_meta_bundle,
        'status' => $entity->isPublished(),
      ]);
    }

    return $entity_meta;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function setValue($values, $notify = TRUE) {
    $old_values_ids = array_keys($this->list);

    if (!isset($values) || $values === []) {
      $this->list = [];
    }
    else {
      // Only arrays are supported.
      if (!is_array($values)) {
        $values = [0 => $values];
      }

      foreach ($values as $delta => $value) {

        // Id can be different deppending what we have attached.
        if (Uuid::isValid($delta)) {
          $id = $delta;
        }
        else {
          $id = $value instanceof EntityMetaInterface ? $value->uuid() : $value->entity->uuid();
        }

        // Remove from old ids for future pruning.
        $key = array_search($id, $old_values_ids);
        if ($key !== FALSE) {
          unset($old_values_ids[$key]);
        }

        if (!$value instanceof EntityMetaInterface) {
          continue;
        }

        if (!isset($this->list[$id])) {
          $this->list[$id] = $this->createItem($id, $value);
        }
        else {
          $this->list[$id]->setValue($value, TRUE);
        }
      }
      // If the value was not attached, clean extraneous values.
      if (!empty($old_values_ids)) {
        $content_entity = $this->getEntity();
        if ($content_entity->isNewRevision()) {
          $this->entitiesToSkipRelations = array_merge($old_values_ids, $this->entitiesToSkipRelations);
        }
        else {
          $this->entitiesToDeleteRelations = array_merge($old_values_ids, $this->entitiesToDeleteRelations);
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
  public function preSave() {
    // Override default method from entity reference revisions.
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

      $id = $item->entity->uuid();
      if (in_array($id, $this->entitiesToSkipRelations)) {
        $item->entity->markToSkipRelations();
      }
      if (in_array($id, $this->entitiesToDeleteRelations)) {
        $item->entity->markToDeleteRelations();
      }

      if ($this->getEntity()->isPublished() != $item->entity->isEnabled()) {
        $item->entity->setNewRevision(TRUE);
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
