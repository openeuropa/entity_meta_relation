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
   * Gets/sets EMR fields to be checked.
   *
   * These represent the fields that are visible on content form to be checked.
   *
   * @param array|null $fields
   *   The fields to check.
   *
   * @return mixed
   *   Returns the fields to check if.
   */
  public function emrFieldsToCheck(array $fields = NULL);

  /**
   * Gets/sets EMR wrapped items.
   *
   * These will be used to save information before and after entity meta
   * get saved.
   *
   * @param string $key
   *   The key for the wrapped item.
   * @param array|null $values
   *   The values for the wrapped item.
   *
   * @return mixed
   *   Returns the wrapped item for the key if present.
   */
  public function emrWrappedItem(string $key, array $values = NULL);

  /**
   * Gets/sets host entity in the entity meta.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface|null $contentEntity
   *   The content entity.
   *
   * @return mixed
   *   Returns the host entity if present.
   */
  public function emrHostEntity(ContentEntityInterface $contentEntity = NULL);

}
