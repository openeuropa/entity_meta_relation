<?php

declare(strict_types=1);

namespace Drupal\emr\Plugin\Field\FieldType;

/**
 * Defines the 'emr_item_host' entity field type.
 *
 * @FieldType(
 *   id = "emr_item_host",
 *   label = @Translation("Entity meta relation item for host entity"),
 *   description = @Translation("Relates to host entity."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\emr\Field\ComputedHostEntityItemList",
 * )
 */
class EntityMetaRelationHostEntityMetaItem extends BaseEntityMetaRelationItem {

}
