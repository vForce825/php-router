<?php
include_once "init.php";

$request = \lib\Request::getInstance();
$response = \lib\Response::getInstance();

$path = $request->path();
if (!$path[count($path)-1]) unset($path[count($path)-1]);
$pathLength = count($path);

//两次Routing，第一次按照/service/namespace/controller的标准去尝试解析一次，如果失败，第二次按照/service/namespace/controller/method再尝试解析，如果都失败，那么返回404
$namespace = implode("\\", array_slice($path, 1, $pathLength - 2));//service为当前目录，不计入routing部分
$namespace = $namespace ? $namespace . "\\" : "";
$controllerName = "\\controller\\{$namespace}" . (ucfirst($path[$pathLength-1] ?: "Home")) . "_Controller";
$method = "index";
if (!is_callable(array($controllerName, $method))) {
    //尝试进行第二种解析/service/namespace/controller/method
    $namespace = implode("\\", array_slice($path, 1, $pathLength - 3));//service为当前目录，不计入routing部分
    $namespace = $namespace ? $namespace . "\\" : "";
    $controllerName = "\\controller\\{$namespace}" . (ucfirst($path[$pathLength-2] ?: "Home")) . "_Controller";
    $method = $path[$pathLength-1] ?: "index";
    if (!is_callable(array($controllerName, $method))) {
        //如果指定的method不存在，那么尝试使用index方法
        $method = "index";
    }
    if (!is_callable(array($controllerName, $method))) {
        //如果index也无法访问，则返回404
        $response->rawSend("Not Found", 404);
    }
}

$controller = new $controllerName($method);
$controller->$method();