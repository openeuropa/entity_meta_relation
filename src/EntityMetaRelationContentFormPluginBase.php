<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for entity_meta_relation plugins.
 */
abstract class EntityMetaRelationContentFormPluginBase extends EntityMetaRelationPluginBase implements EntityMetaRelationPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Builds the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\EntityInterface $contentEntity
   *   The content entity.
   * @param array $entity_meta_relations
   *   The meta relations.
   *
   * @return array
   *   The related meta entities keyed by bundle.
   */
  public function build(array $form, FormStateInterface $form_state, EntityInterface $contentEntity, array $entity_meta_relations) {
    // Plugin is not applicable, just return original form.
    if (!$this->isApplicable($contentEntity)) {
      return $form;
    }

    $pluginDefinition = $this->getPluginDefinition();
    $entityMetaBundle = $pluginDefinition['entity_meta_bundle'];

    $key = 'emr_plugins_' . $this->getPluginId();

    $emrFormKeys = $form['emr_form_keys']['#value'] ?? [];
    $emrFormKeys[] = $key;

    $form[$key] = [
      '#type' => 'details',
      '#title' => $this->label(),
      '#group' => 'advanced',
      '#open' => TRUE,
    ];

    $form[$key]['referenced_meta'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'edit-meta-reference'],
    ];

    $form[$key]['referenced_meta'][$this->getPluginId()] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'entity_meta',
      '#bundle' => $pluginDefinition['entity_meta_bundle'],
      '#save_entity' => TRUE,
      '#entity_meta_relation_bundle' => $pluginDefinition['entity_meta_relation_bundle'],
      '#form_mode' => 'default',
      '#default_value' => $entity_meta_relations[$entityMetaBundle][0] ?? NULL,
    ];

    $form['emr_form_keys'] = [
      '#type' => 'value',
      '#value' => $emrFormKeys,
    ];

    return $form;
  }

}
