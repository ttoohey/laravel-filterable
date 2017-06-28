<?php

namespace Gency\Filterable\Type;

use Gency\Filterable\Filterable;
use \Gency\Filterable\FilterableType;

class StringType implements FilterableType
{
    const type = 'String';
    static function defaultRules () {
        return [
          Filterable::EQ,
          Filterable::LIKE,
          Filterable::ILIKE,
          Filterable::MATCH
        ];
    }
}
