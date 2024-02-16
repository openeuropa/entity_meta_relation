<?php

declare(strict_types=1);

namespace Drupal\emr\Entity\Query\Sql;

use Drupal\Core\Entity\Query\Sql\QueryFactory as CoreQueryFactory;

/**
 * EntityMeta query factory.
 *
 * We can leave core defaults as we just need it to load the Query object from
 * the same namespace.
 */
class QueryFactory extends CoreQueryFactory {}
