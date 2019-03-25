<?php
/**
 * Settings
 */

namespace App\Controller;

use Edoceo\Radix\DB\SQL;

class Settings extends \App\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$C = new \OpenTHC\Company($_SESSION['Company']);

		switch ($_POST['a']) {
		case 'save':
			$C->opt('cost_per_hour', floatval($_POST['cost_per_hour']));
			$C->opt('cost_per_mile', floatval($_POST['cost_per_mile']));
			break;
		}

		$data = array();

		$data['License'] = $_SESSION['License'];

		$data['cost_per_hour'] = $C->opt('cost_per_hour');
		if (empty($data['cost_per_hour'])) {
			$data['cost_per_hour'] = '20.00';
		}

		$data['cost_per_mile'] = $C->opt('cost_per_mile');
		if (empty($data['cost_per_mile'])) {
			$data['cost_per_mile'] = 0.75;
		}

		$data['contact_list'] = $this->getContactList($_SESSION['gid']);

		return $this->_container->view->render($RES, 'page/settings/home.html', $data);
	}
}
