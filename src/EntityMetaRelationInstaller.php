<?php

declare(strict_types=1);

namespace Drupal\emr;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldException;

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

    $allowed_bundles = [
      $entity_meta_relation_content_field => $bundles,
      $entity_meta_relation_meta_field => [$entity_meta_type],
    ];

    foreach ($allowed_bundles as $field_name => $target_bundles) {
      $this->updateTargetBundlesInField($entity_meta_relation_bundle, $field_name, $target_bundles);
    }

    // Sets correct 3rd party settings.
    $bundle_entity_storage = $this->entityTypeManager->getStorage($definition->getBundleEntityType());
    foreach ($bundles as $bundle_id) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityBundleBase $bundle */
      $bundle = $bundle_entity_storage->load($bundle_id);
      $entity_meta_bundles = $bundle->getThirdPartySetting('emr', 'entity_meta_bundles');
      if (empty($entity_meta_bundles) || !in_array($entity_meta_type, $entity_meta_bundles)) {
        $entity_meta_bundles[] = $entity_meta_type;
        $bundle->setThirdPartySetting('emr', 'entity_meta_bundles', $entity_meta_bundles);
        $bundle->save();
      }
    }

  }

  /**
   * Update target bundles in requested field configs.
   *
   * @param string $emr_bundle
   *   The entity meta relation bundle.
   * @param string $field_name
   *   The field name.
   * @param array $target_bundles
   *   The list of allowed bundles.
   */
  protected function updateTargetBundlesInField(string $emr_bundle, string $field_name, array $target_bundles): void {
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = $this->entityTypeManager->getStorage('field_config')->load("entity_meta_relation.{$emr_bundle}.{$field_name}");
    if (!$field_config) {
      throw new FieldException("Field config 'entity_meta_relation.{$emr_bundle}.{$field_name}' not found. Without this field, we cannot properly configure Entity Meta type.");
    }
    $handler_settings = $field_config->getSetting('handler_settings');
    $old_target_bundles = $handler_settings['target_bundles'] ?? [];
    $handler_settings['target_bundles'] = array_unique(array_merge($old_target_bundles, $target_bundles));
    $field_config->setSetting('handler_settings', $handler_settings);
    $field_config->save();
  }

}
