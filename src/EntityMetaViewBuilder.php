<?php

declare(strict_types = 1);

namespace Drupal\entity_meta_relation;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;

/**
 * Provides a view controller for an entity meta entity type.
 */
class EntityMetaViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    // The entity meta has no entity template itself.
    unset($build['#theme']);
    return $build;
  }

}
