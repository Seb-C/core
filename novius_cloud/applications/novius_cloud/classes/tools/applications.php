<?php

namespace NC;

use \Nos\Application;
use \Nos\Config_Data;

class Tools_Applications {

    /**
     * Get installed applications
     *
     * @return array
     */
    public static function getInstalledApplications() {
        $app_installed = array();
        foreach (array_keys(Config_Data::get('app_installed')) as $module) {
            try {
                $app = Application::forge($module);
                if ($app->is_installed()) {
                    $app_installed[$app->folder] = $app;
                }
            } catch (\Exception $e) {}
        }

        return $app_installed;
    }

    /**
     * Search classes of each installed applications, optionally in the specified $folder
     *
     * @param string|null $folder
     * @return array
     */
    public static function searchClasses($folder = null) {
        $folder = trim($folder, DS);

        // Get applications namespaces
        $namespaces = Config_Data::get('app_namespaces', null);

        // Search files in each installed application
        $classes_path = Tools_Applications::searchFiles('classes'.DS.$folder);

        // Build class names with namespace for each file found
        $classes = array();
        foreach ($classes_path as $app_folder => $paths) {
            foreach ($paths as $path) {
                $namespace = \Arr::get($namespaces, $app_folder);

                // Remove extension
                if (\Str::ends_with($path, '.php')) {
                    $path = \Str::sub($path, 0, -4);
                }

                // Remove suffix
                foreach (\Autoloader::$suffixed as $suffix) {
                    if (\Str::ends_with($path, '.'.$suffix)) {
                        $path = \Str::sub($path, 0, -(\Str::length($suffix) + 1));
                    }
                }

                // Convert path to class name
                $class = \Inflector::words_to_upper(str_replace(DS, '_', trim($folder.DS.trim($path, DS), DS)));

                // Add namespace
                $class = rtrim($namespace, '\\').'\\'.$class;

                // Add namespace
                $classes[$app_folder][] = $class;
            }
        }

        return $classes;
    }

    public static function searchInheritedClasses($parent_class) {
        $inherited_classes = array();
        $classes = static::searchClasses();
        foreach ($classes as $app_name => $app_classes) {
            foreach ($app_classes as $class) {
                try {
                    $class = '\\'.trim($class, '\\');
                    if (class_exists($class) && is_subclass_of($class, $parent_class)) {
                        $inherited_classes[] = $class;
                    }
                } catch (\ErrorException $e) {}
            }
        }
        return $inherited_classes;
    }

    /**
     * Recursively search files in the specified $folder for each installed applications
     *
     * @param string|null $folder
     * @return array
     */
    public static function searchFiles($folder) {
        $folder = trim($folder, DS);

        // Try to get from cache
        $cache_key = 'app_files'.(!empty($folder) ? '.'.str_replace(DS, '.', trim($folder, DS)) : '');
        try {
            $files = \Cache::get($cache_key);
        } catch (\CacheNotFoundException $e) {
            $files = static::searchFilesUncached($folder);
            \Cache::set($cache_key, $files);
        }

        return $files;
    }

    /**
     * Recursively search files in the specified $folder for each installed applications, without cache
     *
     * @param $folder
     * @return array
     */
    protected static function searchFilesUncached($folder) {
        $folder = trim($folder, DS);

        // Search installed applications
        $apps = static::getInstalledApplications();

        // Search files for each installed application
        $files = array();
        foreach ($apps as $app) {
            // Gets the folder path for the current application
            $app_folder_path = Application::get_application_path($app->folder).DS.$folder;
            if (is_dir($app_folder_path)) {
                $paths = \File::read_dir($app_folder_path);
                if (!empty($paths)) {
                    $files[$app->folder] = static::flattenPaths($paths);
                }
            }
        }

        return $files;
    }

    /**
     * Flatten a multidimensional array of paths obtained with \File::read_dir()
     *
     * @param $paths
     * @param array $parents
     * @return array
     */
    protected static function flattenPaths($paths, $parents = array()) {
        $flattened_paths = array();
        foreach ($paths as $dir => $path) {
            if (is_array($path)) {
                $flattened_paths = array_merge($flattened_paths, static::flattenPaths($path, array_merge($parents, array(rtrim($dir, DS)))));
            } else {
                $flattened_paths[] = ltrim(implode(DS, $parents).DS.$path, DS);
            }
        }
        return $flattened_paths;
    }
}
