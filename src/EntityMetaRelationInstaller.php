<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Configures entity meta relations .
 */
class EntityMetaRelationInstaller {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * EntityMetaRelationInstaller constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    $this->configFactory = $configFactory;
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
  }

  /**
   * Install en entity meta type on a content entity type / bundles.
   *
   * @param string $entity_meta_type
   *   The entity meta type.
   * @param string $entity_type
   *   The entity type.
   * @param array $bundles
   *   The bundles to install it on.
   */
  public function installEntityMetaTypeOnContentEntityType(string $entity_meta_type, string $entity_type, array $bundles = []): void {
    $definition = $this->entityTypeManager->getDefinition($entity_type);
    $entity_meta_relation_bundle = $definition->get('entity_meta_relation_bundle');
    $entity_meta_relation_content_field = $definition->get('entity_meta_relation_content_field');
    $entity_meta_relation_meta_field = $definition->get('entity_meta_relation_meta_field');
    if (!$entity_meta_relation_bundle || !$entity_meta_relation_content_field || !$entity_meta_relation_meta_field) {
      return;
    }

    if (!$bundles) {
      $bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo($definition->id()));
    }

    $field_name = "entity_meta_relation.{$entity_meta_relation_bundle}.{$entity_meta_relation_content_field}";
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = $this->entityTypeManager->getStorage('field_config')->load($field_name);
    $handler_settings = $field_config->getSetting('handler_settings');
    $handler_settings['target_bundles'] = array_unique(array_merge($handler_settings['target_bundles'], $bundles));
    $field_config->setSetting('handler_settings', $handler_settings);
    $field_config->save();

    $field_name = "entity_meta_relation.{$entity_meta_relation_bundle}.{$entity_meta_relation_meta_field}";
    $field_config = $this->entityTypeManager->getStorage('field_config')->load($field_name);
    $handler_settings = $field_config->getSetting('handler_settings');
    $handler_settings['target_bundles'] = array_unique(array_merge($handler_settings['target_bundles'], [$entity_meta_type]));
    $field_config->setSetting('handler_settings', $handler_settings);
    $field_config->save();
  }

}
