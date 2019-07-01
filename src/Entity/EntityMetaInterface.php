<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining an entity meta entity.
 */
interface EntityMetaInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the entity meta creation timestamp.
   *
   * @return int
   *   Creation timestamp of the entity meta.
   */
  public function getCreatedTime();

  /**
   * Sets the entity meta creation timestamp.
   *
   * @param int $timestamp
   *   The entity meta creation timestamp.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the entity meta status.
   *
   * @return bool
   *   TRUE if the entity meta is enabled, FALSE otherwise.
   */
  public function isEnabled();

  /**
   * Sets the entity meta status.
   *
   * @param bool $status
   *   TRUE to enable this entity meta , FALSE to disable.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta entity.
   */
  public function setStatus($status);

}
