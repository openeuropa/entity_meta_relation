<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Core\Entity\ContentEntityInterface;
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
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The related meta entities keyed by bundle.
   */
  public function build(array $form, FormStateInterface $form_state, ContentEntityInterface $entity): array;

  /**
   * Submits the form before the main form has been submitted.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function preSubmit(array $form, FormStateInterface $form_state): void;

  /**
   * Submits the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submit(array $form, FormStateInterface $form_state): void;

}
