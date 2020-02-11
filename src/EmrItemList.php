<?php

namespace Drupal\emr;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Item list for a computed field that stores related entity metas.
 */
class EmrItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    $entity = $this->getEntity();

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::service('entity_type.manager');
    $entity_metas = $entity_type_manager->getStorage('entity_meta')->getRelatedMetaEntities($entity);

    $i = 0;
    foreach ($entity_metas as $entity_meta_id => $entity_meta) {
      $this->list[$i] = $this->createItem($i, $entity_meta);
      $i++;
    }
  }

}
