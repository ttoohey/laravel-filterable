<?php

namespace Gency\Filterable;

use \Illuminate\Support\Facades\DB;
use \Illuminate\Database\Eloquent\Relations;

trait FilterableTrait
{
    protected $filterableFilter = null;
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new FilterableScope);
    }
  
    public function getFilterable () {
        return isset($this->filterable) ? $this->filterable : [];
    }
    
    public function getFilterableFtTable ($field) {
        return isset($this->filterableFtTable) ? $this->filterableFtTable : ($this->getTable() . '_filterable');
    }
    
    public function getFilterableFtKeyName ($field) {
        return isset($this->filterableFtKeyName) ? $this->filterableFtKeyName : $this->getKeyName();
    }
    
    public function getFilterableFtVector ($field) {
        return isset($this->filterableFtVector) ? $this->filterableFtVector : "${field}_vector";
    }
    
    public function getFilterableFilter () {
        return $this->filterableFilter;
    }
    
    public function scopeFilter ($query, $args = null)
    {
        if ($args === null) {
            return $query;
        }
        if (isset($this->filterableFilter)) {
            $this->filterableFilter = $this->filterableMergeFilters($this->filterableFilter, $args);
        } else {
            $this->filterableFilter = $args;
        }
        return $query;
    }
    
    public function scopeFilterApply ($query)
    {
        if (!isset($this->filterableFilter)) {
            return $query;
        }
        $filter = $this->filterableFilter;
        unset($this->filterableFilter);
        return $query->filterableApply($filter);
    }
    
    public function scopeFilterableApply($query, $args, $root = null)
    {
        if ($args === null) {
            return $query;
        }
        if ($args instanceof $this) {
          return $query->filterableModel($args, $root);
        }
        if (isset($args['AND'])) {
            if (count($args) !== 1) {
                throw new FilterableException('AND parameter must not have peers');
            }
            return $query->filterableAnd($args['AND'], $root);
        }
        if (isset($args['OR'])) {
            if (count($args) !== 1) {
                throw new FilterableException('OR parameter must not have peers');
            }
            return $query->filterableOr($args['OR'], $root);
        }
        if (isset($args['NOT'])) {
            if (count($args) !== 1) {
                throw new FilterableException('NOT parameter must not have peers');
            }
            return $query->filterableNot($args['NOT'], $root);
        }
        if (isset($args['NOR'])) {
            if (count($args) !== 1) {
                throw new FilterableException('NOR parameter must not have peers');
            }
            return $query->filterableNor($args['NOR'], $root);
        }
        foreach ($this->getFilterable() as $field => $rules) {
            if ($rules instanceof Relations\Relation) {
                if (array_key_exists($field, $args)) {
                    $query->filterableRelation($rules, $field, $args[$field], $root);
                    unset($args[$field]);
                }
                continue;
            }
            $rules = collect($rules)->map(function ($rule) {
                return Filterable::isFilterableType($rule) ? $rule::defaultRules() : $rule;
            })->flatten()->unique();
            foreach ($rules as $n => $rule) {
                if ($n === 0) {
                    $k = $field;
                    $nk = "${field}_NOT";
                } else {
                    $k = "${field}_${rule}";
                    $nk = "${field}_NOT_${rule}";
                }
                if (array_key_exists($k, $args)) {
                    $method = 'filter' . $rule;
                    $query->$method($field, $args[$k], $root);
                    unset($args[$k]);
                }
                if (array_key_exists($nk, $args)) {
                    $method = 'filterNot' . $rule;
                    $query->$method($field, $args[$nk], $root);
                    unset($args[$nk]);
                }
            }
        }
        if (count($args) > 0) {
            throw new FilterableException('Filter query on ' . get_class($this) .' has unknown field: ' . array_keys($args)[0]);
        }
        return $query;
    }
    
    public function scopeFilterEq($query, $field, $arg)
    {
        if ($arg === null) {
            return $query->whereNull($field);
        }
        return $query->where($field, $arg);
    }
    
    public function scopeFilterNotEq($query, $field, $arg)
    {
        if ($arg === null) {
            return $query->whereNotNull($field);
        }
        return $query->where($field, '!=' ,$arg);
    }
    
    public function scopeFilterLike($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('LIKE rule does not accept null"');
        }
        return $query->where($field, 'like', $arg);
    }
    
    public function scopeFilterNotLike($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('LIKE rule does not accept null"');
        }
        return $query->where($field, 'not like', $arg);
    }
    
    public function scopeFilterIlike($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('ILIKE rule does not accept null"');
        }
        return $query->where($field, 'ilike', $arg);
    }
    
    public function scopeFilterNotIlike($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('ILIKE rule does not accept null"');
        }
        return $query->where($field, 'not ilike', $arg);
    }
    
    public function scopeFilterMatch($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('MATCH rule does not accept null"');
        }
        $arg = preg_replace('/%/', '\%', $arg);
        if (mb_strstr($arg, '*')) {
            $arg = preg_replace('/\*+/', '%', $arg);
        } else {
            $arg = '%' . $arg . '%';
        }
        return $query->filterIlike($field, $arg);
    }
    
    public function scopeFilterNotMatch($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('MATCH rule does not accept null"');
        }
        $arg = preg_replace('/%/', '\%', $arg);
        if (mb_strstr($arg, '*')) {
            $arg = preg_replace('/\*+/', '%', $arg);
        } else {
            $arg = '%' . $arg . '%';
        }
        return $query->filterNotIlike($field, $arg);
    }
    
    public function scopeFilterMin($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('MIN rule does not accept null"');
        }
        return $query->where($field, '>=', $arg);
    }

    public function scopeFilterMax($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('MAX rule does not accept null"');
        }
        return $query->where($field, '<=', $arg);
    }

    public function scopeFilterLt($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('LT rule does not accept null"');
        }
        return $query->where($field, '<', $arg);
    }
    
    public function scopeFilterGt($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('GT rule does not accept null"');
        }
        return $query->where($field, '>', $arg);
    }

    public function scopeFilterRe($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('RE rule does not accept null"');
        }
        return $query->where($field, '~', $arg);
    }
    
    public function scopeFilterNotRe($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('RE rule does not accept null"');
        }
        return $query->where($field, '!~', $arg);
    }
    
    public function scopeFilterIn($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('IN rule does not accept null"');
        }
        return $query->whereIn($field, $arg);
    }

    public function scopeFilterNotIn($query, $field, $arg)
    {
        if ($arg === null) {
            throw new FilterableException('IN rule does not accept null"');
        }
        return $query->whereNotIn($field, $arg);
    }

    public function scopeFilterFt($query, $field, $arg, $root = null)
    {
        if ($arg === null) {
            throw new FilterableException('FT rule does not accept null"');
        }
        $root = $root ?: $query;
        $table = $query->getModel()->getFilterableFtTable($field);
        $key = $query->getModel()->getFilterableFtKeyName($field);
        $vector = $query->getModel()->getFilterableFtVector($field);
        $rank = "${field}_rank";
        $_rank = DB::raw('ts_rank('.$this->filterable__wrap($vector).', query) as ' . $this->filterable__wrap($rank));
        
        $sub = DB::table($table)->select($key, $_rank);
        $sub->crossJoin(DB::raw('plainto_tsquery(?) query'))->addBinding($arg);

        $where = $this->filterable__wrap($vector) . ' @@ ' . $this->filterable__wrap('query');
        $sub->whereRaw($where);
        
        $t2 = $this->filterable__newAlias($field);
        $f1 = $query->getModel()->getQualifiedKeyName();
        $f2 = "${t2}.{$key}";
        $joinMethod = ($root === $query ? 'join' : 'leftJoin');
        $root->$joinMethod(DB::raw("({$sub->toSql()}) as {$this->filterable__wrap($t2)}"), $f1, '=', $f2);
        foreach ($sub->getBindings() as $binding) {
            $root->addBinding($binding, 'join');
        }
        if ($joinMethod === 'leftJoin') {
            $query->whereNotNull($f2);
        }
        return $query;
    }

    public function scopeFilterNull($query, $field, $arg = true)
    {
        if ($arg) {
            return $query->whereNull($field);
        } else {
            return $query->whereNotNull($field);
        }
    }
    
    public function scopeFilterNotNull($query, $field, $arg = true)
    {
        return $query->filterNull($field, !$arg);
    }
    
    public function scopeFilterableAnd($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->filterableApply($filter, $root);
            }
        });
    }
    
    public function scopeFilterableOr($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->orWhere(function ($subQuery) use ($filter, $root) {
                    $subQuery->filterableApply($filter, $root);
                });
            }
        });
    }
    
    public function scopeFilterableNot($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->filterableApply($filter, $root);
            }
        }, null, null, 'and not');
    }

    public function scopeFilterableNor($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->orWhere(function ($subQuery) use ($filter, $root) {
                    $subQuery->filterableApply($filter, $root);
                });
            }
        }, null, null, 'and not');
    }    

    public function scopeFilterableRelation($query, $relation, $field, $args, $root = null)
    {
        $root = $root ?: $query;
        $t1 = $query->getModel()->getTable();
        $f1 = $query->getModel()->getQualifiedKeyName();
        $class = $relation->getRelated();
        $sub = $class::filterableApply($args);
        $t2 = $this->filterable__newAlias(str_plural($field));
        $joinMethod = ($root === $query ? 'join' : 'leftJoin');
        if ($relation instanceof Relations\BelongsToMany) {
            $sub->join($relation->getTable(), $class->getQualifiedKeyName(), '=', $relation->getOtherKey());
            $a2 = str_singular($t1) . '_id';
            $sub->distinct()->select($relation->getForeignKey() . " as $a2");
            $f2 = $t2 . '.' . $a2;
            $root->$joinMethod(DB::raw("({$sub->toSql()}) as {$this->filterable__wrap($t2)}"), $f1, '=', $f2);
        } else if ($relation instanceof Relations\HasOneOrMany) {
            $sub->distinct()->select($relation->getForeignKey());
            $f2 = $t2 . '.' . $relation->getPlainForeignKey();
            $root->$joinMethod(DB::raw("({$sub->toSql()}) as {$this->filterable__wrap($t2)}"), $f1, '=', $f2);
        } else {
            $a2 = $field . '_id';
            $sub->distinct()->select($relation->getQualifiedOtherKeyName() . " as $a2");
            $key = $relation->getForeignKey();
            $f1 = $relation->getQualifiedForeignKey();
            $f2 = "$t2.$a2";
            $root->$joinMethod(DB::raw("({$sub->toSql()}) as {$this->filterable__wrap($t2)}"), $f1, '=', $f2);
        }
        foreach ($sub->toBase()->getBindings() as $binding) {
            $root->addBinding($binding, 'join');
        }
        if ($joinMethod === 'leftJoin') {
            $query->whereNotNull($f2);
        }
        return $query;
    }
    
    public function scopeFilterableModel($query, $model, $root = null)
    {
        $keyName = $query->getModel()->getKeyName();
        return $query->where($keyName, $model->{$keyName});
    }
    
    public function filterableMergeFilters ($a, $b)
    {
        $logical = function ($a, $b, $op, $inv_op) {
            $logicOperators = ['AND', 'OR'];
            if (in_array(key($a), $logicOperators)) {
                return [key($a) => (
                    collect(current($a))->map(function ($item) use ($b) {
                        return $this->filterableMergeFilters($item, $b);
                    })->toArray()
                )];
            }
            if (key($a) !== $op) {
                return null;
            }
            if (count(current($a)) === 0) {
                return $b;
            }
            $filter = $this->filterableMergeFilters([$inv_op => (
                collect(current($a))->map(function ($item) {
                    return ['!' => $item];
                })->toArray())], $b);
            return count(current($filter)) === 1 ? current($filter)[0] : $filter;
        };
        $filter = $logical($a, $b, 'NOT', 'OR') ?: $logical($a, $b, 'NOR', 'AND')
               ?: $logical($b, $a, 'NOT', 'OR') ?: $logical($b, $a, 'NOR', 'AND');
        if ($filter !== null) {
            return $filter;
        }

        $a_inv = key($a) === '!';
        $b_inv = key($b) === '!';
        if ($a_inv) {
            $a = current($a);
        }
        if ($b_inv) {
            $b = current($b);
        }
        
        $filter = [];
        $and = [];
        foreach ($this->getFilterable() as $field => $rules) {
            if ($rules instanceof Relations\Relation) {
                $in_a = array_key_exists($field, $a);
                $in_b = array_key_exists($field, $b);
                if ($in_a && $in_b) {
                    $related = $rules->getRelated();
                    if (($a[$field] instanceof $related) || ($b[$field] instanceof $related)) {
                        $and[] = $a_inv ? ['NOT' => [[$field => $a[$field]]]] : [$field => $a[$field]];
                        $and[] = $b_inv ? ['NOT' => [[$field => $b[$field]]]] : [$field => $b[$field]];
                    } else {
                        $a_field = $a_inv ? ['!' => $a[$field]] : $a[$field];
                        $b_field = $b_inv ? ['!' => $b[$field]] : $b[$field];
                        $filter[$field] = $related->filterableMergeFilters($a_field, $b_field);
                    }
                } else if ($in_a) {
                    if ($a_inv) {
                        $and[] = ['NOT' => [[$field => $a[$field]]]];
                    } else {
                        $filter[$field] = $a[$field];
                    }
                } else if ($in_b) {
                    if ($b_inv) {
                        $and[] = ['NOT' => [[$field => $b[$field]]]];
                    } else {
                        $filter[$field] = $b[$field];
                    }
                }
                unset($a[$field]);
                unset($b[$field]);
            } else {
                $rules = collect($rules)->map(function ($rule) {
                    return Filterable::isFilterableType($rule) ? $rule::defaultRules() : $rule;
                })->flatten()->unique();
                foreach ($rules as $n => $rule) {
                    if ($n === 0) {
                        $k = $field;
                    } else {
                        $k = "${field}_${rule}";
                    }
                    $in_a = array_key_exists($k, $a);
                    $in_b = array_key_exists($k, $b);
                    if ($in_a && $in_b) {
                        $and[] = $a_inv ? ['NOT' => [[$k => $a[$k]]]] : [$k => $a[$k]];
                        $and[] = $b_inv ? ['NOT' => [[$k => $b[$k]]]] : [$k => $b[$k]];
                    } else if ($in_a) {
                        if ($a_inv) {
                            $and[] = ['NOT' => [[$k => $a[$k]]]];
                        } else {
                            $filter[$k] = $a[$k];
                        }
                    } else if ($in_b) {
                        if ($b_inv) {
                            $and[] = ['NOT' => [[$k => $b[$k]]]];
                        } else {
                            $filter[$k] = $b[$k];
                        }
                    }
                    unset($a[$k]);
                    unset($b[$k]);
                }
                foreach ($rules as $n => $rule) {
                    if ($n === 0) {
                        $k = "${field}_NOT";
                    } else {
                        $k = "${field}_NOT_${rule}";
                    }
                    $in_a = array_key_exists($k, $a);
                    $in_b = array_key_exists($k, $b);
                    if ($in_a && $in_b) {
                        $and[] = $a_inv ? ['NOT' => [[$k => $a[$k]]]] : [$k => $a[$k]];
                        $and[] = $b_inv ? ['NOT' => [[$k => $b[$k]]]] : [$k => $b[$k]];
                    } else if ($in_a) {
                        if ($a_inv) {
                            $and[] = ['NOT' => [[$k => $a[$k]]]];
                        } else {
                            $filter[$k] = $a[$k];
                        }
                    } else if ($in_b) {
                        if ($b_inv) {
                            $and[] = ['NOT' => [[$k => $b[$k]]]];
                        } else {
                            $filter[$k] = $b[$k];
                        }
                    }
                    unset($a[$k]);
                    unset($b[$k]);
                }
            }
        }
        if (count($a) > 0) {
            throw new FilterableException ('Argument #1 Filter query on ' . get_class($this) .' has unknonwn field: ' . collect($a)->keys()[0]);
        }
        if (count($b) > 0) {
            throw new FilterableException ('Argument #2 Filter query on ' . get_class($this) .' has unknonwn field: ' . collect($b)->keys()[0]);
        }
        if (count($and) > 0) {
            if (count($filter) > 0) {
                $and[] = $filter;
            }
            return [
                'AND' => $and
            ];
        }
        return $filter;
    }

    private $filterable__aliasCount = 0;
    private function filterable__newAlias($prefix = 't') {
        return $prefix . '_' . (++$this->filterable__aliasCount);
    }
    
    private function filterable__wrap($field) {
        return DB::getQueryGrammar()->wrap($field);
    }
}
