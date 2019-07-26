<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Content entity form handler interface.
 *
 * Implemented by entity handlers responsible for injecting and handling the
 * entity meta relation plugin form elements in cotent entity forms.
 */
interface ContentFormHandlerInterface extends EntityHandlerInterface {

  /**
   * Adds the form elements.
   *
   * @param array $form
   *   The form being altered.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The form array.
   */
  public function addFormElements(array $form, FormStateInterface $form_state): array;

  /**
   * Submits the embedded form elements.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitFormElements(array &$form, FormStateInterface $form_state): void;

  /**
   * Entity form builder to add the entity meta relations to the node.
   *
   * @param string $entity_type
   *   The Entity type.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function entityBuilder(string $entity_type, ContentEntityInterface $entity, array &$form, FormStateInterface $form_state);

}
