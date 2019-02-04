<?php
/**
 * Settings
*/

namespace App\Controller;

use Edoceo\Radix\DB\SQL;

class Settings extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$data = array();

		$data['License'] = $_SESSION['License'];

		return $this->_container->view->render($RES, 'page/settings/home.html', $data);
	}
}
