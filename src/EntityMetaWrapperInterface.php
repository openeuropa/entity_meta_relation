<?php

declare(strict_types=1);

namespace Drupal\emr;

use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Represents entity meta wrappers.
 */
interface EntityMetaWrapperInterface {

  /**
   * Returns the entity meta.
   *
   * @return \Drupal\emr\Entity\EntityMetaInterface
   *   The entity meta.
   */
  public function getEntityMeta(): EntityMetaInterface;

}
