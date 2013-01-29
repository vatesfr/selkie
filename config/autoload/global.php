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
	'service_manager' => array(
		'factories' => array(
			'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
		),
	),

	'db' => array(
		'driver'         => 'Pdo',
		'dsn'            => 'pgsql:host=localhost;dbname=selkie',
		'driver_options' => array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
		),
		'username'       => 'selkie',
		'password'       => 'selkie',
	),

	'ldap' => array(
		'host'              => '',
		'useSsl'            => true,
		'bindRequiresDn'    => true,
		'baseDn'            => '',
		'accountDomainName' => '',

		'groupDn'    => '',
		'adminGroup' => 'selkie-admin',
		'userGroup'  => 'selkie-user',
	),

	'pfsense' => array(
		'url' => 'https://pfsense.example.net/cp.php',
		'key' => 'password',
	),

	'jasper' => array(
		'url'      => 'http://jasper.example.net',
		'username' => 'user',
		'password' => 'password',

		'format'   => '', // @todo
		'report'   => '', // @todo
	),
);
