<?php

namespace Drupal\emr\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines entity_meta_relation annotation object.
 *
 * @Annotation
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

}
