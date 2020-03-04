<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\emr\EntityMetaWrapperInterface;

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
  public function getCreatedTime(): int;

  /**
   * Sets the entity meta creation timestamp.
   *
   * @param int $timestamp
   *   The entity meta creation timestamp.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta entity.
   */
  public function setCreatedTime($timestamp): EntityMetaInterface;

  /**
   * Returns the entity meta status.
   *
   * @return bool
   *   TRUE if the entity meta is enabled, FALSE otherwise.
   */
  public function isEnabled(): bool;

  /**
   * Sets the entity meta status to TRUE.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta relation entity.
   */
  public function enable(): EntityMetaInterface;

  /**
   * Sets the entity meta status to FALSE.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The called entity meta relation entity.
   */
  public function disable(): EntityMetaInterface;

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
  public function setHostEntity(ContentEntityInterface $entity): EntityMetaInterface;

  /**
   * Gets the "host" entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   Returns the host entity if present.
   */
  public function getHostEntity(): ?ContentEntityInterface;

  /**
   * Mark this entity to skip create relations when being saved.
   */
  public function markToSkipRelations(): void;

  /**
   * Mark this entity to delete relations to current revision when being saved.
   */
  public function markToDeleteRelations(): void;

  /**
   * Should relations to current revision be deleted.
   *
   * @return bool
   *   Returns true in case current relations should be deleted when saving.
   */
  public function shouldDeleteRelations(): bool;

  /**
   * Should relations to current revision be skipped when saving.
   *
   * @return bool
   *   Returns true in case current relations should be skipped when saving.
   */
  public function shouldSkipRelations(): bool;

  /**
   * Gets the wrapper for this entity meta.
   *
   * @return \Drupal\emr\EntityMetaWrapperInterface
   *   The entity meta wrapper.
   */
  public function getWrapper(): EntityMetaWrapperInterface;

  /**
   * Sets the wrapper for this entity meta.
   *
   * @param \Drupal\emr\EntityMetaWrapperInterface $entityMetaWrapper
   *   The entity meta wrapper.
   */
  public function setWrapper(EntityMetaWrapperInterface $entityMetaWrapper): void;

  /**
   * Checks if the host entity is reverting.
   *
   * @return bool
   *   Whether it's being reverted.
   */
  public function isHostEntityIsReverting(): bool;

  /**
   * Sets whether the host entity is reverting.
   *
   * @param bool $hostEntityIsReverting
   *   Whether it's being reverted.
   */
  public function setHostEntityIsReverting(bool $hostEntityIsReverting): void;

}
