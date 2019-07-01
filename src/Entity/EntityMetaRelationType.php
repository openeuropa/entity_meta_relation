<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Entity Meta Relation type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "emr_type",
 *   label = @Translation("Entity Meta Relation type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\emr\Form\EntityMetaRelationTypeForm",
 *       "edit" = "Drupal\emr\Form\EntityMetaRelationTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\emr\EntityMetaRelationTypeListBuilder",
 *   },
 *   admin_permission = "administer entity meta relation types",
 *   bundle_of = "emr",
 *   config_prefix = "emr_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/emr_types/add",
 *     "edit-form" = "/admin/structure/emr_types/manage/{emr_type}",
 *     "delete-form" = "/admin/structure/emr_types/manage/{emr_type}/delete",
 *     "collection" = "/admin/structure/emr_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class EntityMetaRelationType extends ConfigEntityBundleBase {

  /**
   * The machine name of this entity meta relation type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the entity meta relation type.
   *
   * @var string
   */
  protected $label;

}
