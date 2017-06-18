<?php

namespace Gency\Filterable;

use \Illuminate\Support\Facades\DB;
use \Illuminate\Database\Eloquent\Relations;

trait FilterableTrait
{
    public function scopeFilter ($query, $args = null, $root = null)
    {
        if (isset($args['AND'])) {
            if (count($args) !== 1) {
                throw new \ErrorException('AND parameter must not have peers');
            }
            return $query->filterableAnd($args['AND'], $root);
        }
        if (isset($args['OR'])) {
            if (count($args) !== 1) {
                throw new \ErrorException('OR parameter must not have peers');
            }
            return $query->filterableOr($args['OR'], $root);
        }
        if (isset($args['NOT'])) {
            if (count($args) !== 1) {
                throw new \ErrorException('NOT parameter must not have peers');
            }
            return $query->filterableNot($args['NOT'], $root);
        }
        if (isset($args['NOR'])) {
            if (count($args) !== 1) {
                throw new \ErrorException('NOR parameter must not have peers');
            }
            return $query->filterableNor($args['NOR'], $root);
        }
        $filterable = method_exists($this, 'filterable') ? $this->filterable() : $this->filterable;
        foreach ($filterable as $field => $rules) {
            if ($rules instanceof Relations\Relation) {
                if (isset($args[$field])) {
                    $query->filterableRelation($rules, $field, $args[$field], $root);
                    unset($args[$field]);
                }
                continue;
            }
            foreach (collect($rules) as $n => $rule) {
                if ($n === 0) {
                    $k = $field;
                } else {
                    $k = "${field}_${rule}";
                }
                if (isset($args[$k])) {
                    $method = 'filter' . ucfirst(strtolower($rule));
                    $query->$method($field, $args[$k]);
                    unset($args[$k]);
                }
                $k = "!$k";
                if (isset($args[$k])){
                    $method = 'filterNot' . ucfirst(strtolower($rule));
                    $query->$method($field, $args[$k]);
                    unset($args[$k]);
                }
            }
        }
        if (count($args) > 0) {
            throw new \ErrorException('Filter query has unknown field: ' . array_keys($args)[0]);
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
            return $query;
        }
        return $query->where($field, 'like', $arg);
    }
    
    public function scopeFilterIlike($query, $field, $arg)
    {
        if ($arg === null) {
            return $query;
        }
        $arg = mb_strtolower($arg);
        return $query->where($this->lower($field), 'like', $arg);
    }
    
    public function scopeFilterMatch($query, $field, $arg)
    {
        if ($arg === null) {
            return $query;
        }
        $arg = preg_replace('/%/', '\%', $arg);
        if (mb_strstr($term, '*')) {
            $arg = preg_replace('/\*+/', '%', $arg);
        } else {
            $arg = '%' . $arg . '%';
        }
        return $query->filterIlike($field, $arg);
    }
    
    public function scopeFilterMin($query, $field, $arg)
    {
        if ($arg === null) {
            return $query;
        }
        return $query->where($field, '>=', $arg);
    }

    public function scopeFilterMax($query, $field, $arg)
    {
        if ($arg === null) {
            return $query;
        }
        return $query->where($field, '<=', $arg);
    }

    public function scopeFilterLt($query, $field, $arg)
    {
        if ($arg === null) {
            return $query;
        }
        return $query->where($field, '<', $arg);
    }
    
    public function scopeFilterGt($query, $field, $arg)
    {
        if ($arg === null) {
            return $query;
        }
        return $query->where($field, '>', $arg);
    }

    public function scopeFilterRe($query, $field, $arg)
    {
        if ($arg === null) {
            return $query;
        }
        return $query->where($field, '~', $arg);
    }
    
    public function scopeFilterIn($query, $field, $arg)
    {
        return $query->whereIn($field, $arg);
    }

    public function scopeFilterNotIn($query, $field, $arg)
    {
        return $query->whereNotIn($field, $arg);
    }

    public function scopeFilterFt($query, $field, $arg)
    {
        /** TODO: general idea is to join a "table_filterable" table containing a 
            pre-populated tsvector column named "${field}_vector" and use the
            postgres tsearch '@@' operator to search. The $arg expression needs to
            be parsed into a tsquery format **/
        $table = isset($this->filterableTable[self::FT][$field]) ?: ($this->table . '_filterable');
        $vector = "${field}_vector";
        $query->join($table, "${table}.{$this->id}", '=', "{$this->table}.{$this->id}");
        return $query->where("${table}.${vector}", '@@', $this->toFullTextVector($arg));
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
        $t2 = $this->filterableNewAlias(str_plural($field));
        if ($relation instanceof Relations\BelongsToMany) {
            $sub->join($relation->getTable(), $class->getQualifiedKeyName(), '=', $relation->getOtherKey());
            $a2 = str_singular($t1) . '_id';
            $sub->distinct()->select($relation->getForeignKey() . " as $a2");
            $f2 = $t2 . '.' . $a2;
            $root->leftJoin(DB::raw("({$sub->toSql()}) as $t2"), $f1, '=', $f2);
        } else if ($relation instanceof Relations\HasOneOrMany) {
            $sub->distinct()->select($relation->getForeignKey());
            $f2 = $t2 . '.' . $relation->getPlainForeignKey();
            $root->leftJoin(DB::raw("({$sub->toSql()}) as $t2"), $f1, '=', $f2);
        } else {
            $sub->distinct()->select($relation->getQualifiedOtherKeyName() . ' as id');
            $key = $relation->getForeignKey();
            $f1 = $relation->getQualifiedForeignKey();
            $f2 = "$t2.id";
            $root->leftJoin(DB::raw("({$sub->toSql()}) as $t2"), $f1, '=', $f2);
        }
        $root->mergeBindings($sub->toBase());
        $query->whereNotNull($f2);
        return $query;
    }
    
    private $filterableAliasCount = 0;
    private function filterableNewAlias($prefix = 't') {
        return $prefix . '_' . (++$this->filterableAliasCount);
    }
    
    private function lower($field) {
        return DB::raw('lower(' . DB::getQueryGrammar()->wrap($field) .')');
    }
}
