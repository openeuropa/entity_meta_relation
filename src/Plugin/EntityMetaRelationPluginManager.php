<?php

namespace Drupal\emr\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * EntityMetaRelation plugin manager.
 */
class EntityMetaRelationPluginManager extends DefaultPluginManager {

  /**
   * Constructs an EntityMetaRelationPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/EntityMetaRelation',
      $namespaces,
      $module_handler,
      'Drupal\emr\Plugin\EntityMetaRelationPluginInterface',
      'Drupal\emr\Annotation\EntityMetaRelation'
    );
    $this->alterInfo('entity_meta_relation_info');
    $this->setCacheBackend($cache_backend, 'entity_meta_relation_plugins');
  }

  /**
   * Returns the definition that has defaults for a given bundle.
   *
   * This is the plugin definition that sets values on the corresponding entity
   * by default.
   *
   * @param string $bundle
   *   The bundle.
   *
   * @return array|null
   *   The definition or null if none exists.
   */
  public function getDefaultDefinitionForBundle(string $bundle): ?array {
    $definitions = $this->getDefinitions();

    foreach ($definitions as $id => $definition) {
      if (isset($definition['entity_meta_bundle']) && $definition['entity_meta_bundle'] !== $bundle) {
        continue;
      }

      if (isset($definition['attach_by_default']) && $definition['attach_by_default'] !== TRUE) {
        continue;
      }

      // There can only be one definition that works with a given bundle.
      return $definition;
    }

    return NULL;
  }

}
