<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Content entity form handler interface.
 *
 * Implemented by entity handlers responsible for injecting and handling the
 * entity meta relation plugin form elements in content entity forms.
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
   * Submits the embedded form elements before the main form is submitted.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function preSubmitFormElements(array &$form, FormStateInterface $form_state): void;

  /**
   * Submits the embedded form elements after the main form is submitted.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function submitFormElements(array &$form, FormStateInterface $form_state): void;

}
