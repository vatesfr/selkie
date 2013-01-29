<?php
/**
 * This file is a part of Selkie.
 *
 * Selkie is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Selkie is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Selkie. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Julien Fontanet <julien.fontanet@vates.fr>
 * @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GPLv3
 *
 * @package Selkie
 */

namespace Selkie;

/**
 *
 */
final class pfSense
{
	/**
	 *
	 */
	function __construct($url, $key)
	{
		$this->_url = $url;
		$this->_key = $key;
	}

	/**
	 *
	 */
	function createRoll($id, $count, $duration, $comment = '')
	{
		$response = $this->_request(
			'POST',
			$this->_url.'/roll',
			array(
				'number'  => $id,
				'count'   => $count,
				'minutes' => $duration,
				'comment' => $comment,
			)
		);

		return $response;
	}

	/**
	 *
	 */
	function getRoll($id)
	{
		$response = $this->_request(
			'GET',
			$this->_url.'/roll/'.$id
		);

		return $response['data'];
	}

	/**
	 *
	 */
	function deleteRoll($id)
	{
		$response = $this->_request(
			'DELETE',
			$this->_url.'/roll/'.$id
		);

		return (204 == $response['code']);
	}

	/**
	 * @var string
	 */
	private $_url;

	/**
	 * @var string
	 */
	private $_key;

	/**
	 *
	 */
	private function _request($method, $url, $data = null)
	{
		$data = http_build_query(null === $data ? array() : $data);
		$len  = strlen($data);

		$ctx = stream_context_create(array('http' => array(
			'method'  => $method,
			'content' => $data,
			'header'  => array(
				'AUTH: '.$this->_key,
				'Content-Type: application/x-www-form-urlencoded',
				'Content-Length: '.$len,
			),
			'ignore_errors' => true,
		)));

		$data = file_get_contents($url, false, $ctx);

		if (!preg_match(
			',^HTTP/1.1 ([0-9]+).*$,',
			$http_response_header[0],
			$matches
		))
		{
			// @todo remove
			var_export(array($url, $data, $matches));
			throw new \Exception('invalid HTTP response');
		}
		unset($http_response_header[0]);

		$response = array(
			'code'    => $matches[1],
			'headers' => array(),
			'data'    => $data
		);
		foreach ($http_response_header as $header)
		{
			list($key, $value) = explode(',', $header, 2);
			$response['headers'][$key] = $value;
		}

		return $response;
	}
}
