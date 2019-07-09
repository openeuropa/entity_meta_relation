<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Handles relationship logic between content and meta entities.
 */
class EntityMetaRelationManager {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates new entity meta relation.
   *
   * @param string $bundle
   *   The bundle to create.
   * @param \Drupal\Core\Entity\EntityInterface $content_entity
   *   The content entity.
   * @param \Drupal\Core\Entity\EntityInterface $meta_entity
   *   The meta emtity.
   */
  public function createNewMetaRelation(string $bundle, EntityInterface $content_entity, EntityInterface $meta_entity) {

    $metaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $metaRelationStorage->create([
      'bundle' => $bundle,
      'emr_meta_revision' => $meta_entity,
      'emr_node_revision' => $content_entity,
    ])->save();

  }

  /**
   * Loads the associated meta entities with this content entity.
   *
   * @param Drupal\Core\Entity\EntityInterface $content_entity
   *   The content_entity.
   *
   * @return array
   *   The list of meta entities related with this content revision.
   */
  public function loadEntityMetaRelations(EntityInterface $content_entity): array {
    $relations = [];
    $metaRelationStorage = $this->entityTypeManager->getStorage('entity_meta_relation');
    $metaRelationsRevisionIds = $metaRelationStorage->getQuery()->condition('emr_node_revision.target_revision_id', $content_entity->getRevisionId())->execute();
    $metaRelations = $metaRelationStorage->loadMultiple($metaRelationsRevisionIds);

    if (!empty($metaRelations)) {
      foreach ($metaRelations as $relation) {
        $metaRevision = $relation->get('emr_meta_revision')->value;
      }
    }

    return $relations;
  }

}
