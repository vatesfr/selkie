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

use Selkie\pfSense;
use Selkie\Jasper;
use Selkie\Model\Batch;
use Selkie\Model\BatchTable;

use Zend\Authentication\Adapter\Ldap as LdapAuth;
use Zend\Authentication\AuthenticationService;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;

/**
 *
 */
final class Module
{
	/**
	 * return array
	 */
	function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__.'/src/'.__NAMESPACE__,
				),
			),
		);
	}

	/**
	 * @return array
	 */
	function getConfig()
	{
		return require(__DIR__.'/config/module.config.php');
	}

	/**
	 * @return array
	 */
	function getServiceConfig()
	{
		return array(
			'factories' => array(
				'Selkie\Model\BatchTable' =>  function($sm) {
					return new BatchTable(
						$sm->get('Selkie\Model\BatchGateway'),
						$sm->get('Selkie\Model\VoucherGateway'),
						$sm->get('Selkie\pfSense')
					);
				},
				'Selkie\Model\VoucherGateway' => function ($sm) {
					return new TableGateway(
						'voucher',
						$sm->get('Zend\Db\Adapter\Adapter')
					);
				},
				'Selkie\Model\BatchGateway' => function ($sm) {
					$proto = new ResultSet;
					$proto->setArrayObjectPrototype(new Batch);

					return new TableGateway(
						'batch',
						$sm->get('Zend\Db\Adapter\Adapter'),
						null,
						$proto
					);
				},
				'SelkieAuthService' => function ($sm) {
					$conf = $sm->get('Config')['ldap'];
					unset($conf['groupDn'], $conf['adminGroup'], $conf['userGroup']);

					$ldap = new LdapAuth(array($conf));

					return new AuthenticationService(null, $ldap);
				},
				'SelkieSession' => function ($sm) {
					return new \Zend\Session\Container('selkie');
				},
				'Selkie\pfSense' => function ($sm) {
					$conf = $sm->get('Config')['pfsense'];

					return new pfSense($conf['url'], $conf['key']);
				},
				'Selkie\Jasper' => function ($sm) {
					$conf = $sm->get('Config')['jasper'];

					return new Jasper(
						$conf['url'],
						$conf['username'],
						$conf['password'],
						$conf['format'],
						$conf['report']
					);
				},
			),
		);
	}
}