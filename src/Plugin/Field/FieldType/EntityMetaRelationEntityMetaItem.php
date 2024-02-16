<?php

declare(strict_types=1);

namespace Drupal\emr\Plugin\Field\FieldType;

/**
 * Defines the 'emr_item' entity field type.
 *
 * @FieldType(
 *   id = "emr_item_entity_metas",
 *   label = @Translation("Entity meta relation item"),
 *   description = @Translation("Relates to entity meta items."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\emr\Field\ComputedEntityMetasItemList",
 * )
 */
class EntityMetaRelationEntityMetaItem extends BaseEntityMetaRelationItem {

}
