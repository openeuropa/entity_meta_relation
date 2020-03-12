<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\emr\Entity\EntityMetaInterface;

/**
 * Defines the 'emr_item' entity field type.
 *
 * @FieldType(
 *   id = "emr_item",
 *   label = @Translation("Entity meta relation item"),
 *   description = @Translation("Relates to entity meta items."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\emr\Field\ComputedEntityMetasItemList",
 * )
 */
class EntityMetaRelationItem extends MapItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel('The entity')
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      ->setComputed(TRUE)
      ->setReadOnly(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    $this->values = [];
    if (!isset($values)) {
      return;
    }

    if (!is_array($values) && $values instanceof EntityMetaRelationItem) {
      $values = $values->getValue();
    }

    if (!$values instanceof EntityMetaInterface) {
      return;
    }

    $this->values['entity'] = $values;

    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'entity';
  }

}
