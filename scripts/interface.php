<?php


//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB', '1');				// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');				// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');				// Do not check style html tag into posted data
if (! defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');				// If there is no need to load and show top and left menu
if (! defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');				// If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');					// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');	// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', 1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP', 'none');				// Disable all Content Security Policies
//if (! defined('CSRFCHECK_WITH_TOKEN'))     define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
//if (! defined('NOBROWSERNOTIF'))     		 define('NOBROWSERNOTIF', '1');				// Disable browser notification


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = dirname(__FILE__) . '/';

// Include and load Dolibarr environment variables
$res = 0;
if (!$res && file_exists($path . "main.inc.php")) $res = @include($path . "main.inc.php");
if (!$res && file_exists($path . "../main.inc.php")) $res = @include($path . "../main.inc.php");
if (!$res && file_exists($path . "../../main.inc.php")) $res = @include($path . "../../main.inc.php");
if (!$res && file_exists($path . "../../../main.inc.php")) $res = @include($path . "../../../main.inc.php");
if (!$res) die("Include of master fails");
require_once __DIR__ . '/../class/discountrule.class.php';
require_once __DIR__ . '/../class/discountSearch.class.php';
require_once __DIR__ . '/../lib/discountrules.lib.php';

global $langs, $db, $hookmanager, $user, $mysoc;
/**
 * @var DoliDB $db
 */
$hookmanager->initHooks('discountruleinterface');

// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules@discountrules", "other", 'main'));

$action = GETPOST('action');


// Security check
if (empty($conf->discountrules->enabled)) accessforbidden('Module not enabled');

if ($action === 'product-discount'
	&& ($user->socid > 0 || empty($user->rights->discountrules->read))
)
{
	$jsonResponse = new stdClass();
	$jsonResponse->result = false;
	$jsonResponse->log = array("Not enough rights", $user->rights->discountrules->read );

	// output
	print json_encode($jsonResponse, JSON_PRETTY_PRINT);
	$db->close();    // Close $db database opened handler
	exit;
}

// RECHERCHE DE REMISES
if ($action === 'product-discount') {

	$fk_product = GETPOST('fk_product', 'int');
	$fk_project = GETPOST('fk_project', 'int');
	$fk_company = GETPOST('fk_company', 'int');
	$fk_country = GETPOST('fk_country', 'int');
	$qty = GETPOST('qty', 'int');
	$fk_c_typent = GETPOST('fk_c_typent', 'int');

	$search = new DiscountSearch($db);
	$jsonResponse = $search->search($qty, $fk_product, $fk_company, $fk_project, array(), array(), $fk_c_typent, $fk_country);

	// Mise en page du résultat
	$jsonResponse->tpMsg = getDiscountRulesInterfaceMessageTpl($langs, $jsonResponse, $action);

	// Note that $action and $object may be modified by hook
	// Utilisation initiale : interception pour remplissage customisé de $jsonResponse->tpMsg

	$parameters = array(
		'search' => $search,
		'action' => $action,
		'productId' => $fk_product,
		'fk_project' => $fk_project,
		'fk_company' => $fk_company,
		'fk_country' => $fk_country,
		'qty' => $qty,
		'fk_c_typent' => $fk_c_typent
	);

	$reshook = $hookmanager->executeHooks('ToolTipformAddInfo', $parameters, $jsonResponse, $action);

	// output
	print json_encode($jsonResponse, JSON_PRETTY_PRINT);
}

elseif($action === 'export-price')
{
	_exportProductsPrices();
}


$db->close();    // Close $db database opened handler



/*
 * LIBRAIRIES UNIQUEMENT POUR CETTE PAGE
 */

function _exportProductsPrices(){
	global $hookmanager,$user, $db, $mysoc, $langs, $conf;

	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
	if (!empty($conf->categorie->enabled))
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


	$csvDelimiter = ';';

	$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
	$search_ref = GETPOST("search_ref", 'alpha');
	$search_barcode = GETPOST("search_barcode", 'alpha');
	$search_label = GETPOST("search_label", 'alpha');
	$search_type = GETPOST("search_type", 'int');
	$search_vatrate = GETPOST("search_vatrate", 'alpha');
	$searchCategoryProductOperator = (GETPOST('search_category_product_operator', 'int') ? GETPOST('search_category_product_operator', 'int') : 0);
	$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
	$search_tosell = GETPOST("search_tosell", 'int');
	$search_tobuy = GETPOST("search_tobuy", 'int');
	$fourn_id = GETPOST("fourn_id", 'int');
	$catid = GETPOST('catid', 'int');
	$search_tobatch = GETPOST("search_tobatch", 'int');
	$type = GETPOST("type", "int");

	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
	if (!$sortfield) $sortfield = "p.ref";
	if (!$sortorder) $sortorder = "ASC";

	// Initialize context for list
	$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'productservicelist';
	if ((string) $type == '1') { $contextpage = 'servicelist'; if ($search_type == '') $search_type = '1'; }
	if ((string) $type == '0') { $contextpage = 'productlist'; if ($search_type == '') $search_type = '0'; }

	// FILTRE DE SIMULATION
	$from_quantity = GETPOST("from_quantity", 'int');
	$fk_country = GETPOST("fk_country", 'int');
	$fk_country = intval($fk_country);
	if($fk_country<0){ $fk_country = 0; }
	$fk_project = GETPOST("fk_project", 'int');
	$fk_project = intval($fk_project);
	if($fk_project<0){ $fk_project = 0; }
	$fk_c_typent = GETPOST("fk_c_typent", 'int');
	$fk_c_typent = intval($fk_c_typent);
	if($fk_c_typent<0){ $fk_c_typent = 0; }
	$TCategoryCompany = GETPOST("TCategoryCompany", 'array');
	$fk_company = GETPOST("fk_company", 'int');
	$fk_company = intval($fk_company);
	if($fk_company<0){ $fk_company = 0; }
	if(!empty($fk_company)){
		// si societé selectionné, les champs suivants ne sont pas utiles
		$fk_country = 0;
		$fk_c_typent = 0;
		$TCategoryCompany = array();
	}


	//Show/hide child products
	if (!empty($conf->variants->enabled) && !empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
		$show_childproducts = GETPOST('search_show_childproducts');
	} else {
		$show_childproducts = '';
	}



	// Initialize context for list
	$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'productservicelist';
	if ((string) $type == '1') { $contextpage = 'servicelist'; if ($search_type == '') $search_type = '1'; }
	if ((string) $type == '0') { $contextpage = 'productlist'; if ($search_type == '') $search_type = '0'; }

	// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
	$object = new Product($db);
	$hookmanager->initHooks(array('productservicelist'));
	$extrafields = new ExtraFields($db);

	// fetch optionals attributes and labels
	$extrafields->fetch_name_optionals_label($object->table_element);
	$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

	if (empty($action)) $action = 'list';

	// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
	$canvas = GETPOST("canvas");
	$objcanvas = null;
	if (!empty($canvas))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
		$objcanvas = new Canvas($db, $action);
		$objcanvas->getCanvas('product', 'list', $canvas);
	}


	// Security check
	if ($search_type == '0') $result = restrictedArea($user, 'produit', '', '', '', '', '', $objcanvas);
	elseif ($search_type == '1') $result = restrictedArea($user, 'service', '', '', '', '', '', $objcanvas);
	else $result = restrictedArea($user, 'produit|service', '', '', '', '', '', $objcanvas);



	// List of fields to search into when doing a "search in all"
	$fieldstosearchall = array(
		'p.ref'=>"Ref",
		'pfp.ref_fourn'=>"RefSupplier",
		'p.label'=>"ProductLabel",
		'p.description'=>"Description",
		"p.note"=>"Note",

	);

	// multilang
	if (!empty($conf->global->MAIN_MULTILANGS))
	{
		$fieldstosearchall['pl.label'] = 'ProductLabelTranslated';
		$fieldstosearchall['pl.description'] = 'ProductDescriptionTranslated';
		$fieldstosearchall['pl.note'] = 'ProductNoteTranslated';
	}

	if (!empty($conf->barcode->enabled)) {
		$fieldstosearchall['p.barcode'] = 'Gencod';
		$fieldstosearchall['pfp.barcode'] = 'GencodBuyPrice';
	}

	// Personalized search criterias. Example: $conf->global->PRODUCT_QUICKSEARCH_ON_FIELDS = 'p.ref=ProductRef;p.label=ProductLabel'
	if (!empty($conf->global->PRODUCT_QUICKSEARCH_ON_FIELDS)) $fieldstosearchall = dolExplodeIntoArray($conf->global->PRODUCT_QUICKSEARCH_ON_FIELDS);


	$isInEEC = isInEEC($mysoc);

	// Definition of fields for lists
	$arrayfields = array(
		'p.ref'=>array('label'=>$langs->transnoentities("Ref"), 'checked'=>1),
		//'pfp.ref_fourn'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1, 'enabled'=>(! empty($conf->barcode->enabled))),
		'p.label'=>array('label'=>$langs->transnoentities("Label"), 'checked'=>1, 'position'=>10),
		'p.fk_product_type'=>array('label'=>$langs->transnoentities("Type"), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled) && !empty($conf->service->enabled)), 'position'=>11),
		'p.sellprice'=>array('label'=>$langs->transnoentities("BaseSellingPrice"), 'checked'=>1, 'enabled'=>empty($conf->global->PRODUIT_MULTIPRICES), 'position'=>40),
		'discountlabel'=>array('label'=>$langs->transnoentities("Discountrule"), 'checked'=>1,  'position'=>40),
		'discountproductprice'=>array('label'=>$langs->transnoentities("NewProductPrice"), 'checked'=>1, 'position'=>50),
		'discountreductionamount'=>array('label'=>$langs->transnoentities("DiscountRulePriceAmount"), 'checked'=>1, 'position'=>70),
		'discountsubprice'=>array('label'=>$langs->transnoentities("NewSubPrice"), 'checked'=>1,  'position'=>80),
		'discountreduction'=>array('label'=>$langs->transnoentities("DiscountPercent"), 'checked'=>1,  'position'=>90),
		'discountfinalsubprice'=>array('label'=>$langs->transnoentities("FinalDiscountPrice"), 'checked'=>1,  'position'=>100),

