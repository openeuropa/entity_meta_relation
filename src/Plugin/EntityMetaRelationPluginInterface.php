<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Defines an interface for Entity meta relation plugins.
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

  /**
   * Fill entity meta with default values.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity_meta
   *   The entity meta.
   */
  public function fillDefaultEntityMetaValues(EntityMetaInterface $entity_meta): void;

}
