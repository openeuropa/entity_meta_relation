<?php

namespace Drupal\entity_meta_example\Plugin\EntityMetaRelation;

use Drupal\entity_meta_relation\EntityMetaRelationPluginBase;

/**
 * Plugin implementation of the entity_meta_relation.
 *
 * @EntityMetaRelation(
 *   id = "visual_configuration",
 *   label = @Translation("Visual configuration"),
 *   description = @Translation("Visual configuration.")
 * )
 */
abstract class VisualConfiguration extends EntityMetaRelationPluginBase {

}
