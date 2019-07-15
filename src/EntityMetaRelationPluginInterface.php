<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Task plugin plugins.
 */
interface EntityMetaRelationPluginInterface extends PluginInspectionInterface {

  /**
   * Checks if the plugin is applicable to the passed content entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $content_entity
   *   The content entity.
   *
   * @return bool
   *   Return applicability of the plugin.
   */
  protected function isApplicable(EntityInterface $content_entity): bool;

}