//		'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
//		'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
		'p.tosell'=>array('label'=>$langs->transnoentities("Status").' ('.$langs->transnoentities("Sell").')', 'checked'=>1, 'position'=>1000)
	);

	// Extra fields
//	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']))
//	{
//		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
//		{
//			if (!empty($extrafields->attributes[$object->table_element]['list'][$key]))
//				$arrayfields["ef.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
//		}
//	}
//	$object->fields = dol_sort_array($object->fields, 'position');
	$arrayfields = dol_sort_array($arrayfields, 'position');


	$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.fk_product_type, p.barcode, p.price, p.tva_tx, p.price_ttc, p.price_base_type, p.entity,';
	$sql .= ' p.fk_product_type, p.duration, p.finished, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,';
	$sql .= ' p.tobatch, p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,';
	$sql .= ' p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export,';
	$sql .= ' p.datec as date_creation, p.tms as date_update, p.pmp, p.stock,';
	$sql .= ' p.weight, p.weight_units, p.length, p.length_units, p.width, p.width_units, p.height, p.height_units, p.surface, p.surface_units, p.volume, p.volume_units,';
	if (!empty($conf->global->PRODUCT_USE_UNITS))   $sql .= ' p.fk_unit, cu.label as cu_label,';
	$sql .= ' MIN(pfp.unitprice) as minsellprice';
	if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
		$sql .= ', pac.rowid prod_comb_id';
	}
	// Add fields from extrafields
