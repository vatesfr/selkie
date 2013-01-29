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

namespace Selkie\Controller;

use Selkie\Form\Roll as RollForm;
use Selkie\Form\LogIn as LogInForm;
use Selkie\Model\Roll;

use Zend\Db\Sql\Select;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

/**
 * @todo Handle entering a voucher id.
 * @todo Handle clean up.
 *
 * @todo Test: Handle printing a voucher through Jasper.
 */
final class Selkie extends AbstractActionController
{
	function indexAction()
	{
		$admin    = $this->_isAdmin();
		$identity = $this->_getIdentity();
		$where = function (Select $select) use ($admin, $identity) {
			if (!$admin)
			{
				$select->where(array(
					'creator' => $identity,
				));
			}
			$select->limit(100);
		};

		return array(
			'isAdmin' => $admin,
			'rolls'  => $this->getTable()->getAll($where),
		);
	}

	function addAction()
	{
		$form = new RollForm(null, $this->_isAdmin());

		$request = $this->getRequest();
		if ($request->isPost())
		{
			$roll = new Roll;
			$form->setInputFilter($roll->getInputFilter());

			$data = $request->getPost();
			$data['creator'] = $this->_getIdentity();
			$form->setData($data);

			if ($form->isValid())
			{
				$data = $form->getData();
				if (isset($data['activated']) || !$this->_isAdmin())
				{
					$data['activation'] = date('c');
				}
				else
				{
					$data['activation'] = null;
				}
				unset($data['activated']);

				// @todo Activates the vouchers if necessary.

				$roll->exchangeArray($data);
				$this->getTable()->save($roll, $data['number']);

				return $this->redirect()->toRoute('selkie');
			}
		}

		return array('form' => $form);
	}

	function deleteAction()
	{
		$id = $this->params()->fromRoute('id');
		if ((null === $id)
		    || !$this->_isAdmin())
		{
			return $this->redirect()->toRoute('selkie');
		}

		$request = $this->getRequest();
		if ($request->isPost())
		{
			$del = $request->getPost('del', 'No');

			if ($del === 'Yes')
			{
				// @todo Why? Shouldn't it be in the URL?
				$id = (int) $request->getPost('id');
				$this->getTable()->deleteRoll($id);
			}

			return $this->redirect()->toRoute('selkie');
		}

		return array(
			'id'      => $id,
			'roll' => $this->getTable()->get($id)
		);
	}

	function printAction()
	{
		$id = $this->params()->fromRoute('id');
		if (null === $id)
		{
			return $this->redirect()->toRoute('selkie');
		}

		$roll  = $this->getTable()->get($id);
		$jasper = $this->getServiceLocator()->get('Selkie\Jasper');

		$pdf = $jasper->requestReport(get_object_vars($roll));

		$roll->printed = true;
		$this->getTable()->save($roll);

		header('Content-Type: application/pdf');
		echo $pdf;
		exit;
	}

	function searchAction()
	{
		$id = $this->getRequest()->getPost('id');

		if (!$id)
		{
			return $this->redirect()->toRoute('selkie');
		}

		$voucher = $this->getServiceLocator()
			->get('Selkie\Model\VoucherGateway')
			->select(array('id' => $id))
			->current();

		if (!$voucher)
		{
			return $this->redirect()->toRoute('selkie');
		}

		return $this->redirect()->toRoute('selkie', array(
			'action' => 'view',
			'id'     => $voucher['roll_id'],
		));
	}

	function viewAction()
	{
		$id = $this->params()->fromRoute('id');
		if (null === $id)
		{
			return $this->redirect()->toRoute('selkie');
		}

		$roll = $this->getTable()->get($id);
		$vouchers = $this->getServiceLocator()
			->get('Selkie\Model\VoucherGateway')
			->select(array('roll_id' => $id));

		return array(
			'roll'    => $roll,
			'vouchers' => $vouchers,
		);
	}

