<?php

namespace Gency\Filterable;

use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FilterableScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->filterableApply($model->getFilterableFilter());
    }
}
