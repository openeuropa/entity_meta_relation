<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the entity meta entity class.
 *
 * @ContentEntityType(
 *   id = "entity_meta",
 *   label = @Translation("Entity meta"),
 *   label_collection = @Translation("Entity metas"),
 *   bundle_label = @Translation("Entity meta type"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "storage" = "Drupal\emr\EntityMetaStorage",
 *     "form" = {
 *       "add" = "Drupal\emr\Form\EntityMetaForm",
 *       "edit" = "Drupal\emr\Form\EntityMetaForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "list_builder" = "Drupal\emr\EntityMetaListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "entity_meta",
 *   data_table = "entity_meta_field_data",
 *   revision_table = "entity_meta_revision",
 *   revision_data_table = "entity_meta_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer entity meta types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "bundle",
 *     "label" = "id",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/entity-meta/add/{entity_meta_type}",
 *     "add-page" = "/admin/content/entity-meta/add",
 *     "canonical" = "/entity_meta/{entity_meta}",
 *     "edit-form" = "/admin/content/entity-meta/{entity_meta}/edit",
 *     "delete-form" = "/admin/content/entity-meta/{entity_meta}/delete",
 *     "collection" = "/admin/content/entity-meta"
 *   },
 *   bundle_entity_type = "entity_meta_type",
 *   field_ui_base_route = "entity.entity_meta_type.edit_form"
 * )
 */
class EntityMeta extends RevisionableContentEntityBase implements EntityMetaInterface {

  use EntityChangedTrait;

  /**
   * The "host" entity this entity meta relates to.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $hostEntity;

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(): EntityMetaInterface {
    $this->set('status', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(): EntityMetaInterface {
    $this->set('status', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime(): int {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp): EntityMetaInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostEntity(ContentEntityInterface $entity = NULL): EntityMetaInterface {
    $this->hostEntity = $entity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostEntity(): ?ContentEntityInterface {
    return $this->hostEntity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the entity meta is enabled.'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the entity meta was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity meta was last edited.'));

    return $fields;
  }

}
