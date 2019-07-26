<?php

namespace Drupal\entity_meta_example\Plugin\EntityMetaRelation;

use Drupal\emr\Plugin\EntityMetaRelationContentFormPluginBase;

/**
 * Plugin implementation of the entity_meta_relation.
 *
 * @EntityMetaRelation(
 *   id = "visual_configuration",
 *   label = @Translation("Visual configuration"),
 *   entity_meta_bundle = "visual",
 *   content_form = TRUE,
 *   description = @Translation("Visual configuration.")
 * )
 */
class VisualConfiguration extends EntityMetaRelationContentFormPluginBase {}
