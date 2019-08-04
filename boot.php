<?php
/**
 * CRM Bootstrap
 */

use Edoceo\Radix\DB\SQL;

define('APP_NAME', 'OpenTHC | Fire');
define('APP_SITE', 'https://fire.openthc.com');
define('APP_ROOT', __DIR__);
define('APP_SALT', sha1(APP_NAME . APP_SITE . APP_ROOT));

error_reporting(E_ALL & ~ E_NOTICE);

openlog('openthc-fire', LOG_ODELAY|LOG_PID, LOG_LOCAL0);

define('HASHID_SALT', '01CRZ5WKQWMWXRCN24MTTG9R4T');
define('OPT_SYNC_KEY', 'sync-crm-time');

// Composer Autoloader
$cvl = sprintf('%s/vendor/autoload.php', APP_ROOT);
if (!is_file($cvl)) {
	die("Run Composer First\n");
}
require_once($cvl);

SQL::init('pgsql:host=198.74.50.157;dbname=openthc', 'openthc', '13bb034868c4a1545d3d63801bd2266b');

function _leafdata_product_type_nice($t0,$t1)
{
	$x = trim(sprintf('%s/%s', $t0, $t1), '/ ');
	switch ($x) {
	case 'intermediate_product/co2_concentrate':
		return 'Concentrate/CO2';
	case 'end_product/concentrate_for_inhalation':
		return 'Concentrate';
	case 'end_product/infused_mix':
		return 'Mix/Infused';
	case 'end_product/usable_marijuana':
	case 'harvest_materials/flower_lots':
		return 'Flower';
	default:
		return $x;
	}
}
