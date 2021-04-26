<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\emr\EntityMetaWrapperInterface;
use Drupal\emr\Field\DefaultRevisionFieldItemList;

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
 *     "status" = "status"
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
   * The entity meta wrapper.
   *
   * @var \Drupal\emr\EntityMetaWrapper
   */
  protected $entityMetaWrapper;

  /**
   * The entity meta should not create new relationships to its host entity.
   *
   * @var bool
   */
  protected $skipRelations = FALSE;

  /**
   * The entity meta will delete existing relationships to its host entity.
   *
   * @var bool
   */
  protected $deleteRelations = FALSE;

  /**
   * Whether the host entity is in the process of a revert.
   *
   * This flag is set by the ComputedEntityMetasItemList whenever the host
   * entity is saved without having any metas in the list but by having the
   * loaded revision different than the current one, indicating a revert or
   * indicating that the entity meta being added to the list is not in fact
   * changing so a new revision should not be made.
   *
   * @var bool
   */
  protected $hostEntityIsReverting = FALSE;

  /**
   * Forces to not create a new revision of the entity.
   *
   * @var bool
   *   TRUE to not create a new revision, FALSE to allow it based on other
   *   factors.
   */
  protected $forcedNoRevision = FALSE;

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
    $this->set('emr_host_entity', $entity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostEntity(): ?ContentEntityInterface {
    $host_entity = $this->get('emr_host_entity');

    if (!$host_entity->isEmpty()) {
      return $host_entity->first()->entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getWrapper(): EntityMetaWrapperInterface {
    return $this->entityMetaWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function setWrapper(EntityMetaWrapperInterface $entityMetaWrapper): void {
    $this->entityMetaWrapper = $entityMetaWrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function markToSkipRelations(): void {
    $this->skipRelations = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function markToDeleteRelations(): void {
    $this->deleteRelations = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldDeleteRelations(): bool {
    return $this->deleteRelations;
  }

  /**
   * {@inheritdoc}
   */
  public function shouldSkipRelations(): bool {
    return $this->skipRelations;
  }

  /**
   * {@inheritdoc}
   */
  public function isHostEntityReverting(): bool {
    return $this->hostEntityIsReverting;
  }

  /**
   * {@inheritdoc}
   */
  public function setHostEntityIsReverting(bool $hostEntityIsReverting): void {
    $this->hostEntityIsReverting = $hostEntityIsReverting;
  }

  /**
   * {@inheritdoc}
   */
  public function isForcedNoRevision(): bool {
    return $this->forcedNoRevision;
  }

  /**
   * {@inheritdoc}
   */
  public function setForcedNoRevision(bool $force): void {
    $this->forcedNoRevision = $force;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    if ($this->isNew() || isset($this->original)) {
      return;
    }

    // If no original is set, set itself. This is needed because an entity meta
    // entity is possible to not be found in a load even if it does exist, due
    // to it missing a default revision. In this case, the loadUnchanged() won't
    // find it and core expects this original key there.
    $this->original = $this;
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

    // Add a computed field to reference the host entity.
    $fields['emr_host_entity'] = BaseFieldDefinition::create('emr_item_host')
      ->setName('Emr host name')
      ->setLabel(t('Emr host name'))
      ->setComputed(TRUE)
      ->setDisplayConfigurable('view', FALSE);

    // Marker that the entity meta maps to the default revision of its host
    // entity. We are not using the core default revision because it's possible
    // that a given meta entity should have absolutely no default revisions due
    // to its host detaching the meta on its own default revision. So a strict
    // one to one mapping between the host default revision and the meta
    // default revision is not possible. This field is a computed one that keeps
    // track of which is the default revision in another table.
    $fields['emr_default_revision'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default revision'))
      ->setDescription(t('A boolean indicating whether the entity meta revision maps to the default revision of the host entity.'))
      ->setComputed(TRUE)
      ->setClass(DefaultRevisionFieldItemList::class);

    return $fields;
  }

}
