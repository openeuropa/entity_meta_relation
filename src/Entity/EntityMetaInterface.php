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
   * Sets the entity meta status to TRUE.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta relation entity.
   */
  public function enable();

  /**
   * Sets the entity meta status to FALSE.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta relation entity.
   */
  public function disable();

  /**
   * Sets the "host" entity.
   *
   * The "host" entity is the content entity which relates to this EntityMeta.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta relation entity.
   */
  public function setHostEntity(ContentEntityInterface $entity);

  /**
   * Gets the "host" entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Returns the host entity if present.
   */
  public function getHostEntity();

}
