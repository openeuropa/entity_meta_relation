<?php

declare(strict_types = 1);

namespace Drupal\emr\Plugin\Field\FieldType;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;

/**
 * Base class for EntityMeta item field types.
 */
class BaseEntityMetaRelationItem extends MapItem {

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

    if (!is_array($values) && $values instanceof BaseEntityMetaRelationItem) {
      $values = $values->getValue();
    }

    if (!$values instanceof ContentEntityInterface) {
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
