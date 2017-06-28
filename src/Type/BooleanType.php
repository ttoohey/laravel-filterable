<?php

namespace Gency\Filterable\Type;

use Gency\Filterable\Filterable;
use Gency\Filterable\FilterableType;

class BooleanType implements FilterableType
{
    const type = 'Boolean';
    static function defaultRules () {
        return [
          Filterable::EQ
        ];
    }
}