	function cleanAction()
	{
		$where = function (Select $select) {
			// SQL to get all past rolles.
			$select->where('activation + CAST(duration || \' minutes\' AS INTERVAl) < NOW()');
		};

		$bg = $this->getServiceLocator()->get('Selkie\Model\RollTable');
		foreach ($bg->getAll($where) as $roll)
		{
			$bg->deleteRoll($roll);
		}

		exit;
	}

	function loginAction()
	{
		$form    = new LogInForm;
		$request = $this->getRequest();
		if ($request->isPost())
		{
			$form->setData($request->getPost());

			if ($form->isValid())
			{
				$sl = $this->getServiceLocator();

				$auth = $sl->get('SelkieAuthService');

				$auth->getAdapter()
					->setIdentity($request->getPost('username'))
					->setCredential($request->getPost('password'))
					;

				$result = $auth->authenticate();
				if ($result->isValid())
				{
					$conf = $sl->get('Config')['ldap'];
					if (!isset($conf['groupDn'], $conf['adminGroup'], $conf['userGroup']))
					{
						throw new Exception('Invalid LDAP configuration');
					}

					$ldap = $auth->getAdapter()->getLdap();

					$usr = $ldap->getBoundUser();
					$uid = $request->getPost('username');
					$gid = $ldap->getEntry($usr, array('gidnumber'))['gidnumber'][0];

					$filter =
						'(&(objectClass=posixGroup)(|(memberUid='.
						\Zend\Ldap\Filter::escapeValue($uid).
						')(gidNumber='.$gid.')))';

					$allowed = $admin = false;
					foreach (
						$ldap->search(
							$filter,
							$conf['groupDn'],
							\Zend\Ldap\Ldap::SEARCH_SCOPE_ONE,
							array('cn')
						)
						as
						$group
					)
					{
						$group = $group['cn'][0];
						if ($group === $conf['adminGroup'])
						{
							$allowed = $admin = true;
							break;
						}
						if ($group === $conf['userGroup'])
						{
							$allowed = true;
							$break;
						}
					}

					if ($allowed)
					{
						$sl->get('SelkieSession')['admin'] = $admin;
						return $this->redirect()->toRoute('selkie');
					}
					$auth->clearIdentity();
				}
			}
		}

		return array(
			'form' => $form,
		);
	}

	function logOutAction()
	{
		$auth = $this->getServiceLocator()->get('SelkieAuthService')->clearIdentity();
		return $this->redirect()->toRoute('selkie');
	}

	//--------------------------------------

	/**
	 * @return Selkie\Model\RollTable
	 */
	function getTable()
	{
		if (!$this->_table)
		{
			$this->_table = $this
				->getServiceLocator()
				->get('Selkie\Model\RollTable')
				;
		}
		return $this->_table;
	}

	/**
	 *
	 */
	function onDispatch(MvcEvent $e)
	{
		$route = $e->getRouteMatch();
		$auth  = $this->getServiceLocator()->get('SelkieAuthService');

		if ('logIn' === $route->getParam('action'))
		{
			if ($auth->hasIdentity())
			{
				// Already authenticated, redirects to the index.
				return $this->redirect()->toRoute('selkie');
			}
		}
		else
		{
			if (!$auth->hasIdentity())
			{
				// Not authenticated, redirects to the logIn page.
				return $this->redirect()->toRoute('selkie', array(
					'action' => 'logIn',
				));
			}
		}

		$actionResponse = parent::onDispatch($e);

		$result = $e->getResult();
		if (is_array($result))
		{
			$result['currentUser'] = $auth->getIdentity();
		}
		elseif ($result instanceof \Zend\View\Model\ViewModel)
		{
			$result->currentUser = $auth->getIdentity();
		}
		$e->setResult($result);

		return $actionResponse;
	}

	/**
	 * @var Selkie\Model\RollTable
	 */
	protected $_table;

	/**
	 *
	 */
	private function _isAdmin()
	{
		return (bool) $this->getServiceLocator()->get('SelkieSession')['admin'];
	}

	/**
	 *
	 */
	private function _getIdentity()
	{
		return $this->getServiceLocator()->get('SelkieAuthService')->getIdentity();
	}
}
