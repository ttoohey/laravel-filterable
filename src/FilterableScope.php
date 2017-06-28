<?php

namespace Gency\Filterable;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FilterableScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (!isset($builder->filters)) {
            return;
        }
        $filter = $this->merge($builder->filters);
        $builder->filterableApply($filter);
    }
    
    private function merge(array $filters)
    {
        $result = [];
        foreach ($filters as $filter) {
            if (!is_array($filter)) {
                throw new \ErrorException('Expected array');
            }
            foreach ($filter as $key => $value) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = $value;
                    continue;
                }
                if (is_array($value)) {
                    $result[$key] = $this->merge([$result[$key], $value]);
                    continue;
                }
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
