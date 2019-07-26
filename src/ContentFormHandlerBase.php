<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\emr\Plugin\EntityMetaRelationContentFormPluginInterface;
use Drupal\emr\Plugin\EntityMetaRelationPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form handler for content entity forms.
 */
class ContentFormHandlerBase implements ContentFormHandlerInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\emr\Plugin\EntityMetaRelationPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs the EntityMetaContentFormHandlerBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\emr\Plugin\EntityMetaRelationPluginManager $pluginManager
   *   The entity meta relation plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityMetaRelationPluginManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
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
      $form['actions']['submit']['#submit'][] = [$this, 'submitFormElements'];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormElements(array &$form, FormStateInterface $form_state): void {
    $entity = $form_state->getFormObject()->getEntity();

    $plugins = $this->pluginManager->getDefinitions();
    foreach ($plugins as $id => $definition) {
      if (!isset($definition['content_form']) || !$definition['content_form']) {
        continue;
      }

      /** @var \Drupal\emr\Plugin\EntityMetaRelationPluginInterface $plugin */
      $plugin = $this->pluginManager->createInstance($id);
      if ($plugin instanceof EntityMetaRelationContentFormPluginInterface && $plugin->applies($entity)) {
        $form = $plugin->submit($form, $form_state);
      }
    }
  }

}
