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
final class Roll extends Form
{
	function __construct($name = null, $admin = null)
	{
		// we want to ignore the name passed
		parent::__construct('album');

		$this->setAttribute('method', 'post');

		$this->add(array(
			'name' => 'id',
			'attributes' => array(
				'type'  => 'hidden',
			),
		));

		$values = array(
			1,
			2,
			5,
			10,
		);
		$values = array_combine($values, $values); // keys = values
		$this->add(array(
			'name' => 'number',
			'type'  => 'Zend\Form\Element\Select',
			'options' => array(
				'label' => 'Number',
				'value_options' => $values,
			),
		));

		$values = array(
			30   => '30 minutes',
			60   => '1 hour',
			120  => '2 hours',
			300  => '5 hours',
			1440 => '1 day',
		);
		if ($admin)
		{
			$values += array(
				7200 => '5 days',
			);
		}
		$this->add(array(
			'name' => 'duration',
			'type'  => 'Zend\Form\Element\Select',
			'options' => array(
				'label' => 'Duration',
				'value_options' => $values,
			),
		));
		$this->add(array(
			'name' => 'activated',
			'attributes' => array(
				'type'  => 'Checkbox',
				'disabled' => !$admin,
				'checked'  => true
			),
			'options' => array(
				'label' => 'Activate now?',
			),
		));
		$this->add(array(
			'name' => 'comment',
			'attributes' => array(
				'type'  => 'text',
			),
			'options' => array(
				'label' => 'Comment',
			),
		));
		$this->add(array(
			'name' => 'submit',
			'attributes' => array(
				'type'  => 'submit',
				'value' => 'Add',
			),
		));
	}
}
