<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
	'db' => array(
		'driver'         => 'Pdo',
		'dsn'            => 'pgsql:host=localhost;dbname=selkie',
		'driver_options' => array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
		),
	),
	'service_manager' => array(
		'factories' => array(
			'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
		),
	),

	'ldap' => array(
		'host'   => 'ldap.vates.fr',
		'useSsl' => true,
		'bindRequiresDn' => true,
		'baseDn' => 'ou=people,dc=vates,dc=fr',
		'accountDomainName' => 'vates.fr',

		'groupDn' => 'ou=group,dc=vates,dc=fr',
		'adminGroup' => 'direction',
		'userGroup'  => 'Services',
	),

	'pfsense' => array(
		'host' => 'localhost',
		'username' => 'admin',
		'password' => 'admin',
	),
);
