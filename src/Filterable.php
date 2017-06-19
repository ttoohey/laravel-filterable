<?php

namespace Gency\Filterable;

class Filterable
{
    const EQ = 'EQ'; // equal to
    const LIKE = 'LIKE'; // SQL like
    const ILIKE = 'ILIKE'; // case-insensitive like
    const MATCH = 'MATCH'; // glob matching
    const MIN = 'MIN'; // greater than or equal to
    const MAX = 'MAX'; // less than or equal to
    const LT = 'LT'; // less than
    const GT = 'GT'; // greater than
    const RE = 'RE'; // regular expression match
    const FT = 'FT'; // full text search
    const IN = 'IN'; // list contains
    
    const String = Type\StringType::class;
    const Text = Type\TextType::class;
    const Integer = Type\IntegerType::class;
    const Numeric = Type\NumericType::class;
    const Enum = Type\EnumType::class;
    const Date = Type\DateType::class;
    const Boolean = Type\BooleanType::class;
}
