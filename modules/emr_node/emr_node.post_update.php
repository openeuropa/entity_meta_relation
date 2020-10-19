<?php

/**
 * @file
 * Entity Meta Relation Node post update file.
 */

declare(strict_types = 1);

/**
 * Fix misconfigured handler_settings.
 */
function emr_node_post_update_00001(): void {
  $entity_type_manager = \Drupal::entityTypeManager();
  $bundles = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
  foreach (['emr_meta_revision', 'emr_node_revision'] as $field_name) {
    /** @var \Drupal\Core\Field\FieldConfigInterface $field_config */
    $field_config = $entity_type_manager->getStorage('field_config')->load('entity_meta_relation.node_meta_relation.' . $field_name);
    if (!$field_config) {
      continue;
    }
    $handler_settings = !empty($field_config->getSetting('handler_settings')) ? $field_config->getSetting('handler_settings') : [];
    if (!empty($handler_settings['target_bundles'])) {
      foreach ($handler_settings['target_bundles'] as $key => $entity_bundle) {
        $entity_type = $field_config->getFieldStorageDefinition()->getSetting('target_type');
        if (empty($bundles[$entity_type][$entity_bundle])) {
          unset($handler_settings['target_bundles'][$key]);
        }
      }
      if ($handler_settings['target_bundles'] === []) {
        unset($handler_settings['target_bundles']);
      }
      $handler_settings['auto_create_bundle'] = '';
      $field_config->setSetting('handler_settings', $handler_settings);
      $field_config->save();
    }
  }
}
