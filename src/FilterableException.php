<?php

namespace Gency\Filterable;

class FilterableException extends \Exception
{
    public function __construct($message) {
        parent::__construct($message);
    }
}
