<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the emr to alter edit forms in content entities.
 */
class ContentEntityFormManager implements ContentEntityFormManagerInterface {

  /**
   * The entity meta relation manager service.
   *
   * @var \Drupal\emr\EntityMetaRelationManagerInterface
   */
  protected $emrManager;

  /**
   * The plugin manager.
   *
   * @var \Drupal\emr\EntityMetaRelationPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs the ContentEntityFormManager.
   *
   * @param \Drupal\emr\EntityMetaRelationManagerInterface $entityMetaRelationManager
   *   The entity meta relation manager service.
   * @param \Drupal\emr\EntityMetaRelationPluginManager $pluginManager
   *   The entity meta relation plugin manager.
   */
  public function __construct(EntityMetaRelationManagerInterface $entityMetaRelationManager, EntityMetaRelationPluginManager $pluginManager) {
    $this->emrManager = $entityMetaRelationManager;
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public function addFormElements(array $form, FormStateInterface $form_state, EntityInterface $contentEntity = NULL): array {

    // Loads current relations.
    $entity_meta_relations = $this->emrManager->loadBundledEntityMetaRelations($contentEntity);

    // Loops through all plugins and builds the forms for appropriated ones.
    $plugins = $this->pluginManager->getDefinitions();
    foreach ($plugins as $plugin) {
      $pluginInstance = $this->pluginManager->createInstance($plugin['id'], []);
      if ($pluginInstance instanceof EntityMetaRelationContentFormPluginInterface) {
        $form = $pluginInstance->build($form, $form_state, $contentEntity, $entity_meta_relations);
      }
    }

    $form['actions']['submit']['#submit'][] = [get_class($this), 'submitFormElements'];
    $form['#entity_builders'][] = [get_class($this), 'entityBuilder'];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function submitFormElements(array &$form, FormStateInterface $form_state): void {
    $emr_form_keys = $form_state->getValue('emr_form_keys');
    if (empty($emr_form_keys)) {
      return;
    }

    $pluginManager = \Drupal::service('plugin.manager.emr');
    $plugins = $pluginManager->getDefinitions();
    foreach ($plugins as $plugin) {
      $pluginInstance = $pluginManager->createInstance($plugin['id'], []);
      if ($pluginInstance instanceof EntityMetaRelationContentFormPluginInterface) {
        $form = $pluginInstance->submit($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function entityBuilder(string $entity_type, ContentEntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Don't copy previous relations if node is edited through the content form.
    $entity->emr_no_copy = TRUE;
  }

}
