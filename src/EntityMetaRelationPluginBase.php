<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Base class for entity_meta_relation plugins.
 */
abstract class EntityMetaRelationPluginBase extends PluginBase implements EntityMetaRelationPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  public function isApplicable(EntityInterface $content_entity) {
    // $this->pluginDefinition['bundle'])
    // $content_entity->bundle())
  }

}
