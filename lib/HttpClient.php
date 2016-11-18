<?php
namespace lib;

class HttpClient {

	public static function get($url, $timeout = 1, $headers = array()) {
		$opts = array(
			CURLOPT_HTTPGET => true,
		);

		if ($headers) {
			$opts[CURLOPT_HTTPHEADER] = $headers;
		}
		return self::request($url, $opts, $timeout);
	}

	public static function browserGet($url, $timeout = 1) {
		$headers = array(
			"User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15",
		);
		return self::get($url, $timeout, $headers);
	}

	public static function post($url, $post_data, $timeout = 1, $headers = array()) {
		$opts = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_data,
		);

		if ($headers) {
			$opts[CURLOPT_HTTPHEADER] = $headers;
		}

		return self::request($url, $opts, $timeout);
	}

	public static function size($url) {
		// Assume failure.
		$result = -1;

		$curl = curl_init($url);

		// Issue a HEAD request and follow any redirects.
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 20);

		$data = curl_exec($curl);

		if ($data) {
			$content_length = "unknown";
			$status = "unknown";

			if (preg_match("/^HTTP\/1\.[01] (\d\d\d)/", $data, $matches)) {
				$status = (int)$matches[1];
			}

			if (preg_match("/Content-Length: (\d+)/", $data, $matches)) {
				$content_length = (int)$matches[1];
			}

			// http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
			if ($status == 200 || ($status > 300 && $status <= 308)) {
				$result = $content_length;
			} else if ($status == 404) {
				$result = 0;
			}
		}

		curl_close($curl);

		return $result;
	}

	protected static function request($url, $opts = array(), $timeout) {
		$method = 'GET';
		if (isset($opts[CURLOPT_HTTPGET]) && $opts[CURLOPT_HTTPGET]) $method = "GET";
		elseif (isset($opts[CURLOPT_POST]) && $opts[CURLOPT_POST]) $method = "POST";
		elseif (isset($opts[CURLOPT_CUSTOMREQUEST])) $method = $opts[CURLOPT_CUSTOMREQUEST];

		$ch = self::init($url, $method);

		// Begin: set options
		$opts += array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_ENCODING => '',    //enable gzip
			CURLOPT_URL => $url,
		);

		if ($timeout < 1) {
			$opts[CURLOPT_TIMEOUT_MS] = $opts[CURLOPT_CONNECTTIMEOUT_MS] = intval($timeout) * 1000;
		} else {
			$opts[CURLOPT_TIMEOUT] = $opts[CURLOPT_CONNECTTIMEOUT] = intval($timeout);
		}
		curl_setopt_array($ch, $opts);
		// End: set options

		$result = curl_exec($ch);

		if (isset($_GET['debug'])) var_dump($result, curl_error($ch));

		return curl_errno($ch) ? false : $result;
	}

	private static $pool = array();

	private static function init($url, $function_name) {
		if (!preg_match("/^(http[s]?:\/\/[^\/]+\/)/i", $url, $match)) {
			throw new \Exception('Only Http(s) Protocal supported! Url: ' . $url);
		}
		/*
		 * Function + Protocal + 域名 + 端口作为key，最大可能地复用TCP连接。
		 * 不同的HTTP请求类型不要公用curl实例，因为一些设置参数不一致，会导致请求类型之间的相互穿越
		 * */
		$key = $function_name . '|' . $match[1];

		if (!isset(self::$pool[$key])) {
			//实例过多的时候回收一次
			if (count(self::$pool) > 100) {
				foreach (self::$pool as $_ch) {
					curl_close($_ch);
				}
				self::$pool = array();
			}
			//新建curl session
			self::$pool[$key] = curl_init();
		}
		return self::$pool[$key];
	}
}
