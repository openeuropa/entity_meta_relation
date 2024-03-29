<?php

declare(strict_types=1);

namespace Drupal\emr\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Base class for plugins that need to be embedded in content forms.
 */
abstract class EntityMetaRelationContentFormPluginBase extends EntityMetaRelationPluginBase implements EntityMetaRelationPluginInterface, EntityMetaRelationContentFormPluginInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'emr_plugins_' . $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, FormStateInterface $form_state): void {
    // No validation by default.
  }

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
  }

}
