<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for services altering edit forms in content entities.
 */
interface ContentEntityFormManagerInterface {

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
   *   The form array.
   */
  public function addFormElements(array $form, FormStateInterface $form_state, EntityInterface $contentEntity = NULL): array;

  /**
   * Submits all emr elemnts to the form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function submitFormElements(array &$form, FormStateInterface $form_state): void;

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
