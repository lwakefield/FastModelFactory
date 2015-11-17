<?php
namespace FastModelFactory;

use Input;

class FastModelFactory
{

    public static function create($class_name, $input = null)
    {
        return static::createOrUpdate($class_name, $input, false);
    }

    public static function update($class_name, $input = null)
    {
        return static::createOrUpdate($class_name, $input, true);
    }

    private static function createOrUpdate($class_name, $input = null, $update = false)
    {
        $input = $input != null ? $input : Input::all();
        $model = $update && array_key_exists('id', $input) ?
            static::findNewInstance($class_name, array_get($input, 'id')) :
            static::getNewInstance($class_name);
        $attributes = $model->getAttrsFromInput($input);
        foreach ($attributes as $key => $val) {
            if (!$model->isAttrRelation($key)) {
                $model->setAttribute($key, $val);
            }
        }
        $model->save();
        foreach ($attributes as $key => $val) {
            if ($model->isAttrRelation($key)) {
                $update ?
                    $model->updateRelation($key, $val) :
                    $model->createRelation($key, $val);
            }
        }
        return $model->getModel();
    }

    private static function findNewInstance($class_name, $id)
    {
        $instance = $class_name::find($id);
        return FastModelDecorator::decorate($instance);
    }

    private static function getNewInstance($class_name)
    {
        $instance = new $class_name;
        return FastModelDecorator::decorate($instance);
    }

}


