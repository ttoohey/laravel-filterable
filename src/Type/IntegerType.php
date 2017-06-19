<?php

namespace Gency\Filterable\Type;

use Gency\Filterable\Filterable;
use Gency\Filterable\FilterableType;

class IntegerType implements FilterableType
{
    const type = 'Integer';
    static function default () {
        return [
          Filterable::EQ,
          Filterable::MIN,
          Filterable::MAX,
          Filterable::LT,
          Filterable::GT
        ];
    }
}
