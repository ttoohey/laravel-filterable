<?php

namespace Gency\Filterable\Type;

use Gency\Filterable\Filterable;
use Gency\Filterable\FilterableType;

class EnumType implements FilterableType
{
    const type = 'Enum';
    static function defaultRules () {
        return [
          Filterable::EQ,
          Filterable::IN
        ];
    }
}
