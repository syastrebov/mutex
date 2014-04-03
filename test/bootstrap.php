<?php

/**
 * PHP-Erlang Mutex
 * Сервис блокировок для обработки критических секций
 *
 * @category Mutex
 * @package  Mutex
 * @author   Sergey Yastrebov <serg.yastrebov@gmail.com>
 * @link     https://github.com/syastrebov/mutex
 */

spl_autoload_register(function($className)
{
    $classPath  = explode('\\', $className);
    $sourceDirs = array('src', 'test');
    $moduleDirs = array(__DIR__ . '/../', __DIR__ . '/../module/');

    if (count($classPath) > 1) {
        foreach ($moduleDirs as $moduleDir) {
            foreach ($sourceDirs as $dir) {
                $filePath = $moduleDir . $dir . '/' . str_replace('\\', '/', $className) . '.php';

                if (file_exists($filePath)) {
                    require_once $filePath;
                }
            }
        }
    }
});