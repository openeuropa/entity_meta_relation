<?php

/**
 * @file
 * Entity Meta Relation post update file.
 */

/**
 * Intentionally left blank to assure its presence.
 */
function emr_post_update_00001(&$sandbox) {

}

/**
 * Update the entity meta default values.
 */
function emr_post_update_00002(&$sandbox) {
  \Drupal::service('plugin.manager.field.field_type')->clearCachedDefinitions();

  /** @var \Drupal\emr\EntityMetaStorageInterface $storage */
  $storage = \Drupal::entityTypeManager()->getStorage('entity_meta');
  $meta_ids = $storage->getQuery()->allRevisions()->execute();
  $grouped = [];
  foreach ($meta_ids as $revision_id => $meta_id) {
    $grouped[$meta_id][] = $revision_id;
  }

  foreach ($grouped as $meta_id => $revision_ids) {
    foreach ($revision_ids as $revision_id) {
      /** @var \Drupal\emr\Entity\EntityMetaInterface $revision */
      $revision = $storage->loadRevision($revision_id);
      $revision->set('emr_default_revision', $revision->isDefaultRevision());
      $revision->setNewRevision(FALSE);
      $revision->setForcedNoRevision(TRUE);
      $revision->setHostEntity(NULL);
      $revision->markToSkipRelations();
      $revision->save();
    }
  }
}

/**
 * Track the entity meta default values in the new table.
 */
function emr_post_update_00003(&$sandbox) {
  /** @var \Drupal\emr\EntityMetaStorageInterface $storage */
  $storage = \Drupal::entityTypeManager()->getStorage('entity_meta');
  if (!isset($sandbox['total'])) {
    $ids = $storage->getQuery()->allRevisions()->condition('emr_default_revision', 1)->execute();
    if (!$ids) {
      return t('No entity meta entities need to be updated.');
    }

    $sandbox['ids'] = array_keys($ids);
    $sandbox['total'] = count($ids);
    $sandbox['current'] = 0;
    $sandbox['items_per_batch'] = 10;
  }

  // Get a slice from the ids.
  $ids = array_slice($sandbox['ids'], $sandbox['current'], $sandbox['items_per_batch']);
  $revisions = $storage->loadMultipleRevisions($ids);
  foreach ($revisions as $revision) {
    \Drupal::database()->insert('entity_meta_default_revision')
      ->fields(['entity_meta_id' => $revision->id(), 'default_revision_id' => $revision->getRevisionId()])
      ->execute();

    $sandbox['current']++;
  }

  $sandbox['#finished'] = empty($sandbox['total']) ? 1 : ($sandbox['current'] / $sandbox['total']);

  if ($sandbox['#finished'] === 1) {
    return t('A total of @current entity meta revision defaults have been tracked.', ['@current' => $sandbox['current']]);
  }
}
