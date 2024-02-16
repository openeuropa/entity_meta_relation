<?php

declare(strict_types=1);

namespace Drupal\emr\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the entity meta relation entity class.
 *
 * @ContentEntityType(
 *   id = "entity_meta_relation",
 *   label = @Translation("Entity Meta Relation"),
 *   label_collection = @Translation("Entity Meta Relations"),
 *   bundle_label = @Translation("Entity Meta Relation type"),
 *   handlers = {
 *     "list_builder" = "Drupal\emr\EntityMetaRelationListBuilder",
 *     "storage" = "Drupal\emr\EntityMetaRelationStorage",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\emr\Form\EntityMetaRelationForm",
 *       "edit" = "Drupal\emr\Form\EntityMetaRelationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_meta_relation",
 *   revision_table = "entity_meta_relation_revision",
 *   show_revision_ui = TRUE,
 *   admin_permission = "administer entity meta relation types",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "bundle" = "bundle",
 *     "label" = "id",
 *     "uuid" = "uuid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "add-form" = "/admin/content/entity-meta-relation/add/{entity_meta_relation_type}",
 *     "add-page" = "/admin/content/entity-meta-relation/add",
 *     "canonical" = "/entity_meta_relation/{entity_meta_relation}",
 *     "edit-form" = "/admin/content/entity-meta-relation/{entity_meta_relation}/edit",
 *     "delete-form" = "/admin/content/entity-meta-relation/{entity_meta_relation}/delete",
 *     "collection" = "/admin/content/entity-meta-relation"
 *   },
 *   bundle_entity_type = "entity_meta_relation_type",
 *   field_ui_base_route = "entity.entity_meta_relation_type.edit_form"
 * )
 */
class EntityMetaRelation extends RevisionableContentEntityBase implements EntityMetaRelationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(): EntityMetaRelationInterface {
    $this->set('status', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(): EntityMetaRelationInterface {
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
  public function setCreatedTime($timestamp): EntityMetaRelationInterface {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Status'))
      ->setDescription(t('A boolean indicating whether the entity meta relation is enabled.'))
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
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the entity meta relation was created.'))
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
      ->setTranslatable(TRUE)
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity meta relation was last edited.'));

    return $fields;
  }

}
