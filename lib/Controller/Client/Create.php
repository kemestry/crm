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
		return $this->_container->view->render($RES, 'page/client/create.html', $data);
	}
}
