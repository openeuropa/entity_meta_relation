<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Interface for factory class EntityMetaWrapper entities.
 */
interface EntityMetaWrapperFactoryInterface {

  /**
   * Create a new entity meta wrapper.
   *
   * Instantiate a new entity meta wrapper or a more specific class in case it
   * finds a plugin mapping an entity meta wrapper class to this bundle.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity_meta
   *   The entity meta.
   *
   * @return \Drupal\emr\EntityMetaWrapper
   *   The entity meta wrapper.
   */
  public function create(EntityMetaInterface $entity_meta);

}
