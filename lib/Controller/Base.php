<?php
/**
 * Base Controller
 */

namespace App\Controller;

use Edoceo\Radix\DB\SQL;

class Base extends \OpenTHC\Controller\Base
{
	protected function getContactList($com_id=null)
	{
		if (empty($com_id)) {
			$com_id = $_SESSION['gid'];
		}

		// @todo USE crm_contact_full view

		$sql = <<<SQL
SELECT contact.*
FROM contact
JOIN crm_contact ON contact.id = crm_contact.contact_id_prime
WHERE crm_contact.company_id_owner = :c0
UNION ALL
SELECT contact.*
FROM contact
WHERE contact.company_id = :c0
SQL;

		$sql = <<<SQL
SELECT contact.*
FROM contact
WHERE contact.company_id = :c0
SQL;


		$arg = array(':c0' => $com_id);

		if ($com_id == $_SESSION['gid']) {
			$sql.= ' AND contact.company_id = :c1';
			$arg[':c1'] = $com_id;
		}


		$res = SQL::fetch_all($sql, $arg);
		return $res;
	}

}
