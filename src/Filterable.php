<?php

namespace Gency\Filterable;

use \Illuminate\Support\Facades\DB;

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
    
    const String = [
        self::EQ,
        self::LIKE,
        self::ILIKE,
        self::MATCH,
    ];
    
    const Numeric = [
        self::EQ,
        self::MIN,
        self::MAX,
        self::LT,
        self::GT
    ];
    
    const Enum = [
        self::EQ,
        self::IN
    ];
    
    const Date = [
        self::EQ,
        self::MIN,
        self::MAX,
        self::LT,
        self::GT
    ];
    
    const Boolean = [
        self::EQ
    ];
}
