<?php
/**
	Inflate the JSON inbound
*/

namespace App\Middleware;

class Menu
{
	private $_container;

	function __construct($c)
	{
		$this->_container = $c;
	}


	function __invoke($REQ, $RES, $NMW)
	{
		$menu = array(
			'home_link' => '/',
			'home_html' => '<i class="fas fa-home"></i>',
			'show_search' => true,
			'main' => array(),
			'page' => array(
				array(
					'link' => '/auth',
					'html' => '<i class="fas fa-sign-in-alt"></i>',
				)
			),
		);

		$_SESSION['uid'] = 1;
		if (!empty($_SESSION['uid'])) {

			$menu['home_link'] = '/home';

			$menu['main'][] = array(
				'link' => '/transfer',
				'html' => '<i class="fas fa-truck-loading"></i> Transfers',
			);

			$menu['main'][] = array(
				'link' => '/client',
				'html' => '<i class="fas fa-users"></i> Clients',
			);

			$menu['main'][] = array(
				'link' => '/vendor',
				'html' => '<i class="fas fa-truck"></i> Vendors',
			);

			// $menu['main'][] = array(
			// 	'link' => '/inventory',
			// 	'html' => 'Inventory',
			// );

			$menu['main'][] = array(
				'link' => '/report',
				'html' => '<i class="fas fa-chart-bar"></i> Reports',
			);

			$menu['page'] = array(
				array(
					'link' => '/settings',
					'html' => '<i class="fas fa-cogs"></i>'
				),
				array(
					'link' => '/auth/shut',
					'html' => '<i class="fas fa-power-off"></i>',
				)
			);
		}

		// $menu['main'][] = array(
		// 	'link' => 'https://directory.openthc.com/license/recent',
		// 	'html' => '<i class="fas fa-bolt" style="color:#e00;"></i>',
		// );

		$this->_container->view['menu'] = $menu;

		$RES = $NMW($REQ, $RES);

		return $RES;

	}

}

/* Determine Selected ?
{#
<?php
$menu_list = App_Menu::getMenu('page');
foreach ($menu_list as $menu) {

	if (empty($menu['id'])) {
		$menu['id'] = 'menu-' . trim(preg_replace('/[^\w]+/', '-', $menu['link']), '-');
	}

	echo '<li><a ';

	if ($menu['link'] == substr(Radix::$path, 0, strlen($menu['link']))) { // == substr($menu['link'], $l)) {
		echo ' class="hi"';
	}

	echo ' id="' . $menu['id'] . '"';
	echo ' href="' . $menu['link'] . '">';
	echo $menu['name'];
	echo '</a></li>';

}

?>
#}

*/
