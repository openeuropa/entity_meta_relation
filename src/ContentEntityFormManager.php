<?php

namespace Drupal\emr;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the emr to alter edit forms in content entities.
 */
class ContentEntityFormManager {

  /**
   * Get defined plugins for this bundle.
   */
  protected function getPlugins() {

  }

  /**
   * Add form elements.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Drupal\Core\Entity\EntityInterface|null $contentEntity
   *   The content entity.
   *
   * @return array
   *   Form elements to be added.
   */
  public function addFormElements(array $form, FormStateInterface $form_state, EntityInterface $contentEntity = NULL) {

    $entity_meta_relations = [];

    $form['meta_entities'] = [
      '#type' => 'details',
      '#title' => t('Visual configuration'),
      '#group' => 'advanced',
      '#open' => TRUE,
    ];

    $form['meta_entities']['referenced_meta'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'edit-meta-reference'],
    ];

    $form['meta_entities']['referenced_meta']['ief'] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'entity_meta',
      '#bundle' => 'visual',
      '#save_entity' => TRUE,
      '#form_mode' => 'default',
      // If the #default_value is NULL, a new entity will be created.
      '#default_value' => NULL,
    ];

    $form['meta_entities']['referenced_meta']['referenced_meta_revision_ids'] = [
      '#type' => 'value',
      '#value' => $entity_meta_relations,
    ];

    return $form;
  }

}
