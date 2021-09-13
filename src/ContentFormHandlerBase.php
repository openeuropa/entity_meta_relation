<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\emr\Plugin\EntityMetaRelationContentFormPluginInterface;
use Drupal\emr\Plugin\EntityMetaRelationPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form handler for content entity forms.
 */
class ContentFormHandlerBase implements ContentFormHandlerInterface {

  use DependencySerializationTrait;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The plugin manager.
   *
   * @var \Drupal\emr\Plugin\EntityMetaRelationPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs the EntityMetaContentFormHandlerBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   * @param \Drupal\emr\Plugin\EntityMetaRelationPluginManager $pluginManager
   *   The entity meta relation plugin manager.
   */
  public function __construct(EntityTypeInterface $entityType, EntityMetaRelationPluginManager $pluginManager) {
    $this->entityType = $entityType;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entityType) {
    return new static(
      $entityType,
      $container->get('plugin.manager.emr')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function addFormElements(array $form, FormStateInterface $form_state): array {
    $entity = $form_state->getFormObject()->getEntity();
    $entity_meta_forms_added = FALSE;

    // Adds form elements for each plugin that wants to add form elements to the
    // entity form.
    $plugins = $this->pluginManager->getDefinitions();
    foreach ($plugins as $id => $definition) {
      if (!isset($definition['content_form']) || !$definition['content_form']) {
        continue;
      }

      /** @var \Drupal\emr\Plugin\EntityMetaRelationPluginInterface $plugin */
      $plugin = $this->pluginManager->createInstance($id);
      if ($plugin instanceof EntityMetaRelationContentFormPluginInterface && $plugin->applies($entity)) {
        $form = $plugin->build($form, $form_state, $entity);
        $entity_meta_forms_added = TRUE;
      }
    }

    if ($entity_meta_forms_added) {
      // Add submissions of entity meta before entity is saved.
      array_unshift($form['actions']['submit']['#submit'], [
        $this,
        'submitFormElements',
      ]);
      array_unshift($form['#validate'], [
        $this,
        'validateFormElements',
      ]);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateFormElements(array &$form, FormStateInterface $form_state): void {
    $plugins = $this->getApplicablePlugins($form, $form_state);
    foreach ($plugins as $plugin) {
      $plugin->validate($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormElements(array &$form, FormStateInterface $form_state): void {
    $plugins = $this->getApplicablePlugins($form, $form_state);
    foreach ($plugins as $plugin) {
      $plugin->submit($form, $form_state);
    }
  }

  /**
   * Returns the plugins that are applicable for this handler.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\emr\Plugin\EntityMetaRelationContentFormPluginInterface[]
   *   The applicable plugins that can be validated and submitted.
   */
  protected function getApplicablePlugins(array &$form, FormStateInterface $form_state): array {
    $applicable = [];
    $entity = $form_state->getFormObject()->getEntity();

    $plugins = $this->pluginManager->getDefinitions();
    foreach ($plugins as $id => $definition) {
      if (!isset($definition['content_form']) || !$definition['content_form']) {
        continue;
      }

      /** @var \Drupal\emr\Plugin\EntityMetaRelationPluginInterface $plugin */
      $plugin = $this->pluginManager->createInstance($id);
      if ($plugin instanceof EntityMetaRelationContentFormPluginInterface && $plugin->applies($entity)) {
        $applicable[] = $plugin;
      }
    }

    return $applicable;
  }

}
