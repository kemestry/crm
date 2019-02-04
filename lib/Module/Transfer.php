<?php
/**
 * Module for Transfer Routes
*/

namespace App\Module;

class Transfer extends \OpenTHC\Module\Base
{
	function __invoke($a)
	{
		$a->get('', 'App\Controller\Transfer\Home');

		$a->get('/{guid}', 'App\Controller\Transfer\View');
		$a->post('/{guid}', 'App\Controller\Transfer\View');

		$a->post('/{guid}/sync', 'App\Controller\Transfer\Sync');

	}
}
