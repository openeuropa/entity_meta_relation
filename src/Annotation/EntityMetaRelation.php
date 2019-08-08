<?php

declare(strict_types = 1);

namespace Drupal\emr\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity meta relation annotation object.
 *
 * @Annotation
 * phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
 */
class EntityMetaRelation extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The bundle of the EntityMeta entity type this plugin works with.
   *
   * @var string
   */
  public $entity_meta_bundle;

  /**
   * Whether the plugin should be embedded into a content form.
   *
   * @var string
   */
  public $content_form;

}
