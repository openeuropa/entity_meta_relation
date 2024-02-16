<?php

declare(strict_types=1);

namespace Drupal\emr\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed field for the emr default revision field.
 */
class DefaultRevisionFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if (!empty($this->list)) {
      return;
    }

    // We only have 1 value.
    $delta = 0;
    $entity = $this->getEntity();

    if ($entity->isNew()) {
      // Nothing to compute on a new entity.
      return;
    }

    $id = $entity->id();
    $revision_id = $entity->getRevisionId();

    $result = \Drupal::database()->select('entity_meta_default_revision')
      ->fields('entity_meta_default_revision', ['default_revision_id'])
      ->condition('entity_meta_id', $id)
      ->execute()
      ->fetchField(0);

    $default = $result ? (int) $result === (int) $revision_id : FALSE;
    $this->list[$delta] = $this->createItem($delta, $default);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    parent::postSave($update);

    if (empty($this->list)) {
      return;
    }

    // We only have 1 value.
    $delta = 0;
    $value = $this->list[$delta]->value;
    if (is_null($value)) {
      // If there is no value, we don't know what to save, so we do nothing.
      return;
    }

    $entity = $this->getEntity();
    $id = $entity->id();
    $revision_id = $entity->getRevisionId();
    if ($value === TRUE) {
      // If it was marked as default, we save the current revision in the table
      // if it has alredy not been marked as such.
      if ((int) $revision_id !== $this->getDefaultRevisionId((int) $id)) {
        \Drupal::database()->upsert('entity_meta_default_revision')
          ->key('entity_meta_id')
          ->fields([
            'entity_meta_id' => $id,
            'default_revision_id' => $revision_id,
          ])
          ->execute();

        return;
      }
    }
    elseif ((int) $revision_id === (int) $this->getDefaultRevisionId((int) $id)) {
      // Otherwise, we delete the record as it's no longer the default.
      \Drupal::database()->delete('entity_meta_default_revision')
        ->condition('entity_meta_id', $id)
        ->execute();
    }
  }

  /**
   * Gets the default revision ID for a given entity meta.
   *
   * @param int $id
   *   The entity meta ID.
   *
   * @return int|null
   *   The revision ID if one exists.
   */
  protected function getDefaultRevisionId(int $id): ?int {
    $result = \Drupal::database()->select('entity_meta_default_revision')
      ->fields('entity_meta_default_revision', ['default_revision_id'])
      ->condition('entity_meta_id', $id)
      ->execute()
      ->fetchField(0);

    return $result ? (int) $result : NULL;
  }

}
