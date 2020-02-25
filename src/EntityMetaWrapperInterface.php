<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Wrapper entity meta entities.
 */
interface EntityMetaWrapperInterface {

  /**
   * EntityMetaWrapperInterface constructor.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity_meta
   *   The entity meta.
   */
  public function __construct(EntityMetaInterface $entity_meta);

}
