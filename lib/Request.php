<?php
namespace lib;

/**
 * Class Request
 * @package lib
 * @property \stdClass $apiData
 */
class Request {
    protected static $instance = null;
    protected static $params = array();
    protected static $path = array();
    protected static $uri = null;

    public function __construct() {
        if (isset(self::$instance)) {
            throw new \Exception("Only one Request instance is allowed in each request");
        }
        self::$instance = $this;
        //过滤不安全字符
        $_GET = $this->formatInput($_GET);
        $_POST = $this->formatInput($_POST);
        $_REQUEST = array(); //for safe
        if (!self::uri()) {
            self::$uri = ($pos = strpos($_SERVER['REQUEST_URI'], '?')) === false ? $_SERVER['REQUEST_URI'] : substr($_SERVER['REQUEST_URI'], 0, $pos); //去掉GET字符串
        }
        $_SERVER['QUERY_STRING'] = http_build_query($_GET);
        $_SERVER['REQUEST_URI'] = self::$uri . ($_GET ? '?' . $_SERVER['QUERY_STRING'] : '');
        self::$path = explode('/', trim($this->uri(), '/'));
        //将HTTP请求变量直接保存下来
        foreach (array_merge($_GET, $_POST) as $key => $value) $this->$key = $value;
    }

    /**
     * @return Request
     */
    public static function getInstance() {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    public function uri() {
        return self::$uri;
    }

    public function host() {
        return $_SERVER['HTTP_HOST'];
    }

    public function referer() {
        return (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
    }

    public function ua() {
        return (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    }

    public function path($offset = null) {
        if (is_null($offset)) {
            return self::$path;
        } else {
            return isset(self::$path[$offset]) ? self::$path[$offset] : null;
        }
    }

    public function post($name = null, $default = null) {
        return is_null($name) ? $_POST : (isset($_POST[$name]) ? $_POST[$name] : $default);
    }

    public function get($name = null, $default = null) {
        return is_null($name) ? $_GET : (isset($_GET[$name]) ? $_GET[$name] : $default);
    }

    public function cookie($name = null, $default = null) {
        return is_null($name) ? $_COOKIE : (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
    }

    public function method() {
        return isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : '';
    }

    public function isGet() {
        return $this->method() == 'get';
    }

    public function isPost() {
        return $this->method() == 'post';
    }

    public function isPut() {
        return $this->method() == 'put';
    }

    public function isDelete() {
        return $this->method() == 'delete';
    }

    public function params() {
        return self::$params;
    }

    public function remote_ip() {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];	//Direct IP
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = "127.0.0.1";	//for CLI
        }
        $ip_segment = explode(',', $ip);
        return $ip_segment[0];
    }

    public function __set($name, $value) {
        self::$params[$name] = $value;
        if (is_null($value)) unset(self::$params[$name]);
    }

    public function __get($name) {
        return isset(self::$params[$name]) ? self::$params[$name] : null;
    }

    protected function formatInput($data) {
        if (!is_array($data)) {
            return trim(stripslashes($data));
        } else {
            $formatted = array();
            foreach ($data as $key => $value) {
                $formatted[$this->formatInput($key)] = $this->formatInput($value);
            }
            unset($formatted['']);
            return array_filter($formatted, function($v) {
                if(is_string($v)) {
                    return strlen($v) > 0;
                } else {
                    return !empty($v);
                }
            });
        }
    }
}