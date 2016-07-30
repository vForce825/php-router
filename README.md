#php-router

需要编写rewrite规则，如全站启用，则使用如下规则（nginx）：
```
rewrite ^(/service.*)$ index.php last;
```