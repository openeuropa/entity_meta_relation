<?php

declare(strict_types=1);

namespace Drupal\entity_meta_force\Plugin\EntityMetaRelation;

use Drupal\emr\Entity\EntityMetaInterface;
use Drupal\emr\Plugin\EntityMetaRelationInlineContentFormPluginBase;

/**
 * Plugin implementation of the entity_meta_relation.
 *
 * @EntityMetaRelation(
 *   id = "force_configuration",
 *   label = @Translation("Force configuration"),
 *   entity_meta_bundle = "force",
 *   content_form = TRUE,
 *   attach_by_default = TRUE,
 *   entity_meta_wrapper_class = "\Drupal\entity_meta_force\ForceEntityMetaWrapper",
 *   description = @Translation("Force configuration.")
 * )
 */
class ForceConfiguration extends EntityMetaRelationInlineContentFormPluginBase {

  /**
   * {@inheritdoc}
   */
  public function fillDefaultEntityMetaValues(EntityMetaInterface $entity_meta): void {
    $entity_meta->getWrapper()->setGravity('weak');
  }

}
