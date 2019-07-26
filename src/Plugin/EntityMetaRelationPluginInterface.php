<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for Task plugin plugins.
 */
interface EntityMetaRelationPluginInterface extends PluginInspectionInterface {

  /**
   * Checks if the plugin is applicable to the passed content entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return bool
   *   Whether the plugin is applicable.
   */
  public function applies(ContentEntityInterface $entity): bool;

}
