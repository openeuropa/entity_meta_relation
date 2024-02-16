<?php

declare(strict_types=1);

namespace Drupal\emr;

use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Wrapper entity meta entities.
 */
class EntityMetaWrapper implements EntityMetaWrapperInterface {

  /**
   * The entity meta.
   *
   * @var \Drupal\emr\Entity\EntityMetaInterface
   */
  protected $entityMeta;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityMetaInterface $entity_meta) {
    $this->entityMeta = $entity_meta;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityMeta(): EntityMetaInterface {
    return $this->entityMeta;
  }

}
