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

namespace Selkie\Form;

use Zend\Form\Form;

/**
 *
 */
final class LogIn extends Form
{
	function __construct($name = null)
	{
		// we want to ignore the name passed
		parent::__construct('logIn');

		$this->setAttribute('method', 'post');

		$this->add(array(
			'name' => 'username',
			'attributes' => array(
				'type'  => 'text',
			),
			'options' => array(
				'label' => 'Username',
			),
		));
		$this->add(array(
			'name' => 'password',
			'attributes' => array(
				'type'  => 'password',
			),
			'options' => array(
				'label' => 'Password',
			),
		));
		$this->add(array(
			'name' => 'submit',
			'attributes' => array(
				'type'  => 'submit',
				'value' => 'Log in',
				'id' => 'submitbutton',
			),
		));
	}
}
