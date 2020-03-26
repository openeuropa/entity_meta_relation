<?php

namespace Drupal\emr\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;
use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\emr\Plugin\Field\FieldType\BaseEntityMetaRelationItem;

/**
 * Item list for a computed field that stores related entity metas.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ComputedEntityMetasItemList extends FieldItemList implements EntityMetaItemListInterface {

  use ComputedItemListTrait;

  /**
   * Detached meta entities for which we will skip relations.
   *
   * This happens when the host content entity is updated with a new revision
   * but it indicates that the EntityMetaRelation should not be updated. In
   * this case, the new revision of the host entity no longer links to this
   * EntityMeta. However, the previous revision will still link to the
   * EntityMeta via existing EntityMetaRelation revisions.
   *
   * @var \Drupal\emr\Entity\EntityMetaInterface[]
   */
  protected $entitiesToSkipRelations = [];

  /**
   * Detached entities for which we will delete relations.
   *
   * This happens when the host content entity is updated without a new revision
   * and it indicates that the EntityMetaRelation revision used to link to the
   * EntityMeta should be deleted.
   *
   * In this case, the host entity no longer links to this EntityMeta in the
   * current revision. However, the previous revisions will still link to the
   * EntityMeta via existing EntityMetaRelation revisions.
   *
   * @var \Drupal\emr\Entity\EntityMetaInterface[]
   */
  protected $entitiesToDeleteRelations = [];

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    // No need to compute again if we already have the list.
    if (!empty($this->list)) {
      return;
    }

    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = \Drupal::entityTypeManager()->getStorage('entity_meta');
    $entity_metas = $entity_meta_storage->getRelatedEntities($entity);

    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
    foreach ($entity_metas as $entity_meta_id => $entity_meta) {
      $delta = count($this->list);
      $this->list[$delta] = $this->createItem($delta, $entity_meta);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function attach(EntityMetaInterface $entity): void {
    if (!$this->shouldAttach($entity)) {
      return;
    }

    $values = $this->list;
    $uuid = $entity->uuid();

    /** @var \Drupal\emr\Entity\EntityMetaInterface $item */
    foreach ($values as $delta => $item) {
      if ($uuid === $item->entity->uuid()) {
        // If we already have it in the list, replace it and we are done.
        $values[$delta] = $entity;
        $this->setValue($values, TRUE);
        return;
      }
    }

    // Increase the delta and add it to the list.
    $delta = count($this->list);
    $values[$delta] = $entity;
    $this->setValue($values, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function detach(EntityMetaInterface $entity): void {
    $uuid = $entity->uuid();

    // If the host entity made a new revision, we don't want to create a new
    // revision of the EntityMetaRelation to point to this new revision.
    if ($this->getEntity()->isNewRevision()) {
      $this->entitiesToSkipRelations[$uuid] = $entity;
      return;
    }

    // If the host entity made no new revision, we want to delete the
    // EntityMetaRelation revision that points to the current host entity
    // revision.
    $this->entitiesToDeleteRelations[$uuid] = $entity;
  }

  /**
   * Checks whether the meta entity should be attached or not.
   *
   * EntityMeta entities are only attached in case they have changes because we
   * don't want them updated if there are no changes.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta entity.
   *
   * @return bool
   *   Whether it should attach or not.
   */
  protected function shouldAttach(EntityMetaInterface $entity): bool {
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = \Drupal::entityTypeManager()->getStorage('entity_meta');

    $change_fields = $entity_meta_storage->getChangeFields($entity);
    foreach ($change_fields as $field) {
      if (!$entity->get($field)->isEmpty()) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityMeta(string $bundle): EntityMetaInterface {
    if (empty($this->list)) {
      $this->computeValue();
    }

    foreach ($this->list as $item) {
      if (!$item->entity instanceof EntityMetaInterface) {
        continue;
      }

      if ($item->entity->bundle() == $bundle) {
        $entity_meta = $item->entity;
        break;
      }
    }

    if (empty($entity_meta)) {
      /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
      $entity_meta_storage = $entity_type_manager = \Drupal::entityTypeManager()->getStorage('entity_meta');
      $entity_meta = $entity_meta_storage->create([
        'bundle' => $bundle,
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
    if (!isset($values) || $values === []) {
      $this->list = [];

      if ($notify && isset($this->parent)) {
        $this->parent->onChange($this->name);
      }

      return;
    }

    // Keep track of the EntityMeta entities are no longer found in the list
    // so we can detach them.
    $to_detach = $this->list;

    // Only arrays are supported.
    if (!is_array($values)) {
      $values = [0 => $values];
    }

    foreach ($values as $delta => $item) {
      $entity_meta = $this->getEntityMetaFromFieldItem($item);
      if (!$entity_meta instanceof EntityMetaInterface) {
        unset($values[$delta]);
        continue;
      }

      if (!$this->shouldAttach($entity_meta)) {
        unset($to_detach[$delta]);
        continue;
      }

      // If we have the entity already in the list, remove it from the items
      // that need to be detached.
      $delta = $this->getItemDelta($entity_meta);
      if (!is_null($delta) && isset($to_detach)) {
        unset($to_detach[$delta]);
      }

      if (is_null($delta) || !isset($this->list[$delta])) {
        $delta = count($this->list);
        $this->list[$delta] = $this->createItem($delta, $entity_meta);
        continue;
      }

      // If we are updating an entity meta value that is already in the list,
      // set the "_original" value on it by loading the unchanged entity from
      // the storage. This will be used when saving to determine whether to
      // create a new revision.
      if (!$entity_meta->isNew()) {
        $storage = \Drupal::entityTypeManager()->getStorage('entity_meta');
        $storage->resetCache([$entity_meta->id()]);
        $entity_meta->_original = $storage->loadRevision($entity_meta->getRevisionId());
      }
      $this->list[$delta]->setValue($entity_meta, TRUE);
    }

    // Detach all the values that have not found themselves in the new list.
    $content_entity = $this->getEntity();
    foreach ($to_detach as $item) {
      /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
      $entity_meta = $item->entity;

      if ($content_entity->isNewRevision()) {
        $this->entitiesToSkipRelations[$entity_meta->uuid()] = $entity_meta;
        continue;
      }

      $this->entitiesToDeleteRelations[$entity_meta->uuid()] = $entity_meta;
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
    // Override default method from entity reference revisions because we don't
    // that behaviour.
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function postSave($update): bool {
    $revision = NULL;
    $reverting = FALSE;
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = \Drupal::entityTypeManager()->getStorage('entity_meta');

    if (empty($this->list) && empty($this->entitiesToSkipRelations) && empty($this->entitiesToDeleteRelations)) {
      // If we are saving the host entity and are creating a new revision which
      // does not have any items, we load the entity metas linked from the
      // loaded revision and set them onto the new revision if any exist.
      // The loaded revision is that one based on which a new revision is made
      // and it's typically useful when reverting revisions to allow us to
      // set onto the new revision the entity meta relations of the revision
      // being reverted from. We only do this, however, when there are no
      // metas marked to have their relations skipped or deleted because that
      // means they have been detached from the host entity leaving it empty
      // for a good reason in which case we don't want to do anything with the
      // metas.
      $entity = $this->getEntity();
      if ($entity->getLoadedRevisionId() !== $entity->getRevisionId()) {
        // If we don't have any metas in the list and a new revision is being
        // loaded it means we are reverting or pulling up metas that did not
        // change.
        $reverting = TRUE;
      }

      // Load up the revision of the content which represents the loaded one,
      // instead of the new attempted one.
      $revision_id = $entity->getLoadedRevisionId() ?? $entity->getRevisionId();
      $revision = \Drupal::entityTypeManager()->getStorage($entity->getEntityTypeId())->loadRevision($revision_id);

      $entity_metas = $entity_meta_storage->getRelatedEntities($revision);

      foreach ($entity_metas as $entity_meta_id => $entity_meta) {
        $delta = count($this->list);
        $this->list[$delta] = $this->createItem($delta, $entity_meta);
      }
    }

    // If we don't determine a potential new revision to use as the host entity
    // default to the current entity revision.
    if (!$revision) {
      $revision = $this->getEntity();
    }

    // If the host entity is new, get a list of potential default entity metas
    // that we need to create for it.
    $default_entity_metas = is_null($revision->original) ? $entity_meta_storage->getDefaultEntityMetas($revision) : [];

    foreach ($this->list as $item) {
      if (!$item->entity instanceof EntityMetaInterface) {
        continue;
      }

      /** @var \Drupal\emr\Entity\EntityMetaInterface $entity_meta */
      $entity_meta = $item->entity;
      if (isset($default_entity_metas[$entity_meta->bundle()])) {
        unset($default_entity_metas[$entity_meta->bundle()]);
      }

      if ($this->metaIsBeingDetached($entity_meta)) {
        $entity_meta->setHostEntity($this->getEntity());
        $entity_meta->setNewRevision(FALSE);
        $entity_meta->save();
        continue;
      }

      if ($revision->isPublished() !== $entity_meta->isEnabled()) {
        $entity_meta->setNewRevision(TRUE);
      }

      // Copy status from the host entity which can be an older revision.
      $revision->isPublished() ? $entity_meta->enable() : $entity_meta->disable();
      // The host entity needs to be the current revision, new one if we are
      // reverting.
      $entity_meta->setHostEntity($this->getEntity());
      if ($reverting) {
        $entity_meta->setHostEntityIsReverting($reverting);
      }

      $entity_meta->save();
    }

    // If we have default metas left (they have not been created overtly with
    // specific values), we go through each and create them as well.
    foreach ($default_entity_metas as $entity_meta) {
      $revision->isPublished() ? $entity_meta->enable() : $entity_meta->disable();
      $entity_meta->setHostEntity($this->getEntity());
      $entity_meta->save();
    }

    if ($this->list) {
      // If we had items in the list, the metas have already been saved and
      // detached if necessary.
      return parent::postSave($update);
    }

    // However, it's possible also to have detached metas AND an empty list
    // due to a brand new host entity revision.
    $detached = array_merge($this->entitiesToSkipRelations, $this->entitiesToDeleteRelations);
    foreach ($detached as $entity_meta) {
      if ($this->metaIsBeingDetached($entity_meta)) {
        // If we are detaching a meta and it wasn't in the list it means the
        // list values were not computed. In this case, we want to load the
        // previous revision of the host entity and set that onto the meta as
        // host so that it doesn't mark the meta as default.
        $revision = \Drupal::entityTypeManager()->getStorage($revision->getEntityTypeId())->loadRevision($revision->getLoadedRevisionId());
        $entity_meta->setHostEntity($revision);
        $entity_meta->setNewRevision(FALSE);
        $entity_meta->save();
      }
    }

    return parent::postSave($update);
  }

  /**
   * Determines if a given entity meta is being detached.
   *
   * Marks them also correctly on what should happen with their relations.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity_meta
   *   The entity meta.
   *
   * @return bool
   *   Returns TRUE if detached, FALSE otherwise.
   */
  protected function metaIsBeingDetached(EntityMetaInterface $entity_meta) {
    if (array_key_exists($entity_meta->uuid(), $this->entitiesToSkipRelations)) {
      $entity_meta->markToSkipRelations();
      return TRUE;
    }

    if (array_key_exists($entity_meta->uuid(), $this->entitiesToDeleteRelations)) {
      $entity_meta->markToDeleteRelations();
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Looks in the list for an EntityMeta and returns its delta.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity_meta
   *   The entity meta entity.
   *
   * @return int|null
   *   The delta or NULL if not found.
   */
  protected function getItemDelta(EntityMetaInterface $entity_meta): ?int {
    if (!$this->list) {
      return NULL;
    }

    foreach ($this->list as $delta => $item) {
      if ($item->entity->uuid() === $entity_meta->uuid()) {
        return (int) $delta;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * When the host entity is deleted, we want to delete all the related
   * EntityMeta entities. That will also delete the EntityMetaRelation entities
   * in turn.
   */
  public function delete() {
    $entity = $this->getEntity();
    /** @var \Drupal\emr\EntityMetaStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_meta');
    $storage->deleteAllRelatedMetaEntities($entity);
  }

  /**
   * {@inheritdoc}
   *
   * When a given revision of the host entity is deleted, we want to also
   * delete all the EntityMetaRelation revisions that point to this revision
   * being deleted.
   */
  public function deleteRevision() {
    parent::deleteRevision();
    /** @var \Drupal\emr\EntityMetaStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('entity_meta');
    $storage->deleteAllRelatedEntityMetaRelationRevisions($this->getEntity());
  }

  /**
   * Returns the EntityMeta object from a field item.
   *
   * The field item can come in more than one way so we need to determine
   * where the entity is located.
   *
   * @param mixed $item
   *   The field item.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface|null
   *   The EntityMeta or NULL if none can be found.
   */
  protected function getEntityMetaFromFieldItem($item): ?EntityMetaInterface {
    if (!$item) {
      return NULL;
    }

    if ($item instanceof EntityMetaInterface) {
      return $item;
    }

    if ($item instanceof BaseEntityMetaRelationItem) {
      return $item->entity;
    }

    if (is_array($item) && isset($item['entity'])) {
      return $item['entity'];
    }
  }

}
