<?php

declare(strict_types = 1);

namespace Drupal\entity_meta_force;

use Drupal\emr\EntityMetaWrapper;

/**
 * Wrapper for entity meta entities with bundle "force".
 */
class ForceEntityMetaWrapper extends EntityMetaWrapper {

  /**
   * Gets the volume.
   *
   * @return string
   *   The gravity.
   */
  public function getGravity(): ?string {
    return $this->getEntityMeta()->get('field_gravity')->value;
  }

  /**
   * Sets the gravity.
   *
   * @param string $gravity
   *   The gravity.
   */
  public function setGravity(string $gravity): void {
    $this->getEntityMeta()->set('field_gravity', $gravity);
  }

}
