<?php
/**
 * Pie Chart, Product Type, 90 Days
 */

use Edoceo\Radix\DB\SQL;

$d0 = new DateTime();
$d0->setTimezone(new DateTimeZone('America/Los_Angeles'));
//$d0->sub(new DateInterval('P1D'));

$m00 = clone $d0;

$m01 = clone $m00;
$m01->sub(new DateInterval('P3M'));
//$m01->sub(new DateInterval('P30D'));

$c_value = "crm_transfer_item.package_qty";
$c_value = "crm_transfer_item.full_price";


// $m24 = clone $m00;
// $m24->sub(new DateInterval('P24M'));
$sql = <<<EOS
SELECT crm_transfer_item.meta->'Product'->>'intermediate_type' AS t
, sum( NULLIF($c_value, '0')::float) AS c
FROM crm_transfer
JOIN crm_transfer_item ON crm_transfer.id = crm_transfer_item.transfer_id
WHERE crm_transfer.company_id = :c AND crm_transfer.stat IN (100, 200, 204, 302, 307) AND completed_at >= :d0 AND completed_at <= :d1
GROUP BY crm_transfer_item.meta->'Product'->>'intermediate_type'
ORDER BY c, t
LIMIT 10
EOS;

$arg = array(
	':c' => $_SESSION['Company']['id'],
	':d0' => $m01->format(DateTime::RFC3339),
	':d1' => $m00->format(DateTime::RFC3339),
);
$res = SQL::fetch_all($sql, $arg);

$cht_data = array();
$cht_data[] = array('Product', 'Revenue');
foreach ($res as $rec) {
	$cht_data[] = array($rec['t'], floatval($rec['c']));
}

_exit_json($cht_data);
