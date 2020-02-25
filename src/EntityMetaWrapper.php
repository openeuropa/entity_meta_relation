<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Wrapper entity meta entities.
 */
class EntityMetaWrapper implements EntityMetaWrapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityMetaInterface $entity_meta) {
    $this->entity_meta = $entity_meta;
  }

}
