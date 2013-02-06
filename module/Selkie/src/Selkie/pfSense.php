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

		if (303 != $response['code'])
		{
			return false;
		}

		return json_decode($response['data'], true);
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

		return json_decode($response['data'], true);
	}

	/**
	 * @param string $id
	 *
	 * @return integer The number of minutes left for this voucher.
	 */
	function getVoucherTtl($id)
	{
		return $this->_request(
			'GET',
			$this->_url.'/voucher/'.$id.'/?test'
		);
	}

	/**
	 * @param string $id
	 *
	 * @return integer The number of minutes left for this voucher.
	 */
	function useVoucher($id)
	{
		return $this->_request(
			'GET',
			$this->_url.'/voucher/'.$id
		);
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

		$ctx = stream_context_create(array(
			'http' => array(
				'method'  => $method,
				'content' => $data,
				'header'  => array(
					'AUTH: '.$this->_key,
					'Content-Type: application/x-www-form-urlencoded',
					'Content-Length: '.$len,
				),
				'ignore_errors' => true,
			),
			'ssl' => array(
				'allow_self_signed' => true,
			)
		));

		$data = file_get_contents($url, false, $ctx);


		// @todo Properly handles “HTTP/*” not in first item.
		if (!preg_match(
			',^HTTP/1.[01] ([0-9]+).*$,',
			$http_response_header[0],
			$matches
		))
		{
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
			$_ = explode(':', $header, 2);
			if (2 !== count($_))
			{
				continue;
			}
			$response['headers'][$_[0]] = trim($_[1]);
		}

		return $response;
	}
}
