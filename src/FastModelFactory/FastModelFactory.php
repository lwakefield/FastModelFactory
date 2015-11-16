<?php
namespace FastModelFactory;

use Illuminate\Database\Eloquent\Model;
use Input;

class FastModelFactory
{

    public static function create($class_name, $input = null)
    {
        $input = $input != null ? $input : Input::all();
        $model = static::getNewInstance($class_name);
        $attributes = static::getInputForModel($input, $model);
        foreach ($attributes as $key => $val) {
            if (!static::isRelation($key, $model)) {
                $model->setAttribute($key, $val);
            }
        }
        $model->save();
        foreach ($attributes as $key => $val) {
            if (static::isRelation($key, $model)) {
                static::saveRelation($key, $val, $model);
            }
        }
        return $model;
    }

    private static function saveRelation($key, $val, $model)
    {
        $relation = $model->$key();
        if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\BelongsTo')) {
            $related_model = static::create(get_class($relation->getRelated()),
                $val);
            $relation->associate($related_model);
        } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasOne')) {
            $related_model = static::create(get_class($relation->getRelated()),
                $val);
            $relation->save($related_model);
        } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasMany')) {
            $models = [];
            foreach ($val as $attrs) {
                $models[] = static::create(get_class($relation->getRelated()),
                    $attrs);
            }
            $relation->saveMany($models);
        }

    }

    private static function isRelation($attribute, $model)
    {
        return method_exists($model, $attribute) &&
             is_subclass_of($model->$attribute(), 'Illuminate\Database\Eloquent\Relations\Relation');
    }

    private static function getInputForModel($attributes, $model)
    {
        $attrs = [];
        foreach($model->getFillable() as $fillable) {
            if (array_key_exists($fillable, $attributes)) {
                $attrs[$fillable] = array_get($attributes, $fillable);
            }
        }
        return $attrs;
    }

    private static function getNewInstance($class_name)
    {
        return new $class_name;
    }

    private function saveRelations($attrs, $update = false)
    {
        if (!Input::ajax()) {
            return;
        }
        foreach ($attrs as $key => $val) {
            $relation = $this->$key();
            if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\BelongsTo')) {
                $model = $update ? 
                    $relation->getRelated()->updateFromAttributes($val) :
                    $relation->getRelated()->createFromAttributes($val);
                $relation->associate($model);
            } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasOne')) {
                $model = $update ?
                    $relation->getRelated()->updateFromAttributes($val) :
                    $relation->create($val);
            } else if (is_a($relation, 'Illuminate\Database\Eloquent\Relations\HasMany')) {
                foreach ($val as $attrs) {
                    $model = $update ?
                        $relation->getRelated()->updateFromAttributes($attrs) :
                        $relation->getRelated()->createFromAttributes($attrs);
                    $relation->save($model);
                }
            }
        }
    }

    protected function populateFromArray($attributes)
    {
        foreach ($attributes as $key => $val) {
            if (in_array($key, $this->fillable)) {
                $this->setAttribute($key, $val);
            }
        }
    }

    protected function validate()
    {
        $messages = empty($this->messages) ? [] : $this->messages;
        if (isset($this->rules)) {
            $validator = Validator::make($this->getAttributes(), $this->rules, $messages);
            if ($validator->fails()) {
                throw new ValidationException('Error validating model', $validator->errors());
            }
        }
    }

}


