<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for EMR Content Form Plugins.
 */
interface EntityMetaRelationContentFormPluginInterface {

  /**
   * Generates form key to be used by this plugin.
   *
   * @return string
   *   The key
   */
  public function getFormKey(): string;

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
  public function build(array $form, FormStateInterface $form_state, EntityInterface $contentEntity, array $entity_meta_relations): array;

  /**
   * Builds the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submit(array $form, FormStateInterface $form_state): void;

}
