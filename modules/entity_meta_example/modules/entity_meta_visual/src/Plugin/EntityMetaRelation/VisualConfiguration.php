<?php

declare(strict_types=1);

namespace Drupal\entity_meta_visual\Plugin\EntityMetaRelation;

use Drupal\emr\Plugin\EntityMetaRelationInlineContentFormPluginBase;

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
class VisualConfiguration extends EntityMetaRelationInlineContentFormPluginBase {}
