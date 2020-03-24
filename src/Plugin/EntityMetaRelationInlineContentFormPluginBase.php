<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for plugins altering content forms that use IEF for entity meta.
 */
abstract class EntityMetaRelationInlineContentFormPluginBase extends EntityMetaRelationContentFormPluginBase implements EntityMetaRelationPluginInterface, EntityMetaRelationContentFormPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build(array $form, FormStateInterface $form_state, ContentEntityInterface $entity): array {
    $key = $this->getFormKey();
    $this->buildFormContainer($form, $form_state, $key);
    $plugin_definition = $this->getPluginDefinition();
    $entity_meta = $entity->get('emr_entity_metas')->getEntityMeta($plugin_definition['entity_meta_bundle']);

    $form[$key]['referenced_meta'][$this->getPluginId()] = [
      '#type' => 'inline_entity_form',
      '#entity_type' => 'entity_meta',
      '#bundle' => $plugin_definition['entity_meta_bundle'],
      '#save_entity' => TRUE,
      '#form_mode' => 'default',
      '#default_value' => $entity_meta ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state): void {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $host_entity */
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
    $host_entity->get('emr_entity_metas')->attach($entity);
  }

}
