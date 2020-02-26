<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\emr\Entity\EntityMetaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage handler for the entity meta entities.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityMetaStorage extends SqlContentEntityStorage implements EntityMetaStorageInterface {

  /**
   * The entity meta wrapper factory.
   *
   * @var \Drupal\emr\EntityMetaWrapperFactoryInterface
   */
  protected $entityMetaWrapperFactory;

  /**
   * Constructs a SqlContentEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend to be used.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend to be used.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\emr\EntityMetaWrapperFactoryInterface $entity_meta_wrapper_factory
   *   The entity meta wrapper factory.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache = NULL, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityTypeManagerInterface $entity_type_manager = NULL, EntityMetaWrapperFactoryInterface $entity_meta_wrapper_factory) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager);
    $this->entityMetaWrapperFactory = $entity_meta_wrapper_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity_field.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('emr.entity_meta_wrapper.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    // Guarantee wrapper.
    $entity = parent::create($values);
    $entity->setWrapper($this->entityMetaWrapperFactory->create($entity));
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function postLoad(array &$entities) {
    parent::postLoad($entities);
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity */
    foreach ($entities as &$entity) {
      // Injects the wrapper in the entity.
      $entity->setWrapper($this->entityMetaWrapperFactory->create($entity));
    }
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
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
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
    if (!$content_entity || $content_entity->isNew()) {
      return;
    }

    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $content_entity_type = $content_entity->getEntityType();

    $entity_meta_relation_content_field = $content_entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $content_entity_type->get('entity_meta_relation_meta_field');
    $entity_meta_relation_bundle = $content_entity_type->get('entity_meta_relation_bundle');

    // If we are editing an item, check if we have relations to this revision.
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition("{$entity_meta_relation_content_field}.target_id", $content_entity->id())
      ->condition("{$entity_meta_relation_meta_field}.target_id", $entity->id())
      ->execute();

    // If entity is marked to be dettached and it is not saving a new revision.
    if ($entity->shouldDettach() && (!$entity->isNewRevision()) && !empty($ids)) {
      // We need to delete existing relations.
      $relation = $entity_meta_relation_storage->loadRevision(key($ids));
      $relation->delete();
      return;
    }
    // Otherwise, if the entity is creating a new revision, we won't save
    // the relation.
    elseif ($entity->shouldDettach()) {
      return;
    }

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
      $relation->set($entity_meta_relation_content_field, $content_entity);
      $relation->set($entity_meta_relation_meta_field, $entity);
      $relation->setNewRevision(TRUE);
    }

    $relation->save();
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    parent::delete($entities);

    // For each entity meta that we delete, we make sure we delete all the
    // associated entity meta relation entities. This is because once an entity
    // meta is deleted for any reason, there is no more relation that needs to
    // existing between it and any content entity.
    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $entity_meta_relation_fields = [];
    // Determine the field names that reference the entity metas.
    // @todo move this to a base field as it will always be the same field.
    foreach ($this->entityTypeManager->getDefinitions() as $definition) {
      if ($definition->get('entity_meta_relation_meta_field')) {
        $entity_meta_relation_fields[] = $definition->get('entity_meta_relation_meta_field');
      }
    }

    $entity_meta_relation_fields = array_unique($entity_meta_relation_fields);

    // Get the entity meta IDs being deleted.
    $entity_meta_ids = [];
    foreach ($entities as $entity) {
      $entity_meta_ids[] = $entity->id();
    }

    foreach ($entity_meta_relation_fields as $field_name) {
      $ids = $entity_meta_relation_storage->getQuery()
        ->condition("{$field_name}.target_id", $entity_meta_ids, 'IN')
        ->execute();

      if (!$ids) {
        // This should not happen normally as relations should exist for entity
        // metas.
        continue;
      }

      // Delete all the associated relation entities.
      $entity_meta_relations = $entity_meta_relation_storage->loadMultiple($ids);
      $entity_meta_relation_storage->delete($entity_meta_relations);
    }
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
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function getRelatedEntities(ContentEntityInterface $entity, int $revision_id = NULL): array {

    $entity_type = $entity->getEntityType();
    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $entity_type->get('entity_meta_relation_meta_field');
    $relation_field = $entity instanceof EntityMetaInterface ? 'emr_meta_revision' : $entity_meta_relation_content_field;

    // Get all revisions of this content entity.
    if ($revision_id === -1) {
      $target_field = 'target_id';
      $target_id = $entity->id();
    }
    elseif (!empty($revision_id)) {
      $target_field = 'target_revision_id';
      $target_id = $revision_id;
    }
    else {
      $target_field = 'target_revision_id';
      $target_id = $entity->getRevisionId();
    }

    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition($relation_field . '.' . $target_field, $target_id)
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
    foreach ($entity_meta_relations as $relation) {

      $reverse_relation_field = $entity instanceof EntityMetaInterface ? $entity_meta_relation_storage->getContentFieldName($relation) : $entity_meta_relation_meta_field;

      // Avoid loading revisions of wrong bundle.
      if (!$relation->hasField($reverse_relation_field)) {
        continue;
      }

      $related_entity = $relation->get($reverse_relation_field)->entity;
      $id = $related_entity->getEntityTypeId() . ':' . $related_entity->id();
      // Only original revisions.
      if ($related_entity->getEntityTypeId() == 'entity_meta' || empty($related_entities[$id])) {
        $related_entities[$related_entity->getEntityTypeId() . ':' . $related_entity->id()] = $related_entity;
      }
    }

    return $related_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldMakeRevision(EntityMetaInterface $entity): bool {

    if ($entity->isNewRevision()) {
      return TRUE;
    }

    if ($entity->isNew()) {
      return TRUE;
    }

    // Host entity is keeping the revision, we will follow.
    if (!empty($entity->getHostEntity()) && ($entity->getHostEntity()->getLoadedRevisionId() == $entity->getHostEntity()->getRevisionId())) {
      return FALSE;
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
