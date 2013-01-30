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

use Selkie\pfSense;

use Zend\Db\TableGateway\TableGateway;

final class RollTable
{
	protected $_tblRoll;
	protected $_tblVoucher;
	protected $_pfSense;

	function __construct(
		TableGateway $tblRoll,
		TableGateway $tblVoucher,
		pfSense $pfSense
	)
	{
		$this->_tblRoll   = $tblRoll;
		$this->_tblVoucher = $tblVoucher;
		$this->_pfSense    = $pfSense;
	}

	function getAll($where = null)
	{
		return $this->_tblRoll->select($where);
	}

	function get($id)
	{
		$id     = (int) $id;
		$rowset = $this->_tblRoll->select(array('id' => $id));
		$row    = $rowset->current();
		if (!$row)
		{
			throw new \Exception("Could not find row $id");
		}
		return $row;
	}

	function save(Roll $roll, $number = 1)
	{
		$data = array();
		foreach (array_keys(get_object_vars($roll)) as $field)
		{
			if (('id' !== $field)
			    && isset($roll->$field))
			{
				$data[$field] = $roll->$field;
			}
		}

		$id = (int) $roll->id;
		if ($id === 0)
		{
			$tries = 10;
			do
			{
				$pfs_id = mt_rand(0, 65535);
				$roll = $this->_pfSense->createRoll(
					$pfs_id,
					$number,
					$data['duration'],
					$data['comment']
				);
			} while (!$roll && --$tries);
			if (!$tries)
			{
				throw new \Exception('failed to create the roll');
			}

			$data['pfs_id'] = $pfs_id;
			$this->_tblRoll->insert($data);

			// @todo Bug in Zend Framework 2.
			$id = $this->_tblRoll->getAdapter()->getDriver()->getConnection()->getLastGeneratedValue('roll_id_seq');

			foreach ($roll['vouchers'] as $voucher)
			{
				$this->_tblVoucher->insert(array(
					'roll_id' => $id,
					'id'       => $voucher,
				));
			}
		}
		else
		{
			if ($this->get($id))
			{
				$this->_tblRoll->update($data, array('id' => $id));
			}
			else
			{
				throw new \Exception('Form id does not exist');
			}
		}
	}

	function deleteRoll($roll)
	{
		if (is_numeric($roll))
		{
			$roll = $this->get($roll);
		}

		$this->_tblVoucher->delete(array('roll_id' => $roll->id));
		$this->_tblRoll->delete(array('id' => $roll->id));

		$this->_pfSense->deleteRoll($roll->pfs_id);
	}
}
