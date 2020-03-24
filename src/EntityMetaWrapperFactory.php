<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\emr\Plugin\EntityMetaRelationPluginManager;

/**
 * Factory class EntityMetaWrapper entities.
 */
class EntityMetaWrapperFactory implements EntityMetaWrapperFactoryInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\emr\Plugin\EntityMetaRelationPluginManager
   */
  protected $pluginManager;

  /**
   * EntityMetaWrapperFactory constructor.
   *
   * @param \Drupal\emr\Plugin\EntityMetaRelationPluginManager $pluginManager
   *   The plugin manager.
   */
  public function __construct(EntityMetaRelationPluginManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function create(EntityMetaInterface $entity_meta) {
    $plugins = $this->pluginManager->getDefinitions();

    // Try to find a plugin with a wrapper that applies to this bundle.
    foreach ($plugins as $id => $definition) {
      if (!empty($definition['entity_meta_bundle']) && $definition['entity_meta_bundle'] == $entity_meta->bundle() && !empty($definition['entity_meta_wrapper_class'])) {
        $wrapper_class = $definition['entity_meta_wrapper_class'];
        break;
      }
    }

    if (empty($wrapper_class)) {
      return new EntityMetaWrapper($entity_meta);
    }
    else {
      return new $wrapper_class($entity_meta);
    }
  }

}
