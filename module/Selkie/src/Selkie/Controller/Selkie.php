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
			$select->limit(100)->order('creation');
		};

		return array(
			'isAdmin' => $admin,
			'rolls'   => $this->_('Selkie\Model\RollTable')->getAll($where),
		);
	}

	function createAction()
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
				$this->_('Selkie\Model\RollTable')->save($roll, $data['number']);

				return $this->redirect()->toRoute('selkie', array(
					'action' => 'view',
					'id'     => $roll->id
				));
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

		$rolls = $this->_('Selkie\Model\RollTable');

		$request = $this->getRequest();
		if ($request->isPost())
		{
			$del = $request->getPost('del', 'No');

			if ($del === 'Yes')
			{
				// @todo Why? Shouldn't it be in the URL?
				$id = (int) $request->getPost('id');
				$rolls->delete($id);
			}

			return $this->redirect()->toRoute('selkie');
		}

		return array(
			'id'   => $id,
			'roll' => $rolls->get($id)
		);
	}

	function printAction()
	{
		$id = $this->params()->fromRoute('id');
		if (null === $id)
		{
			return $this->redirect()->toRoute('selkie');
		}

		$vouchers = $this->_('Selkie\Model\VoucherGateway');
		$voucher  = $vouchers->select(array('id' => $id))->current();
		if (!$voucher)
		{
			return $this->redirect()->toRoute('selkie');
		}

		$roll = $this->_('Selkie\Model\RollTable')->get($voucher['roll_id']);

		$pdf = $this->_('Selkie\Jasper')->requestReport(array(
			'activation' => $roll->activation,
			'comment'    => $roll->comment,
			'creation'   => $roll->creation,
			'creator'    => $roll->creator,
			'duration'   => $roll->duration,
			'roll'       => $roll->pfs_id,
			'voucher'    => $voucher['id'],
		));

		// Updates the voucher if necessary.
		if (!$voucher['printed'])
		{
			$vouchers->update(
				array('printed' => true),
				array('id'      => $voucher['id'])
			);
		}

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

		$voucher = $this->_('Selkie\Model\VoucherGateway')
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

		$roll = $this->_('Selkie\Model\RollTable')->get($id);
		$vouchers = $this->_('Selkie\Model\VoucherGateway')
			->select(
				function (Select $select) use ($id) {
					$select->where(array('roll_id' => $id))->order('id');
				}
			);

		return array(
			'isAdmin'  => $this->_isAdmin(),
			'roll'     => $roll,
			'vouchers' => $vouchers,
		);
	}

	function cleanAction()
	{
		$where = function (Select $select) {
			// SQL to get all past rolles.
			$select->where('activation + CAST(duration || \' minutes\' AS INTERVAl) < NOW()');
		};

		$bg = $this->_('Selkie\Model\RollTable');
		foreach ($bg->getAll($where) as $roll)
		{
			$bg->delete($roll);
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
				$auth = $this->_('SelkieAuthService');

				$auth->getAdapter()
					->setIdentity($request->getPost('username'))
					->setCredential($request->getPost('password'))
					;

				$result = $auth->authenticate();
				if ($result->isValid())
				{
					$conf = $this->_('Config')['ldap'];
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
						$this->_('SelkieSession')['admin'] = $admin;
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
		$auth = $this->_('SelkieAuthService')->clearIdentity();
		return $this->redirect()->toRoute('selkie');
	}

	//--------------------------------------

	/**
	 *
	 */
	function onDispatch(MvcEvent $e)
	{
		$route = $e->getRouteMatch();
		$auth  = $this->_('SelkieAuthService');

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
	 *
	 */
	private function _($service)
	{
		return $this->serviceLocator->get($service);
	}

	/**
	 *
	 */
	private function _isAdmin()
	{
		return (bool) $this->_('SelkieSession')['admin'];
	}

	/**
	 *
	 */
	private function _getIdentity()
	{
		return $this->_('SelkieAuthService')->getIdentity();
	}
}
