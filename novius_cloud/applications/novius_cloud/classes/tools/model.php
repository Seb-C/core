<?php

namespace NC;

class Tools_Model {

    /**
     * Search models of each installed applications, optionally in the specified $folder
     *
     * @param string|null $folder
     * @return array
     */
    public static function searchModels($folder = null) {
        $folder = trim($folder, DS);
        return Tools_Applications::searchClasses('model'.DS.$folder);
    }

    /**
     * Search models that have the specified $behaviour_name for each installed applications
     *
     * @param string $behaviour_name
     * @return array
     */
    public static function searchModelsWithBehaviour($behaviour_name) {
        // Search all models
        $models = static::searchModels();
        $models = \Arr::flatten($models);
        // Filter the models to keep those with the searched behaviour
        $models = array_filter($models, function($model) use ($behaviour_name) {
            //filters for the non Abstract Model !
            $reflexion = new \ReflectionClass($model);
            if ($reflexion->isAbstract()) {
                return false;
            }
            if (class_exists($model) && method_exists($model, 'behaviours')) {
                $behaviour = $model::behaviours($behaviour_name);
                if (!empty($behaviour)) {
                    return true;
                }
            }
            return false;
        });
        return $models;
    }

    /**
     * Get all the behaviour properties for the specified $model
     *
     * @param string|object $model
     * @param string $behaviour_name
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public static function getBehaviourProperties($model, $behaviour_name) {
        $model = is_a($model, '\Nos\Orm\Model') ? get_class($model) : (string) $model;
        $behaviour_properties = call_user_func($model.'::observers', $behaviour_name);
        if (is_null($behaviour_properties)) {
            throw new \OutOfBoundsException('Model '.$model.' has no behaviour '.$behaviour_name.'.');
        }
        return $behaviour_properties;
    }

    /**
     * Get the behaviour property by $name for the specified $model
     *
     * @param \Nos\Orm\Model|string $model
     * @param string $behaviour_name
     * @param string $property_name
     * @param null|mixed $default
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public static function getBehaviourProperty($model, $behaviour_name, $property_name, $default = null) {
        return \Arr::get(static::getBehaviourProperties($model, $behaviour_name), $property_name, $default);
    }
}
