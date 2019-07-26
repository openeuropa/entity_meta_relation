<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Base class for plugins that need to be directly embedded in content forms.
 */
abstract class EntityMetaRelationContentFormPluginBase extends EntityMetaRelationPluginBase implements EntityMetaRelationPluginInterface, EntityMetaRelationContentFormPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build(array $form, FormStateInterface $form_state, ContentEntityInterface $entity): array {
    $key = $this->getFormKey();
    $this->buildFormContainer($form, $form_state, $key);

    $entity_meta_entities = $this->entityTypeManager->getStorage('entity_meta')->getBundledRelatedMetaEntities($entity);
    $pluginDefinition = $this->getPluginDefinition();
    $entity_meta_bundle = $pluginDefinition['entity_meta_bundle'];

    $form[$key]['referenced_meta'][$this->getPluginId()] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'entity_meta',
      '#bundle' => $pluginDefinition['entity_meta_bundle'],
      '#save_entity' => TRUE,
      '#form_mode' => 'default',
      '#default_value' => $entity_meta_entities[$entity_meta_bundle][0] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state): void {
    $host_entity = $form_state->getFormObject()->getEntity();

    $key = $this->getFormKey();
    $entity_form = $form[$key]['referenced_meta'][$this->getPluginId()];

    if ($entity_form['#type'] !== 'inline_entity_form') {
      return;
    }

    /** @var \Drupal\inline_entity_form\InlineFormInterface $inline_form_handler */
    $inline_form_handler = $this->entityTypeManager->getHandler($entity_form['#entity_type'], 'inline_form');
    $inline_form_handler->entityFormSubmit($entity_form, $form_state);

    /** @var \Drupal\emr\Entity\EntityMetaInterface $entity */
    $entity = $entity_form['#entity'];

    // Copy status from the host entity.
    $host_entity->isPublished() ? $entity->enable() : $entity->disable();
    $entity->setHostEntity($host_entity);

    if (!$this->shouldSaveEntity($entity)) {
      return;
    }

    $inline_form_handler->save($entity);
  }

  /**
   * Checks whether the meta entity should be saved or not.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *   The entity meta entity.
   *
   * @return bool
   *   Whether it should save or not.
   */
  protected function shouldSaveEntity(EntityMetaInterface $entity) {
    $change_fields = $this->entityTypeManager->getStorage('entity_meta')->getChangeFields($entity);
    foreach ($change_fields as $field) {
      if (!$entity->get($field)->isEmpty()) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
