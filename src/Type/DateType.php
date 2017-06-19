<?php

namespace Gency\Filterable\Type;

use Gency\Filterable\Filterable;
use Gency\Filterable\FilterableType;

class DateType implements FilterableType
{
    const type = 'Date';
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
