<?php

namespace Drupal\entity_meta_example\Plugin\EntityMetaRelation;

use Drupal\emr\EntityMetaRelationPluginInterface;
use Drupal\emr\EntityMetaRelationContentFormPluginBase;

/**
 * Plugin implementation of the entity_meta_relation.
 *
 * @EntityMetaRelation(
 *   id = "visual_configuration",
 *   label = @Translation("Visual configuration"),
 *   bundle = "node_meta_relation",
 *   entity_meta_bundle = "visual",
 *   description = @Translation("Visual configuration.")
 * )
 */
class VisualConfiguration extends EntityMetaRelationContentFormPluginBase implements EntityMetaRelationPluginInterface {

}
