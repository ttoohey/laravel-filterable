<?php

namespace Gency\Filterable;

use \Illuminate\Support\Facades\DB;
use \Illuminate\Database\Eloquent\Relations;

trait FilterableTrait
{
    
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
    
    public function scopeFilter ($query, $args = null, $root = null)
    {
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
                return Filterable::isFilterableType($rule) ? $rule::default() : $rule;
            })->flatten()->unique();
            foreach ($rules as $n => $rule) {
                if ($n === 0) {
                    $k = $field;
                } else {
                    $k = "${field}_${rule}";
                }
                if (array_key_exists($k, $args)) {
                    $method = 'filter' . ucfirst(strtolower($rule));
                    $query->$method($field, $args[$k], $root);
                    unset($args[$k]);
                }
                $k = "!$k";
                if (array_key_exists($k, $args)) {
                    $method = 'filterNot' . ucfirst(strtolower($rule));
                    $query->$method($field, $args[$k], $root);
                    unset($args[$k]);
                }
            }
        }
        if (count($args) > 0) {
            throw new FilterableException('Filter query has unknown field: ' . array_keys($args)[0]);
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
        $root->$joinMethod(DB::raw("({$sub->toSql()}) as $t2"), $f1, '=', $f2);
        $query->mergeBindings($sub);
        if ($joinMethod === 'leftJoin') {
            $query->whereNotNull($f2);
        }
        return $query;
    }
    
    public function scopeFilterableAnd($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->filter($filter, $root);
            }
        });
    }
    
    public function scopeFilterableOr($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->orWhere(function ($subQuery) use ($filter, $root) {
                    $subQuery->filter($filter, $root);
                });
            }
        });
    }
    
    public function scopeFilterableNot($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->filter($filter, $root);
            }
        }, null, null, 'and not');
    }

    public function scopeFilterableNOr($query, $filters, $root = null)
    {
        $root = $root ?: $query;
        return $query->where(function ($subQuery) use ($filters, $root) {
            foreach($filters as $filter) {
                $subQuery->orWhere(function ($subQuery) use ($filter, $root) {
                    $subQuery->filter($filter, $root);
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
        $sub = $class::filter($args);
        $t2 = $this->filterable__newAlias(str_plural($field));
        $joinMethod = ($root === $query ? 'join' : 'leftJoin');
        if ($relation instanceof Relations\BelongsToMany) {
            $sub->join($relation->getTable(), $class->getQualifiedKeyName(), '=', $relation->getOtherKey());
            $a2 = str_singular($t1) . '_id';
            $sub->distinct()->select($relation->getForeignKey() . " as $a2");
            $f2 = $t2 . '.' . $a2;
            $root->$joinMethod(DB::raw("({$sub->toSql()}) as $t2"), $f1, '=', $f2);
        } else if ($relation instanceof Relations\HasOneOrMany) {
            $sub->distinct()->select($relation->getForeignKey());
            $f2 = $t2 . '.' . $relation->getPlainForeignKey();
            $root->$joinMethod(DB::raw("({$sub->toSql()}) as $t2"), $f1, '=', $f2);
        } else {
            $sub->distinct()->select($relation->getQualifiedOtherKeyName() . ' as id');
            $key = $relation->getForeignKey();
            $f1 = $relation->getQualifiedForeignKey();
            $f2 = "$t2.id";
            $root->$joinMethod(DB::raw("({$sub->toSql()}) as $t2"), $f1, '=', $f2);
        }
        $root->mergeBindings($sub->toBase());
        if ($joinMethod === 'leftJoin') {
            $query->whereNotNull($f2);
        }
        return $query;
    }
    
    private $filterable__aliasCount = 0;
    private function filterable__newAlias($prefix = 't') {
        return $prefix . '_' . (++$this->filterable__aliasCount);
    }
    
    private function filterable__wrap($field) {
        return DB::getQueryGrammar()->wrap($field);
    }
}
