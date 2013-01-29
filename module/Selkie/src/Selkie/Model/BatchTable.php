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

user Selkie\pfSense;

use Zend\Db\TableGateway\TableGateway;

final class BatchTable
{
	protected $tableGateway;
	protected $pfSense;

	function __construct(TableGateway $tableGateway, pfSense $pfSense)
	{
		$this->tableGateway = $tableGateway;
		$this->pfSense      = $pfSense;
	}

	function getAll($where = null)
	{
		return $this->tableGateway->select($where);
	}

	function get($id)
	{
		$id     = (int) $id;
		$rowset = $this->tableGateway->select(array('id' => $id));
		$row    = $rowset->current();
		if (!$row)
		{
			throw new \Exception("Could not find row $id");
		}
		return $row;
	}

	function save(Batch $batch)
	{
		$fields = array(
			'creator',
			'comment',
			'duration',
			'creation',
			'activation',
			'number',
		);

		$data = array();
		foreach ($fields as $field)
		{
			if (isset($batch->$field))
			{
				$data[$field] = $batch->$field;
			}
		}

		$id = (int) $batch->id;
		if ($id === 0)
		{
			$this->pfSense->createRoll(rand());

			$this->tableGateway->insert($data);
		}
		else
		{
			if ($this->get($id))
			{
				$this->tableGateway->update($data, array('id' => $id));
			}
			else
			{
				throw new \Exception('Form id does not exist');
			}
		}
	}

	function deleteBatch($id)
	{
		$this->tableGateway->delete(array('id' => $id));
	}
}