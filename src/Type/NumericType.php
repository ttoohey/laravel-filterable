<?php

namespace Gency\Filterable\Type;

use Gency\Filterable\Filterable;
use Gency\Filterable\FilterableType;

class NumericType implements FilterableType
{
    const type = 'Numeric';
    static function defaultRules () {
        return [
          Filterable::EQ,
          Filterable::MIN,
          Filterable::MAX,
          Filterable::LT,
          Filterable::GT
        ];
    }
}
