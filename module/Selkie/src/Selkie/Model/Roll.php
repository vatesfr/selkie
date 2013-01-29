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

namespace Selkie\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

final class Roll
{
	/**
	 * @var integer
	 */
	public $id;

	/**
	 * @var string
	 */
	public $creator;

	/**
	 * @var string
	 */
	public $comment;

	/**
	 * @var integer
	 */
	public $duration;

	/**
	 * @var integer
	 */
	public $creation;

	/**
	 * @var null|integer
	 */
	public $activation;

	/**
	 * @var boolean
	 */
	public $printed;

	/**
	 * @var integer
	 */
	public $pfs_id;

	/**
	 *
	 */
	function exchangeArray(array $data)
	{
		foreach (array_keys(get_object_vars($this)) as $field)
		{
			$this->$field = isset($data[$field])
				? $data[$field]
				: null;
		}
	}

	/**
	 * @return array
	 */
	function getArrayCopy()
	{
		return get_object_vars($this);
	}

	/**
	 *
	 */
	function setInputFilter(InputFilterInterface $inputFilter)
	{
		throw new \Exception('not used');
	}

	/**
	 *
	 */
	function getInputFilter()
	{
		if (!$this->_inputFilter)
		{
            $inputFilter = new InputFilter;
            $factory     = new InputFactory;

            $inputFilter->add($factory->createInput(array(
                'name'     => 'id',
                'required' => true,
                'filters'  => array(
                    array('name' => 'Int'),
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'creator',
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 100,
                        ),
                    ),
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'duration',
                'required' => true,
                'filters'  => array(
                    array('name' => 'Int'),
                ),
            )));

            // @todo Either null or date.
            $inputFilter->add($factory->createInput(array(
                'name'     => 'activation',
                'required' => false,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                    array('name' => 'Null'),
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'comment',
                'required' => false,
                'filters'  => array(
                    array('name' => 'StringTrim'),
                ),
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'number',
                'required' => true,
                'filters'  => array(
                    array('name' => 'Int'),
                ),
            )));

            $this->_inputFilter = $inputFilter;
		}
		return $this->_inputFilter;
	}

	/**
	 *
	 */
	protected $_inputFilter;
}
