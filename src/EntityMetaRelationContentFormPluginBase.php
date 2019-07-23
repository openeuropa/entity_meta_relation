<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\Element\InlineEntityForm;

/**
 * Base class for entity_meta_relation plugins.
 */
abstract class EntityMetaRelationContentFormPluginBase extends EntityMetaRelationPluginBase implements EntityMetaRelationPluginInterface, EntityMetaRelationContentFormPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Builds the form container for the plugin.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $key
   *   The key to use for the container.
   */
  protected function buildFormContainer(array &$form, FormStateInterface $form_state, string $key) {
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

    $form['emr_form_keys'] = [
      '#type' => 'value',
      '#value' => $emrFormKeys,
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'emr_plugins_' . $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $form, FormStateInterface $form_state, EntityInterface $contentEntity, array $entity_meta_relations): array {
    // Plugin is not applicable, just return original form.
    if (!$this->isApplicable($contentEntity)) {
      return $form;
    }

    $pluginDefinition = $this->getPluginDefinition();
    $entityMetaBundle = $pluginDefinition['entity_meta_bundle'];

    $key = $this->getFormKey();

    $this->buildFormContainer($form, $form_state, $key);

    $form[$key]['referenced_meta'][$this->getPluginId()] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'entity_meta',
      '#bundle' => $pluginDefinition['entity_meta_bundle'],
      '#save_entity' => TRUE,
      '#entity_meta_relation_bundle' => $pluginDefinition['entity_meta_relation_bundle'],
      '#form_mode' => 'default',
      '#default_value' => $entity_meta_relations[$entityMetaBundle][0] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state): void {
    $key = $this->getFormKey();
    $emr_children = Element::children($form[$key]['referenced_meta']);

    foreach ($emr_children as $emr_element_key) {
      $entity_form = $form[$key]['referenced_meta'][$emr_element_key];

      // Only submit inline entity forms.
      if ($entity_form['#type'] == 'inline_entity_form' && $entity_form['#save_entity']) {
        $inline_form_handler = InlineEntityForm::getInlineFormHandler($entity_form['#entity_type']);
        $inline_form_handler->entityFormSubmit($entity_form, $form_state);

        // Fields to be considered.
        $entity_form['#entity']->setEmrFieldsToCheck($form_state->get($entity_form['#ief_id']));

        // Saves host entity in a property to handle the relationships.
        $emrHostEntity = $form_state->getFormObject()->getEntity();
        $emrHostEntity->entity_meta_relation_bundle = $entity_form['#entity_meta_relation_bundle'];

        // Copy status from $emrHostEntity.
        $emrHostEntity->isPublished() ? $entity_form['#entity']->enable() : $entity_form['#entity']->disable();
        $entity_form['#entity']->setEmrHostEntity($emrHostEntity);

        try {
          $inline_form_handler->save($entity_form['#entity']);
        }
        catch (EntityStorageException $exception) {
          // Don't do anything if entity meta is empty.
        }
      }
    }
  }

}