//	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
//		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
//	}
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as ef on (p.rowid = ef.fk_object)";
	if (!empty($searchCategoryProductList) || !empty($catid)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON p.rowid = cp.fk_product"; // We'll need this table joined to the select in order to filter by categ
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON p.rowid = pfp.fk_product";
	// multilang
	if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang()."'";

	if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_attribute_combination pac ON pac.fk_product_child = p.rowid";
	}
	if (!empty($conf->global->PRODUCT_USE_UNITS))   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_units cu ON cu.rowid = p.fk_unit";


	$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
	if ($sall) $sql .= natural_search(array_keys($fieldstosearchall), $sall);
// if the type is not 1, we show all products (type = 0,2,3)
	if (dol_strlen($search_type) && $search_type != '-1')
	{
		if ($search_type == 1) $sql .= " AND p.fk_product_type = 1";
		else $sql .= " AND p.fk_product_type <> 1";
	}

	if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
		$sql .= " AND pac.rowid IS NULL";
	}

	if ($search_ref)     $sql .= natural_search('p.ref', $search_ref);
	if ($search_label)   $sql .= natural_search('p.label', $search_label);
	if ($search_barcode) $sql .= natural_search('p.barcode', $search_barcode);
	if (isset($search_tosell) && dol_strlen($search_tosell) > 0 && $search_tosell != -1) $sql .= " AND p.tosell = ".((int) $search_tosell);
	if (isset($search_tobuy) && dol_strlen($search_tobuy) > 0 && $search_tobuy != -1)   $sql .= " AND p.tobuy = ".((int) $search_tobuy);
	if (isset($search_tobatch) && dol_strlen($search_tobatch) > 0 && $search_tobatch != -1)   $sql .= " AND p.tobatch = ".((int) $search_tobatch);
	if ($search_vatrate) $sql .= natural_search('p.tva_tx', $search_vatrate, 1);
	if (dol_strlen($canvas) > 0)                    $sql .= " AND p.canvas = '".$db->escape($canvas)."'";
	if ($catid > 0)     $sql .= " AND cp.fk_categorie = ".$catid;
	if ($catid == -2)   $sql .= " AND cp.fk_categorie IS NULL";
	$searchCategoryProductSqlList = array();
	if ($searchCategoryProductOperator == 1) {
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			if (intval($searchCategoryProduct) == -2) {
				$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
			} elseif (intval($searchCategoryProduct) > 0) {
				$searchCategoryProductSqlList[] = "cp.fk_categorie = ".$db->escape($searchCategoryProduct);
			}
		}
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryProductSqlList).")";
		}
	} else {
		foreach ($searchCategoryProductList as $searchCategoryProduct) {
			if (intval($searchCategoryProduct) == -2) {
				$searchCategoryProductSqlList[] = "cp.fk_categorie IS NULL";
			} elseif (intval($searchCategoryProduct) > 0) {
				$searchCategoryProductSqlList[] = "p.rowid IN (SELECT fk_product FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_categorie = ".$searchCategoryProduct.")";
			}
		}
		if (!empty($searchCategoryProductSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryProductSqlList).")";
		}
	}
	if ($fourn_id > 0)  $sql .= " AND pfp.fk_soc = ".((int) $fourn_id);


	// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.tva_tx, p.price_ttc, p.price_base_type,";
	$sql .= " p.fk_product_type, p.duration, p.finished, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,";
	$sql .= ' p.datec, p.tms, p.entity, p.tobatch, p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,';
	$sql .= ' p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export, p.pmp, p.stock,';
	$sql .= ' p.weight, p.weight_units, p.length, p.length_units, p.width, p.width_units, p.height, p.height_units, p.surface, p.surface_units, p.volume, p.volume_units';
	if (!empty($conf->global->PRODUCT_USE_UNITS))   $sql .= ', p.fk_unit, cu.label';

	if (!empty($conf->variants->enabled) && (!empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD) && !$show_childproducts)) {
		$sql .= ', pac.rowid';
	}
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
	}
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldSelect', $parameters); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sql .= $db->order($sortfield, $sortorder);

	$product_static = new Product($db);

	$sqlNoLimit = $sql;

	$limit  = 5000; $offset = 0; // first step of trick :  I use a trick to avoid  $db->query($sql) memory leak with huge database
	$currentRowCount = 0;
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql)
	{
		header("Content-disposition: attachment; filename=test.csv");
		header("Content-Type: text/csv");
		$csv = fopen("php://output", 'w');
		$outputRow = array();
		foreach ($arrayfields as $key => $values){
			$outputRow[$key] = '';
			if(isset($values['label'])){
				$outputRow[$key] = $values['label'];
			}
		}
		fputcsv($csv, $outputRow, $csvDelimiter);


		$colForNumbers = array(
			'p.sellprice',
			'p.sellpriceTTC',
			'discountproductprice',
			'product_reduction_amount',
			'discountsubprice',
			'discountreduction',
			'discountfinalsubprice'
		);

		$lastRowid = 0;
		while ($obj = $db->fetch_object($resql))
		{
			$currentRowCount++;

			// ROW control test only for developer test
//			if($lastRowid == $obj->rowid){ var_dump('error duplicate content'); exit; }
//			$lastRowid = $obj->rowid;

			// second step of trick :  I use a trick to avoid  $db->query($sql) memory leak with huge database
			// I use this trick because Dolibarr can't allow me to use Unbuffered queries : https://www.php.net/manual/en/mysqlinfo.concepts.buffering.php
			if($currentRowCount == $limit){
				$db->free($resql);
				$offset += $currentRowCount;
				$resql = $db->query($sqlNoLimit.$db->plimit($limit, $offset));
				$currentRowCount = 0;
			}

			// Multilangs
			if (!empty($conf->global->MAIN_MULTILANGS))  // If multilang is enabled
			{
				$sql = "SELECT label";
				$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
				$sql .= " WHERE fk_product=".$obj->rowid;
				$sql .= " AND lang='".$db->escape($langs->getDefaultLang())."'";
				$sql .= " LIMIT 1";

				$result = $db->query($sql);
				if ($result)
				{
					$objtp = $db->fetch_object($result);
					if (!empty($objtp->label)) $obj->label = $objtp->label;
					$db->free($result);
				}
			}

			$product_static->id = $obj->rowid;
			$product_static->ref = $obj->ref;
			$product_static->ref_fourn = $obj->ref_supplier; // deprecated
			$product_static->ref_supplier = $obj->ref_supplier;
			$product_static->label = $obj->label;
			$product_static->finished = $obj->finished;
			$product_static->type = $obj->fk_product_type;
			$product_static->status_buy = $obj->tobuy;
			$product_static->status     = $obj->tosell;
			$product_static->status_batch = $obj->tobatch;
			$product_static->entity = $obj->entity;
			$product_static->pmp = $obj->pmp;
			$product_static->accountancy_code_sell = $obj->accountancy_code_sell;
			$product_static->accountancy_code_sell_export = $obj->accountancy_code_sell_export;
			$product_static->accountancy_code_sell_intra = $obj->accountancy_code_sell_intra;
			$product_static->accountancy_code_buy = $obj->accountancy_code_buy;
			$product_static->accountancy_code_buy_intra = $obj->accountancy_code_buy_intra;
			$product_static->accountancy_code_buy_export = $obj->accountancy_code_buy_export;
			$product_static->length = $obj->length;
			$product_static->length_units = $obj->length_units;
			$product_static->width = $obj->width;
			$product_static->width_units = $obj->width_units;
			$product_static->height = $obj->height;
			$product_static->height_units = $obj->height_units;
			$product_static->weight = $obj->weight;
			$product_static->weight_units = $obj->weight_units;
			$product_static->volume = $obj->volume;
			$product_static->volume_units = $obj->volume_units;
			$product_static->surface = $obj->surface;
			$product_static->surface_units = $obj->surface_units;
			if (!empty($conf->global->PRODUCT_USE_UNITS)) {
				$product_static->fk_unit = $obj->fk_unit;
			}

			// Search discount
			$discountSearch = new DiscountSearch($db);
			$discountSearchResult = $discountSearch->search($from_quantity, $product_static->id, $fk_company, $fk_project, array(), $TCategoryCompany, $fk_c_typent, $fk_country);
			DiscountRule::clearProductCache();

			$line = array(
				'p.ref' => $product_static->ref,
				'p.label' => $product_static->label,
				'p.sellprice' => $obj->price,
				'p.sellpriceTTC' => $obj->price_ttc,
				'p.datec' => dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser'),
				'p.tms' => dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser'),
				'p.tosell' => $product_static->LibStatut($obj->tosell, 1, 0),
			);

			if ($obj->fk_product_type == 0) $line['p.fk_product_type'] = $langs->trans("Product");
			else $line['p.fk_product_type'] = $langs->trans("Service");

			// DISCOUNT
			if (!is_object($discountSearchResult)){
				continue;
			}

			$line['discountlabel'] = $discountSearchResult->label;
			$line['discountproductprice'] = $discountSearchResult->product_price;
			$line['product_reduction_amount'] = $discountSearchResult->product_reduction_amount;
			$line['discountsubprice'] = !empty($discountSearchResult->subprice)?$discountSearchResult->subprice:'';
			$line['discountreduction'] = $discountSearchResult->reduction;

			if ($discountSearchResult->result){
				$line['discountfinalsubprice'] = $discountSearchResult->calcFinalSubprice();
			}else{
				$line['discountfinalsubprice'] = DiscountRule::getProductSellPrice($product_static->id, $fk_company);
			}


			$outputRow = array();
			foreach ($arrayfields as $key => $values){
				$outputRow[$key] = '';
				if(isset($line[$key])){
					$outputRow[$key] = $line[$key];

					if(in_array($key, $colForNumbers)){
						$outputRow[$key] = price($line[$key]);
					}
				}
			}

			if(!empty($line['discountfinalsubprice'])){
				fputcsv($csv, $outputRow, $csvDelimiter);
			}
		}

		fclose($csv);

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
