<?php

namespace Gency\Filterable\Type;

use Gency\Filterable\Filterable;
use Gency\Filterable\FilterableType;

class TextType implements FilterableType
{
    const type = 'Text';
    static function defaultRules () {
        return [
          Filterable::FT,
          Filterable::EQ,
          Filterable::LIKE,
          Filterable::ILIKE,
          Filterable::MATCH
        ];
    }
}
