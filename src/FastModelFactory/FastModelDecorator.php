<?php
namespace FastModelFactory;

use Illuminate\Database\Eloquent\Model;
use Input;

class FastModelDecorator
{

    public function __construct($model)
    {
        $this->model = $model;
    }

    public static function decorate($model)
    {
        return new FastModelDecorator($model);
    }

    public function getAttrsFromInput($input)
    {
        $attrs = [];
        foreach($this->model->getFillable() as $fillable) {
            if (array_key_exists($fillable, $input)) {
                $attrs[$fillable] = array_get($input, $fillable);
            }
        }
        return $attrs;
    }

    public function isAttrRelation($attr)
    {
        return method_exists($this->model, $attr) &&
             is_subclass_of($this->model->$attr(), 'Illuminate\Database\Eloquent\Relations\Relation');
    }

    public function createRelation($relation, $attrs)
    {
        $relation = $this->$relation();
        if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\BelongsTo')) {
            $related_model = FastModelFactory::create(get_class($relation->getRelated()),
                $attrs);
            $relation->associate($related_model);
        } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasOne')) {
            $related_model = FastModelFactory::create(get_class($relation->getRelated()),
                $attrs);
            $relation->save($related_model);
        } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasMany')) {
            $models = [];
            foreach ($attrs as $nested_attrs) {
                $models[] = FastModelFactory::create(get_class($relation->getRelated()),
                    $nested_attrs);
            }
            $relation->saveMany($models);
        }
    }

    public function updateRelation($relation, $attrs)
    {
        $relation = $this->$relation();
        if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\BelongsTo')) {
            $related_model = FastModelFactory::update(get_class($relation->getRelated()),
                $attrs);
            $relation->associate($related_model);
        } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasOne')) {
            $related_model = FastModelFactory::update(get_class($relation->getRelated()),
                $attrs);
            $relation->save($related_model);
        } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasMany')) {
            $models = [];
            foreach ($attrs as $nested_attrs) {
                $models[] = FastModelFactory::update(get_class($relation->getRelated()),
                    $nested_attrs);
            }
            $relation->saveMany($models);
        }
    }

    public function getModel()
    {
        return $this->model;
    }

    public function __call($fn_name, $args)
    {
        return call_user_func_array([$this->model, $fn_name], $args);
    }

}



