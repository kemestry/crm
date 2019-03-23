<?php
/**
 * Base Controller
 */

namespace App\Controller;

use Edoceo\Radix\DB\SQL;

class Base extends \OpenTHC\Controller\Base
{
	protected function getContactList()
	{
		$sql = <<<SQL
SELECT contact.*
FROM contact
JOIN crm_contact ON contact.id = crm_contact.contact_id_prime
WHERE crm_contact.company_id_owner = :c0
SQL;
		$arg = array(':c0' => $_SESSION['gid']);
		$res = SQL::fetch_all($sql, $arg);
		return $res;
	}
}
