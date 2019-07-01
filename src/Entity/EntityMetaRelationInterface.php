<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an entity meta relation entity.
 */
interface EntityMetaRelationInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the entity meta relation creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity meta relation.
   */
  public function getCreatedTime();

  /**
   * Sets the entity meta relation creation timestamp.
   *
   * @param int $timestamp
   *   The entity meta relation creation timestamp.
   *
   * @return \Drupal\emr\Entity\EntityMetaRelationInterface
   *   The called entity meta relation entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the entity meta relation status.
   *
   * @return bool
   *   TRUE if the entity meta relation is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the entity meta relation status to TRUE.
   *
   * @return \Drupal\emr\Entity\EntityMetaRelationInterface
   *   The called entity meta relation entity.
   */
  public function enable();

  /**
   * Sets the entity meta relation status to FALSE.
   *
   * @return \Drupal\emr\Entity\EntityMetaRelationInterface
   *   The called entity meta relation entity.
   */
  public function disable();

}
