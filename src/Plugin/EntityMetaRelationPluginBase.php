<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for EntityMetaRelation plugins.
 */
abstract class EntityMetaRelationPluginBase extends PluginBase implements EntityMetaRelationPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey(): string {
    return 'emr_plugins_' . $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function applies(ContentEntityInterface $entity): bool {
    $entity_type = $entity->getEntityType();
    $entity_meta_relation_content_field = $entity_type->get('entity_meta_relation_content_field');
    if (!$entity_meta_relation_content_field) {
      return FALSE;
    }

    // Gets fields defined for the defined bundle.
    $fields = $this->entityFieldManager->getFieldDefinitions('entity_meta_relation', $entity_type->get('entity_meta_relation_bundle'));
    if (!isset($fields[$entity_meta_relation_content_field])) {
      return FALSE;
    }

    /** @var \Drupal\Core\Field\FieldConfigInterface $field_definition */
    $field_definition = $fields[$entity_meta_relation_content_field];
    $target_bundles = $field_definition->getSetting('handler_settings')['target_bundles'];

    if (in_array($entity->bundle(), $target_bundles)) {
      return TRUE;
    }

    return FALSE;
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
