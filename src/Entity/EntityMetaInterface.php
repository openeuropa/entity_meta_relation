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
   * Sets EMR fields to be checked.
   *
   * These represent the fields that are visible on content form to be checked.
   *
   * @param array|null $fields
   *   The fields to check.
   */
  public function setEmrFieldsToCheck(array $fields);

  /**
   * Gets EMR fields to be checked.
   *
   * These represent the fields that are visible on content form to be checked.
   *
   * @return array|null
   *   The fields to check.
   */
  public function getEmrFieldsToCheck();

  /**
   * Sets host entity in the entity meta.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $contentEntity
   *   The content entity.
   *
   * @return mixed
   *   Returns the host entity if present.
   */
  public function setEmrHostEntity(ContentEntityInterface $contentEntity);

  /**
   * Gets host entity in the entity meta.
   *
   * @return mixed
   *   Returns the host entity if present.
   */
  public function getEmrHostEntity();

}
