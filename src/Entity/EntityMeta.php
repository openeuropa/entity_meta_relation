<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
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
 *   revision_table = "entity_meta_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer entity meta types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "bundle",
 *     "label" = "id",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
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
   * Fields that need to be checked for changes or emptyness.
   *
   * @var array
   */
  protected $emrFieldsToCheck;

  /**
   * The wrapped items to be handled by entity meta logic.
   *
   * @var array
   */
  protected $emrWrappedItems;

  /**
   * The emr host entityt that this entity meta is related to.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $emrHostEntity;

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->set('status', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable() {
    $this->set('status', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function emrFieldsToCheck(array $fields = NULL) {
    if (!is_null($fields)) {
      $this->emrFieldsToCheck = $fields;
    }

    return $this->emrFieldsToCheck();
  }

  /**
   * {@inheritdoc}
   */
  public function emrWrappedItem(string $key, array $values = NULL) {
    if (!is_null($values)) {
      $this->emrWrappedItems[$key] = $values;
    }

    return $this->emrWrappedItems[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function emrHostEntity(ContentEntityInterface $contentEntity = NULL) {
    if (!empty($contentEntity)) {
      $this->emrHostEntity = $contentEntity;
    }

    return $this->emrHostEntity;
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

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // If the entity is being saved through the content entity form,
    // we save a new relationship to the host entity.
    if (!empty($emrHostEntity = $this->emrHostEntity())) {
      \Drupal::service('emr.manager')->createEntityMetaRelation($emrHostEntity->entity_meta_relation_bundle, $emrHostEntity, $this);
    }
    // Otherwise we need to copy previous relations if entity is not new.
    elseif ($update) {
      \Drupal::service('emr.manager')->copyEntityMetaRelations($this, 'emr_meta_revision');
    }

    parent::postSave($storage, $update);
  }

}
