<?php

namespace Drupal\entity_meta_relation;

use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for entity_meta_relation plugins.
 */
abstract class EntityMetaRelationPluginBase extends PluginBase implements EntityMetaRelationInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

}
