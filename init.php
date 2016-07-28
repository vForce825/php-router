<?php
define('ROOT', __DIR__);
define('IS_CLI', PHP_SAPI == 'cli');

//根据namespace自动加载，没有namespace则去$lib_folders指定的默认目录去寻找
spl_autoload_register(function ($class) {
    $className = ltrim($class, '\\');
    $fileName  = '';
    $namespace = null;
    $lib_folders = ['lib', 'controller', 'interface'];
    if ($lastNsPos = strrpos($className, '\\')) {
        $namespace = substr($className, 0, $lastNsPos);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= $className . '.php';
    if ($namespace) {
        $path = ROOT.DIRECTORY_SEPARATOR.$fileName;
        if (file_exists($path)) require $path;
        else return false;
    } else {
        //非命名空间下的类，尝试去默认目录下寻找
        foreach ($lib_folders as $folder) {
            if (file_exists(ROOT . "/{$folder}/{$className}.php")) {
                require ROOT . "/{$folder}/{$className}.php";
                break;
            }
        }
    }
});

//todo:生产环境线上错误自动报警  ErrorHandler：set_exception_handler、set_error_handler、register_shutdown_function