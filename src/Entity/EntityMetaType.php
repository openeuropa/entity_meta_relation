<?php

declare(strict_types = 1);

namespace Drupal\entity_meta_relation\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Entity meta type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "entity_meta_type",
 *   label = @Translation("Entity meta type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\entity_meta_relation\Form\EntityMetaTypeForm",
 *       "edit" = "Drupal\entity_meta_relation\Form\EntityMetaTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\Core\Config\Entity\ConfigEntityListBuilder",
 *   },
 *   admin_permission = "administer entity meta types",
 *   bundle_of = "entity_meta",
 *   config_prefix = "entity_meta_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/entity_meta_types/add",
 *     "edit-form" = "/admin/structure/entity_meta_types/manage/{entity_meta_type}",
 *     "delete-form" = "/admin/structure/entity_meta_types/manage/{entity_meta_type}/delete",
 *     "collection" = "/admin/structure/entity_meta_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class EntityMetaType extends ConfigEntityBundleBase {

  /**
   * The machine name of this entity meta type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the entity meta type.
   *
   * @var string
   */
  protected $label;

}
