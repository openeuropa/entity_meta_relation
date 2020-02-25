<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for EntityMetaRelation plugins.
 */
abstract class EntityMetaRelationPluginBase extends PluginBase implements EntityMetaRelationPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity meta storage.
   *
   * @var \Drupal\emr\EntityMetaStorageInterface
   */
  protected $entityMetaStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityMetaStorage = $this->entityTypeManager->getStorage('entity_meta');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    $entity_type = $entity->getEntityType();
    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $entity_type->get('entity_meta_relation_meta_field');
    if (!$entity_meta_relation_content_field) {
      return FALSE;
    }

    // Gets fields defined for the defined bundle.
    $fields = $this->entityFieldManager->getFieldDefinitions('entity_meta_relation', $entity_type->get('entity_meta_relation_bundle'));
    if (!isset($fields[$entity_meta_relation_content_field])) {
      return FALSE;
    }

    /** @var \Drupal\Core\Field\FieldConfigInterface $content_field_definition */
    $meta_field_definition = $fields[$entity_meta_relation_meta_field];
    $target_meta_bundles = $meta_field_definition->getSetting('handler_settings')['target_bundles'];
    // If the associated entity meta bundle used by the plugin is not available
    // in the relationship, the plugin does not apply.
    if (empty($this->pluginDefinition['entity_meta_bundle']) || !in_array($this->pluginDefinition['entity_meta_bundle'], $target_meta_bundles)) {
      return FALSE;
    }

    /** @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
    $content_field_definition = $fields[$entity_meta_relation_content_field];
    $target_content_bundles = $content_field_definition->getSetting('handler_settings')['target_bundles'];
    // If current content bundle is not available in the relationship,
    // the plugin does not apply.
    if (!empty($target_content_bundles) && !in_array($entity->bundle(), $target_content_bundles)) {
      return FALSE;
    }

    return TRUE;
  }

}
