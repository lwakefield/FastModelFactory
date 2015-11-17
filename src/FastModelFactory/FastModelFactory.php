<?php
namespace FastModelFactory;

use Input;

class FastModelFactory
{

    public static function create($class_name, $input = null)
    {
        $input = $input != null ? $input : Input::all();
        $model = static::getNewInstance($class_name);
        $attributes = $model->getAttrsFromInput($input);
        foreach ($attributes as $key => $val) {
            if (!$model->isAttrRelation($key)) {
                $model->setAttribute($key, $val);
            }
        }
        $model->save();
        foreach ($attributes as $key => $val) {
            if ($model->isAttrRelation($key)) {
                $model->createRelation($key, $val);
            }
        }
        return $model->getModel();
    }

    public static function update($class_name, $input = null)
    {
        $input = $input != null ? $input : Input::all();
        $model = array_key_exists('id', $input) ?
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
                $model->updateRelation($key, $val);
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


