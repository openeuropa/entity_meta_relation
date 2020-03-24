<?php

declare(strict_types = 1);

namespace Drupal\entity_meta_audio\Plugin\EntityMetaRelation;

use Drupal\emr\Plugin\EntityMetaRelationInlineContentFormPluginBase;

/**
 * Plugin implementation of the entity_meta_relation.
 *
 * @EntityMetaRelation(
 *   id = "audio_configuration",
 *   label = @Translation("Audio configuration"),
 *   entity_meta_bundle = "audio",
 *   content_form = TRUE,
 *   entity_meta_wrapper_class = "\Drupal\entity_meta_audio\AudioEntityMetaWrapper",
 *   description = @Translation("Audio configuration.")
 * )
 */
class AudioConfiguration extends EntityMetaRelationInlineContentFormPluginBase {}
