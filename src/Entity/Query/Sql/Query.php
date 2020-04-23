<?php

declare(strict_types = 1);

namespace Drupal\emr\Entity\Query\Sql;

use Drupal\Core\Entity\Query\Sql\Query as CoreQuery;

/**
 * EntityMeta query class.
 *
 * Alters the query in order to include all the revisions in the query and
 * apply a default filter for the default revision.
 */
class Query extends CoreQuery {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $original_all_revisions = $this->allRevisions;

    // Include all revisions in the query so that we can join into the revisions
    // tables.
    $this->allRevisions = TRUE;

    $query = $this->prepare();

    // If not all revisions are requested, apply a filter on the default
    // revision.
    if (!$original_all_revisions) {
      $this->condition('emr_default_revision', TRUE);
    }

    $result = $query->compile()
      ->addSort()
      ->finish()
      ->result();

    if (!$original_all_revisions && !empty($result) && is_array($result)) {
      // This should not happen since only one default revision should exist
      // per entity, but ensure only one revision is included in the results
      // per entity.
      return array_unique($result);
    }

    return $result;
  }

}
