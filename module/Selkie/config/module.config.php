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

return array(

	'controllers' =>  array(
		'invokables' => array(
			'Selkie\Controller\Selkie' => 'Selkie\Controller\Selkie',
		),
	),

	'router' => array(
		'routes' => array(
			'selkie' => array(
				'type' => 'segment',
				'options' => array(
					'route'	=> '/selkie[/:action][/:id]',
					'constraints' => array(
						'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
						'id'	 => '[0-9]+',
					),
					'defaults' => array(
						'controller' => 'Selkie\Controller\Selkie',
						'action'	 => 'index',
					),
				),
			),
		),

	),

	'view_manager' => array(
		'template_path_stack' => array(
			'selkie' => __DIR__ . '/../view',
		),
	),
);
