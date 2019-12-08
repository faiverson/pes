<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected $builder;

    protected static $entityGlobalScope = [];

    protected static function boot()
    {
        parent::boot();
        if(!empty(static::$entityGlobalScope)) {
            foreach (static::$entityGlobalScope as $global) {
                $scope = class_exists($global) ? new $global : $global;
                static::addGlobalScope($scope);
            }
        }
    }

    public static function query(): Builder
    {
        return parent::query();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder( $query)
    {
        $builder = $this->builder;
        if (empty($builder)) {
            throw new \Exception('empty_entity_builder', ['builder' => class_basename($this)]);
        }
        return new $builder($query);
    }
}
