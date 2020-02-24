<?php

declare(strict_types = 1);

namespace Drupal\entity_meta_speed\Plugin\EntityMetaRelation;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\emr\Plugin\EntityMetaRelationContentFormPluginBase;

/**
 * Plugin implementation of the entity_meta_relation.
 *
 * @EntityMetaRelation(
 *   id = "speed",
 *   label = @Translation("Speed"),
 *   entity_meta_bundle = "speed",
 *   content_form = TRUE,
 *   description = @Translation("Speed.")
 * )
 */
class SpeedConfiguration extends EntityMetaRelationContentFormPluginBase {

  use StringTranslationTrait;

  /**
   * Builds entity meta from the values of the $form_state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\emr\Entity\EntityMetaInterface|null
   *   The entity meta.
   */
  protected function buildEntity(FormStateInterface $form_state): ?EntityMetaInterface {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->getFormObject()->getEntity();

    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $this->entityTypeManager->getStorage('entity_meta');

    $entity_meta_entities = $entity_meta_storage->getBundledRelatedMetaEntities($entity);
    $pluginDefinition = $this->getPluginDefinition();
    $entity_meta_bundle = $pluginDefinition['entity_meta_bundle'];

    if (empty($entity_meta_entities[$entity_meta_bundle])) {
      $entity_meta = $entity_meta_storage->create([
        'bundle' => $entity_meta_bundle,
        'status' => $entity->isPublished(),
      ]);
    }
    else {
      $entity_meta = $entity_meta_entities[$entity_meta_bundle][0];
    }

    $entity_meta->set('field_gear', $form_state->getValue('gear'));
    $entity_meta->setHostEntity($entity);
    $entity_meta->isDefaultRevision($entity->isDefaultRevision());
    return $entity_meta;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $form, FormStateInterface $form_state, ContentEntityInterface $entity): array {
    /** @var \Drupal\emr\EntityMetaStorageInterface $entity_meta_storage */
    $entity_meta_storage = $this->entityTypeManager->getStorage('entity_meta');

    $entity_meta_bundle = $this->getPluginDefinition()['entity_meta_bundle'];
    $entity_meta_entities = $entity_meta_storage->getBundledRelatedMetaEntities($entity);

    // Get possible values.
    $field_definitions = $this->entityFieldManager->getFieldStorageDefinitions('entity_meta');
    $options = options_allowed_values($field_definitions['field_gear'], $entity);

    // Add none option.
    $options = array_merge(['' => $this->t('None')], $options);
    $key = $this->getFormKey();
    $this->buildFormContainer($form, $form_state, $key);
    $form[$key]['referenced_meta']['gear'] = [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $entity_meta_entities[$entity_meta_bundle][0]->field_gear->value ?? '',
      '#title' => $this->t('Gear'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, FormStateInterface $form_state): void {
    $entity_meta = $this->buildEntity($form_state);
    if ($entity_meta === NULL) {
      return;
    }

    $host_entity = $form_state->getFormObject()->getEntity();

    $host_entity->get('emr_entity_metas')->attach($entity_meta);
  }

}
