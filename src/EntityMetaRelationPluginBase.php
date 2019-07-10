<?php

declare(strict_types = 1);

namespace Drupal\emr;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for entity_meta_relation plugins.
 */
abstract class EntityMetaRelationPluginBase extends PluginBase implements EntityMetaRelationPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
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
   * Checks if the plugin is applicable to the passed content entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $content_entity
   *   The content entity.
   *
   * @return bool
   *   Return applicability of the plugin.
   */
  protected function isApplicable(EntityInterface $content_entity): bool {
    $content_bundle = $content_entity->bundle();

    // Gets fields defined for the defined bundle.
    $fields = $this->entityFieldManager->getFieldDefinitions('entity_meta_relation', $this->pluginDefinition['bundle']);

    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition */
    foreach ($fields as $field_name => $field_definition) {
      if ($field_definition->getType() == 'entity_reference_revisions' && $field_definition->getSetting('target_type') == 'node') {
        $target_bundles = $field_definition->getSetting('handler_settings')['target_bundles'];

        // Checks if the current content entity bundle
        // is referenceable by emr bundle used by the plugin.
        if (in_array($content_bundle, $target_bundles)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

}
