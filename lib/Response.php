<?php
namespace lib;


class Response {
    private static $instance = null;
    private $statusMap = array(
        200 => 'OK',

        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',

        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',

        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        503 => 'Service Unavailable',
    );

    private $headers = array();
    private $status;

    public function __construct() {
        if (self::$instance) {
            throw new \Exception("Only one Response instance is allowed in each request");
        }
        self::$instance = $this;
        ob_start();
    }

    /**
     * @return Response
     */
    public static function getInstance() {
        if (!isset(self::$instance)) self::$instance = new self();
        return self::$instance;
    }

    private function appendHeader($status) {
        if ($status) $this->status = "{$status} {$this->statusMap[$status]}";
        if (!isset($this->headers['Content-Type'])) $this->header('Content-Type', 'text/html; charset=utf-8');
        header("HTTP/1.1 {$this->status}");
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    public function header($key, $value) {
        if (preg_match("/[\n\r]/", $key . $value)) {
            throw new \Exception('Header should not contains new line (\r,\n)');
        }
        $this->headers[trim($key)] = trim($value);
        return $this;
    }

    public function send($content = '', $status = 200) {
        $this->appendHeader($status);
        ob_flush();//404这类错误客户端只关心HTTP头，不关心内容
        if (is_array($content)) $content = json_encode($content);
        echo $content;
        exit;
    }

    public function redirect($url, $status = 302) {
        if (!in_array($status, array(302, 301))) throw new \Exception('Can not redirect with status:' . $status);
        $this->header('Location', $url);
        $this->send("<a href='{$url}'>{$url}</a>", $status);//默认应该在Location头就跳转，这里以防万一
    }
}