<?php

namespace App\Extensions\Database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as DBBuilder;


class QueryBuilder
{
    /** @var Builder|DBBuilder $request */
    protected $model;

    /** @var array $methodsInUse */
    protected $methodsInUse = [];  // structure: ['join' => [], 'rightJoin' => [], 'orderBy' => [], ...]

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function __call($method, $params)
    {
        $key = serialize($params);

        // This if branch stops calling duplicate Eloquent\Builder calls.
        if( isset($this->methodsInUse[$method])
            && in_array($key, $this->methodsInUse[$method])
        ) {
            return $this;
        }

        $this->methodsInUse[$method][] = $key;

        $return = $this->model->$method( ...$params );

        //  If $return is not instance of Eloquent\QueryBuilder return $result otherwise return $this.
        return $return instanceof Builder || $return instanceof DBBuilder ? $this : $return;
    }

    public function __callStatis($method, $params)
    {
        $return = $this->model->$method( ...$params );

        //  If $return is not instance of Eloquent\QueryBuilder return $result otherwise return $this.
        return $return instanceof Builder || $return instanceof DBBuilder ? $this : $return;
    }

    public function __get($param)
    {
        return $this->model->$param;
    }

    public function __set($prop, $value)
    {
        $this->model->$prop = $value;

        return $this;
    }
}
