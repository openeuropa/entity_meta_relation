<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\Element\InlineEntityForm;

/**
 * Form controller for the emr to alter edit forms in content entities.
 */
class ContentEntityFormManager {

  /**
   * The entity relation manager.
   *
   * @var \Drupal\emr\EntityMetaRelationManager
   */
  protected $emrManager;

  /**
   * The plugin manager.
   *
   * @var \Drupal\emr\EntityMetaRelationPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\emr\EntityMetaRelationManager $entity_meta_relation_manager
   *   The EntityMetaRelationManager.
   * @param \Drupal\emr\EntityMetaRelationPluginManager $pluginManager
   *   The plugin manager.
   */
  public function __construct(EntityMetaRelationManager $entity_meta_relation_manager, EntityMetaRelationPluginManager $pluginManager) {
    $this->emrManager = $entity_meta_relation_manager;
    $this->pluginManager = $pluginManager;
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
  public function addFormElements(array $form, FormStateInterface $form_state, EntityInterface $contentEntity = NULL): array {

    // Loads current relations.
    $entity_meta_relations = $this->emrManager->loadEntityMetaRelations($contentEntity);

    // Loops through all plugins and builds the forms for appropriated ones.
    $plugins = $this->pluginManager->getDefinitions();
    foreach ($plugins as $plugin) {
      $pluginInstance = $this->pluginManager->createInstance($plugin['id'], []);
      $form = $pluginInstance->build($form, $form_state, $contentEntity, $entity_meta_relations);
    }

    return $form;
  }

  /**
   * Submits all emr elemnts to the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitFormElements(array &$form, FormStateInterface $form_state): void {
    $emr_form_keys = $form_state->getValue('emr_form_keys');
    if (empty($emr_form_keys)) {
      return;
    }

    // Loop through all submitted plugins.
    foreach ($emr_form_keys as $form_key) {
      // For each plugin loop through all inline entity forms.
      $emr_children = Element::children($form[$form_key]['referenced_meta']);

      foreach ($emr_children as $emr_element_key) {
        $entity_form = $form[$form_key]['referenced_meta'][$emr_element_key];

        // Only submit inline entity forms.
        if ($entity_form['#type'] == 'inline_entity_form') {
          $inline_form_handler = InlineEntityForm::getInlineFormHandler($entity_form['#entity_type']);
          $inline_form_handler->entityFormSubmit($entity_form, $form_state);
          if ($entity_form['#save_entity']) {
            $entity_form['#entity']->emr_host_entity = $form_state->getFormObject()->getEntity();
            $entity_form['#entity']->emr_host_entity->entity_meta_relation_bundle = $entity_form['#entity_meta_relation_bundle'];
            $inline_form_handler->save($entity_form['#entity']);
          }
          $entity = $form_state->getFormObject()->getEntity();
          $entity->entity_meta_relations = $form_state->getValue('referenced_meta_revision_ids');
        }
      }
    }
  }

}
