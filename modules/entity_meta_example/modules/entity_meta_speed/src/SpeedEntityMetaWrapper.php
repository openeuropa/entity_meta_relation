<?php

declare(strict_types=1);

namespace Drupal\entity_meta_speed;

use Drupal\emr\EntityMetaWrapper;

/**
 * Wrapper for entity meta entities with bundle "speed".
 */
class SpeedEntityMetaWrapper extends EntityMetaWrapper {

  /**
   * Gets the gear.
   *
   * @return string
   *   The gear.
   */
  public function getGear(): ?string {
    return $this->getEntityMeta()->get('field_gear')->value;
  }

  /**
   * Sets the gear.
   *
   * @param string $gear
   *   The gear.
   */
  public function setGear(string $gear): void {
    $this->getEntityMeta()->set('field_gear', $gear);
  }

}
