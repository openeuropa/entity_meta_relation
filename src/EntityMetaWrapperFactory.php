<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Factory class EntityMetaWrapper entities.
 */
class EntityMetaWrapperFactory {

  /**
   * Create a new entity meta wrapper.
   *
   * Instantiate a new entity meta wrapper or a more specific class in case it
   * finds a plugin mapping an entity meta wrapper class to this bundle.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity_meta
   *   The entity meta.
   *
   * @return \Drupal\emr\EntityMetaWrapper
   *   The entity meta wrapper.
   */
  public static function create(EntityMetaInterface $entity_meta) {
    $plugin_manager = \Drupal::service('plugin.manager.emr');
    $plugins = $plugin_manager->getDefinitions();

    // Try to find a plugin with a wrapper that applies to this bundle.
    foreach ($plugins as $id => $definition) {
      if ($definition['entity_meta_bundle'] == $entity_meta->bundle() && !empty($definition['entity_meta_wrapper_class'])) {
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
