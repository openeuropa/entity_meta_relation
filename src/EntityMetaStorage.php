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
use Drupal\emr\Plugin\EntityMetaRelationPluginManager;
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
   * The entity meta relation plugin manager.
   *
   * @var \Drupal\emr\Plugin\EntityMetaRelationPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs a EntityMetaStorage object.
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
   * @param \Drupal\emr\Plugin\EntityMetaRelationPluginManager $pluginManager
   *   The entity meta relation plugin manager.
   *
   * @SuppressWarnings(PHPMD.ExcessiveParameterList)
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache = NULL, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, EntityTypeManagerInterface $entity_type_manager = NULL, EntityMetaWrapperFactoryInterface $entity_meta_wrapper_factory, EntityMetaRelationPluginManager $pluginManager) {
    parent::__construct($entity_type, $database, $entity_field_manager, $cache, $language_manager, $memory_cache, $entity_type_bundle_info, $entity_type_manager);
    $this->entityMetaWrapperFactory = $entity_meta_wrapper_factory;
    $this->pluginManager = $pluginManager;
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
      $container->get('emr.entity_meta_wrapper.factory'),
      $container->get('plugin.manager.emr')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    // Set the wrapper on the EntityMeta.
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity */
    $entity = parent::create($values);
    $entity->setWrapper($this->entityMetaWrapperFactory->create($entity));

    $default_definition = $this->pluginManager->getDefaultDefinitionForBundle($entity->bundle());
    if ($default_definition) {
      /** @var \Drupal\emr\Plugin\EntityMetaRelationPluginInterface $plugin */
      $plugin = $this->pluginManager->createInstance($default_definition['id']);
      $plugin->fillDefaultEntityMetaValues($entity);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function revisionIds(EntityMetaInterface $entity_meta): array {
    return $this->database->query(
      'SELECT revision_id FROM {' . $this->getRevisionTable() . '} WHERE id=:id ORDER BY revision_id',
      [':id' => $entity_meta->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  protected function postLoad(array &$entities) {
    parent::postLoad($entities);
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity */
    foreach ($entities as &$entity) {
      // Set the wrapper on the EntityMeta.
      $entity->setWrapper($this->entityMetaWrapperFactory->create($entity));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity */
    if ($this->shouldMakeRevision($entity)) {
      $entity->setNewRevision(TRUE);
    }

    if ($entity->isNew() && $entity->isDefaultRevision()) {
      // If the created entity meta is the default revision by core standards,
      // we mark the custom field the same way. This is for cases in which it
      // doesn't have a host entity.
      $entity->set('emr_default_revision', TRUE);
    }

    $host_entity = $entity->getHostEntity();
    if (!$host_entity) {
      parent::save($entity);
      return;
    }

    if ($host_entity->isDefaultRevision()) {
      // If the host entity is the default revision, we indicate this meta to
      // be the same.
      $entity->set('emr_default_revision', TRUE);
    }
    else {
      // Otherwise, we mark it as non-default.
      $entity->set('emr_default_revision', FALSE);
    }

    parent::save($entity);
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity */
    parent::doPostSave($entity, $update);

    $default_revision = (bool) $entity->get('emr_default_revision')->value;
    // If the current entity being saved has been marked as the default
    // revision, we need to go through all its other revisions and mark them
    // as not default.
    if ($default_revision) {
      $current = $entity->getRevisionId();
      $revision_ids = $this->revisionIds($entity);
      foreach ($revision_ids as $id) {
        if ($id === $current || is_null($current)) {
          continue;
        }
        /** @var \Drupal\emr\Entity\EntityMetaInterface $revision */
        $revision = $this->loadRevision($id);
        $revision->set('emr_default_revision', FALSE);
        $revision->setNewRevision(FALSE);
        $revision->markToSkipRelations();
        $revision->setHostEntity(NULL);
        $revision->setForcedNoRevision(TRUE);
        $revision->save();
      }
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
    // There should only be one ID because there can only be one relation
    // between a content entity and a meta.
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition("{$entity_meta_relation_content_field}.target_id", $content_entity->id())
      ->condition("{$entity_meta_relation_meta_field}.target_id", $entity->id())
      ->execute();

    // If the entity is marked to be detached and it is not saving a new
    // revision.
    if ($entity->shouldDeleteRelations() && !empty($ids)) {
      $relation = $entity_meta_relation_storage->loadRevision(key($ids));
      $revision_ids = $entity_meta_relation_storage->revisionIds($relation);
      if (count($revision_ids) === 1) {
        $relation->delete();
        return;
      }

      // We need to delete existing relation revision.
      $revision_ids = $entity_meta_relation_storage->getQuery()
        ->condition('id', reset($ids))
        ->condition("{$entity_meta_relation_content_field}.target_revision_id", $content_entity->getRevisionId())
        ->condition("{$entity_meta_relation_meta_field}.target_id", $entity->id())
        ->allRevisions()
        ->execute();

      foreach ($revision_ids as $revision_id => $id) {
        $entity_meta_relation_storage->deleteRevision($revision_id);
      }

      return;
    }
    // Otherwise, if the entity is creating a new revision, we won't save
    // the relation.
    elseif ($entity->shouldSkipRelations()) {
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
   *
   * Drupal core does not allow the deletion of default revisions. But in some
   * cases, we need to delete a revision that is marked as default. So before
   * we can do that, we need to make the previous revision the default one to
   * allow the deletion.
   */
  public function deleteRevision($revision_id) {
    /** @var \Drupal\Core\Entity\RevisionableInterface $revision */
    $revision = $this->loadRevision($revision_id);
    if (!$revision instanceof EntityMetaInterface) {
      // It's possible that by the time this revision delete is requested, the
      // actual revision might have been deleted by the EntityReferenceRevision
      // field. So we don't want to do anything in this case.
      parent::deleteRevision($revision_id);
      return;
    }

    if (!$revision->isDefaultRevision()) {
      // If it's not the default revision we just defer to the parent to delete
      // it.
      parent::deleteRevision($revision_id);
      return;
    }

    // Check to see if there are more than 1 revisions of this entity. If there
    // is only one, delete the entire revision.
    $revision_ids = $this->revisionIds($revision);

    if (count($revision_ids) === 1) {
      parent::deleteRevision($revision_id);
      return;
    }

    // Mark the previous revision as the default and then defer to the parent
    // to perform the deletion.
    array_pop($revision_ids);
    $revision_id_to_default = end($revision_ids);
    /** @var \Drupal\emr\Entity\EntityMetaInterface $revision_to_default */
    $revision_to_default = $this->loadRevision($revision_id_to_default);
    $revision_to_default->isDefaultRevision(TRUE);
    $revision_to_default->setNewRevision(FALSE);
    $revision_to_default->markToSkipRelations();
    $revision_to_default->save();

    parent::deleteRevision($revision_id);
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
  public function deleteAllRelatedEntityMetaRelationRevisions(ContentEntityInterface $content_entity): void {
    $entity_type = $content_entity->getEntityType();

    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');

    // Find all the revisions of EntityMetaRelation that point to the current
    // revision of this entity.
    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition($entity_meta_relation_content_field . '.target_revision_id', $content_entity->getRevisionId())
      ->allRevisions()
      ->execute();

    if (!$ids) {
      return;
    }

    // Delete all the found revisions.
    foreach ($ids as $revision_id => $id) {
      $relation = $entity_meta_relation_storage->loadRevision($revision_id);
      // When deleting a revision that has an entity reference revision field
      // that points to another entity revision, the EntityReferenceRevision
      // field will attempt to delete the target entity revision as well. But
      // we don't want that so we need to update temporarily this value to
      // prevent it from doing so.
      $relation->setNewRevision(FALSE);
      $relation->set($entity_meta_relation_content_field, NULL);
      $relation->save();
      $entity_meta_relation_storage->deleteRevision($revision_id);
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
  public function getRelatedEntities(ContentEntityInterface $entity): array {
    $entity_type = $entity->getEntityType();
    // This is the field name that points to the passed entity.
    $relation_field_name = NULL;
    // This is the field name that points to entity related to the passed
    // entity. We will determine this using the relation in the loop below.
    $reverse_relation_field_name = NULL;
    $target_field = 'target_revision_id';
    $target_id = $entity->getRevisionId();

    if ($entity instanceof EntityMetaInterface) {
      // @todo get this dynamically from the available entity meta relation
      // bundles.
      $relation_field_name = 'emr_meta_revision';
    }
    else {
      // Any host entity.
      $relation_field_name = $entity_type->get('entity_meta_relation_content_field');
    }

    // Load all the relation revisions that point to the passed entity.
    /** @var \Drupal\emr\EntityMetaRelationStorageInterface $entity_meta_relation_storage */
    $entity_meta_relation_storage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $ids = $entity_meta_relation_storage->getQuery()
      ->condition($relation_field_name . '.' . $target_field, $target_id)
      ->allRevisions()
      ->execute();

    if (!$ids) {
      return [];
    }

    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface[] $entity_meta_relations */
    $entity_meta_relation_revisions = $entity_meta_relation_storage->loadMultipleRevisions(array_keys($ids));

    $related_entities = [];

    /** @var \Drupal\emr\Entity\EntityMetaRelationInterface $relation */
    foreach ($entity_meta_relation_revisions as $relation) {
      if ($entity instanceof EntityMetaInterface) {
        $reverse_relation_field_name = $entity_meta_relation_storage->getRelationFieldName($relation, EntityMetaRelationStorageInterface::RELATION_FIELD_TARGET_CONTENT);
      }
      else {
        $reverse_relation_field_name = $entity_type->get('entity_meta_relation_meta_field');
      }

      // Avoid loading revisions of wrong bundle.
      if (!$relation->hasField($reverse_relation_field_name)) {
        continue;
      }

      $target_revision_id = $relation->get($reverse_relation_field_name)->target_revision_id;
      $target_type = $relation->get($reverse_relation_field_name)->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type');
      $storage = $entity instanceof EntityMetaInterface ? $this->entityTypeManager->getStorage($target_type) : $this;
      $related_entity = $storage->loadRevision($target_revision_id);
      if (!$related_entity instanceof ContentEntityInterface) {
        continue;
      }

      $id = $related_entity->getEntityTypeId() . ':' . $related_entity->id();
      $related_entities[$id] = $related_entity;
    }

    return $related_entities;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   */
  public function shouldMakeRevision(EntityMetaInterface $entity): bool {
    if ($entity->isForcedNoRevision()) {
      return FALSE;
    }

    if ($entity->isNewRevision()) {
      return TRUE;
    }

    if ($entity->isNew()) {
      return TRUE;
    }

    // Host entity is keeping the revision, we will follow by not making a new
    // revision either.
    if (!empty($entity->getHostEntity()) && ($entity->getHostEntity()->getLoadedRevisionId() == $entity->getHostEntity()->getRevisionId())) {
      return FALSE;
    }

    if ($entity->isHostEntityReverting()) {
      // We don't want to make a new revision of the meta if the host entity
      // is reverting.
      return FALSE;
    }

    // When determining if there are field changes, we try to compare the
    // current field values with the original ones. These can be set on the
    // entity elsewhere or, if not, we load the latest entity revision and
    // compare to that.
    $change_fields = $this->getChangeFields($entity);
    $original = $entity->_original instanceof EntityMetaInterface ? $entity->_original : NULL;

    if (!$original) {
      // In case there are revisions, load the latest revision to compare
      // against.
      $original_id = $this->getLatestRevisionId($entity->id());
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
      $original = $this->loadRevision($original_id);
    }

    foreach ($change_fields as $field) {
      // Only save a new revision if important fields changed.
      // If we encounter a change, we save a new revision.
      if (!empty($original) && $entity->get($field)->hasAffectingChanges($original->get($field)->filterEmptyItems(), $entity->language()->getId())) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultEntityMetas(ContentEntityInterface $entity): array {
    $default_metas = [];

    // If the host entity was marked not to preset defaults on its meta, we
    // don't return any entity metas.
    if (isset($entity->entity_meta_no_default) && $entity->entity_meta_no_default === TRUE) {
      return $default_metas;
    }

    $plugins = $this->pluginManager->getDefinitions();
    foreach ($plugins as $id => $definition) {
      if (!isset($definition['attach_by_default']) || $definition['attach_by_default'] === FALSE) {
        continue;
      }

      /** @var \Drupal\emr\Plugin\EntityMetaRelationPluginInterface $plugin */
      $plugin = $this->pluginManager->createInstance($id);
      if ($plugin->applies($entity)) {
        $default_metas[$definition['entity_meta_bundle']] = $this->create([
          'bundle' => $definition['entity_meta_bundle'],
          'emr_host_entity' => $entity
        ]);
      }
    }

    return $default_metas;
  }

  /**
   * {@inheritdoc}
   *
   * Overriding the method to build the query starting from the revisions
   * table so that we can mimic the core "default revision" logic by checking
   * a field value on the revision table.
   */
  protected function buildQuery($ids, $revision_ids = FALSE) {
    // Use the revision table as the base table in the query.
    $query = $this->database->select($this->revisionTable, 'revision');

    $query->addTag($this->entityTypeId . '_load_multiple');

    if ($revision_ids) {
      if (!is_array($revision_ids)) {
        // phpcs:ignore
        @trigger_error('Passing a single revision ID to "\Drupal\Core\Entity\Sql\SqlContentEntityStorage::buildQuery()" is deprecated in drupal:8.5.x and will be removed before drupal:9.0.0. An array of revision IDs should be given instead. See https://www.drupal.org/node/2924915', E_USER_DEPRECATED);
      }

      $query->condition("revision.{$this->revisionKey}", $revision_ids, 'IN');
    }
    else {
      // If we are not querying for particular revision IDs, join on the
      // revision data table and include only the default revisions by using the
      // "emr_default_revision" field.
      $query->join($this->revisionDataTable, 'revision_data', "revision.{$this->revisionKey} = revision_data.{$this->revisionKey} AND revision_data.emr_default_revision = 1");
    }

    // Join back into the main entity table.
    $query->join($this->baseTable, 'base', "revision.{$this->idKey} = base.{$this->idKey}");

    // Add fields from the {entity} table.
    $table_mapping = $this->getTableMapping();
    $entity_fields = $table_mapping->getAllColumns($this->baseTable);

    if ($this->revisionTable) {
      // Add all fields from the {entity_revision} table.
      $entity_revision_fields = $table_mapping->getAllColumns($this->revisionTable);
      $entity_revision_fields = array_combine($entity_revision_fields, $entity_revision_fields);
      // The ID field is provided by entity, so remove it.
      unset($entity_revision_fields[$this->idKey]);

      // Remove all fields from the base table that are also fields by the same
      // name in the revision table.
      $entity_field_keys = array_flip($entity_fields);
      foreach ($entity_revision_fields as $name) {
        if (isset($entity_field_keys[$name])) {
          unset($entity_fields[$entity_field_keys[$name]]);
        }
      }
      $query->fields('revision', $entity_revision_fields);

      // Compare revision ID of the base and revision table, if equal then this
      // is the default revision.
      $query->addExpression('CASE base.' . $this->revisionKey . ' WHEN revision.' . $this->revisionKey . ' THEN 1 ELSE 0 END', 'isDefaultRevision');
    }

    $query->fields('base', $entity_fields);

    if ($ids) {
      $query->condition("base.{$this->idKey}", $ids, 'IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   *
   * Overriding the method to allow the mapping of the storage records from the
   * revision tables since the storage queries are now relying on the revisions
   * tables primarily.
   */
  protected function getFromStorage(array $ids = NULL) {
    $entities = [];

    if (!empty($ids)) {
      // Sanitize IDs. Before feeding ID array into buildQuery, check whether
      // it is empty as this would load all entities.
      $ids = $this->cleanIds($ids);
    }

    if ($ids === NULL || $ids) {
      // Build and execute the query.
      $query_result = $this->buildQuery($ids)->execute();
      $records = $query_result->fetchAllAssoc($this->idKey);

      if (!$records) {
        return $entities;
      }

      // Map the loaded records into entity objects and according fields. But
      // first, key the array on the revision ID so we ensure we retrieve the
      // values from the correct table.
      $revision_records = [];
      foreach ($records as $record) {
        $revision_records[$record->{$this->revisionKey}] = $record;
      }
      $objects = $this->mapFromStorageRecords($revision_records, TRUE);
      if (!$objects) {
        return $entities;
      }

      // If we have built entity objects, key them back as IDs.
      foreach ($objects as $entity) {
        $entities[$entity->id()] = $entity;
      }
    }

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'emr.entity_meta.query.sql';
  }

}
