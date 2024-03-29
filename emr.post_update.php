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
  $meta_ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->allRevisions()
    ->execute();
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
