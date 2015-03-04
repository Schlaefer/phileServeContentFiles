<?php

namespace Phile\Plugin\Siezi\PhileServeContentFiles;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}

use Phile\Core\Event;
use Phile\Core\Response;
use Phile\Core\Session;
use Phile\Core\Utility;
use Phile\Gateway\EventObserverInterface;
use Phile\Plugin\AbstractPlugin;
use Phile\Repository\Page;

class Plugin extends AbstractPlugin implements EventObserverInterface {

	protected $url;

	protected $registeredEvents = [
		'request_uri' => 'onRequestUri',
		'before_parse_content' => 'onBeforeParseContent',
		'after_parse_content' => 'onAfterParseContent',
	];

	public function __construct() {
		foreach ($this->registeredEvents as $event => $method) {
			Event::registerEvent($event, $this);
		}
	}

	public function on($eventKey, $data = null) {
		$method = $this->registeredEvents[$eventKey];
		$this->{$method}($data);
	}

	protected function onRequestUri($data) {
		$this->url = $data['uri'];
	}

	protected function onAfterParseContent($data) {
		if (!$this->settings['replaceUrlsInHtml']) {
			return;
		}
		$content = $data['content'];
		$baseUrl = Utility::getBaseUrl();
		$cache = [];
		$callback = function ($match) use ($baseUrl, &$cache) {
			$url = $match['url'];
			if (isset($cache[$url])) {
				return $cache[$url];
			}
			$dir = dirname($this->url);
			$path = CONTENT_DIR . $dir . DS . $url;
			if (!file_exists($path)) {
				// check if URL 'foo' is actually 'foo/index'
				$dir = basename($this->url);
				$path = CONTENT_DIR . $dir . DS . $url;
			}
			if (!file_exists($path)) {
				return $match[0];
			}
			$contentUrl = Utility::getBaseUrl() . "/content/$dir/" . $url;
			$cache[$url] = $contentUrl;
			return $cache[$url];
		};
		$content = preg_replace_callback(
			'/((?<=src=")|(?<=href="))(?!(http|\/))(?P<url>.*?\.\w{1,4})(?=")/i',
			$callback,
			$content
		);
		$data['content'] = $content;
	}

	protected function onBeforeParseContent() {
		// figure out 200 request
		$page = (new Page())->findByPath($this->url);
		if ($page) {
			if ($this->settings['resolvePageRelative']) {
				Session::set('siezi.phileContentAsset.lastPage', $this->url);
			}
			return;
		}

		// try root relative file
		$path = CONTENT_DIR . $this->url;
		if ($this->settings['resolveRootRelative'] && file_exists($path)) {
			$this->sendFile($path);
		}

		if (!$this->settings['resolvePageRelative']) {
			return;
		}

		// try to find file relative to current page
		$lastPage = Session::get('siezi.phileContentAsset.lastPage');
		if ($lastPage === null) {
			return;
		}
		$lastPage = explode('/', $lastPage);
		if (count($lastPage)) {
			array_pop($lastPage);
			$lastPage = implode(DS, $lastPage) . DS;
		} else {
			$lastPage = '';
		}
		$path = CONTENT_DIR . $lastPage . $this->url;
		if (file_exists($path)) {
			$this->sendFile($path);
		}

	}

	protected function sendFile($path) {
		$headers = [
			'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + 3600),
			'Content-Type' => $this->getMime($path),
			'Content-Length' => filesize($path)
		];
		$content = file_get_contents($path);

		Event::triggerEvent(
			'siezi\phileTotalCache.command.setPage',
			[
				'url' => $this->url,
				'body' => $content,
				'options' => ['headers' => $headers]
			]
		);

		$response = new Response();
		foreach ($headers as $key => $value) {
			$response->setHeader($key, $value);
		}
		$response
			->setBody($content)
			->send();
		die();
	}

	protected function getMime($path) {
		$finfo = new \finfo(FILEINFO_MIME);
		return $finfo->file($path);
	}

}
