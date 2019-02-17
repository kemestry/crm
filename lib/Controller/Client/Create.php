<?php
/**
 * Client Home
 */

namespace App\Controller\Client;

use Edoceo\Radix\DB\SQL;

class Create extends \OpenTHC\Controller\Base
{
	function __invoke($REQ, $RES,$ARG)
	{
		$data = array();

		switch ($_POST['a']) {
		case 'create':
			$L0 = new \OpenTHC\License($_POST['license_id']);
			$L1 = \App\License::import($L0);
			_exit_text('Saved');
		}


		return $this->_container->view->render($RES, 'page/client/create.html', $data);
	}
}
