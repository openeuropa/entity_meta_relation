<?php

declare(strict_types=1);

namespace Drupal\entity_meta_audio;

use Drupal\emr\EntityMetaWrapper;

/**
 * Wrapper for entity meta entities with bundle "audio".
 */
class AudioEntityMetaWrapper extends EntityMetaWrapper {

  /**
   * Gets the volume.
   *
   * @return string
   *   The volume.
   */
  public function getVolume(): ?string {
    return $this->getEntityMeta()->get('field_volume')->value;
  }

  /**
   * Sets the volume.
   *
   * @param string $volume
   *   The volume.
   */
  public function setVolume(string $volume): void {
    $this->getEntityMeta()->set('field_volume', $volume);
  }

}
