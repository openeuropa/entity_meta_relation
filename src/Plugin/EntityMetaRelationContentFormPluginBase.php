<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangesDetectionTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;
use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\inline_entity_form\Element\InlineEntityForm;

/**
 * Base class for entity_meta_relation plugins.
 */
abstract class EntityMetaRelationContentFormPluginBase extends EntityMetaRelationPluginBase implements EntityMetaRelationPluginInterface, EntityMetaRelationContentFormPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build(array $form, FormStateInterface $form_state, ContentEntityInterface $entity): array {
    if (!$this->isApplicable($entity)) {
      // Plugin is not applicable, just return original form.
      return $form;
    }

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
    if (!$this->isApplicable($host_entity)) {
      return;
    }

    $key = $this->getFormKey();
    $entity_form = $form[$key]['referenced_meta'][$this->getPluginId()];

    if ($entity_form['#type'] !== 'inline_entity_form') {
      return;
    }

    /** @var \Drupal\inline_entity_form\InlineFormInterface $inline_form_handler */
    $inline_form_handler = $this->entityTypeManager->getHandler($entity_form['#entity_type'], 'inline_form');
    $inline_form_handler->entityFormSubmit($entity_form, $form_state);

    /** @var EntityMetaInterface $entity */
    $entity = $entity_form['#entity'];

    // Copy status from the host entity.
    $host_entity->isPublished() ? $entity->enable() : $entity->disable();
    $entity->setEmrHostEntity($host_entity);

    if (!$this->shouldSaveEntity($entity)) {
      return;
    }

    $inline_form_handler->save($entity);
    return;
  }

  /**
   * Checks whether the meta entity should be saved or not.
   *
   * @param \Drupal\emr\Entity\EntityMetaInterface $entity
   *
   * @return bool
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
