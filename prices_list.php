<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013-2019	Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Adolfo segura           <adolfo.segura@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Ferran Marcet		    <fmarcet@2byte.es>
 * Copyright (C) 2020	    Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2021	    john Botella			<john.botella@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/list.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 */
// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (! $res) die("Include of main fails");

include_once __DIR__ .'/class/discountSearch.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (!empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies'));
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

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
$search_accountancy_code_sell = GETPOST("search_accountancy_code_sell", 'alpha');
$search_accountancy_code_sell_intra = GETPOST("search_accountancy_code_sell_intra", 'alpha');
$search_accountancy_code_sell_export = GETPOST("search_accountancy_code_sell_export", 'alpha');
$search_accountancy_code_buy = GETPOST("search_accountancy_code_buy", 'alpha');
$search_accountancy_code_buy_intra = GETPOST("search_accountancy_code_buy_intra", 'alpha');
$search_accountancy_code_buy_export = GETPOST("search_accountancy_code_buy_export", 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$type = GETPOST("type", "int");

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

$diroutputmassaction = $conf->product->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "ASC";

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'productservicelist';
if ((string) $type == '1') { $contextpage = 'servicelist'; if ($search_type == '') $search_type = '1'; }
if ((string) $type == '0') { $contextpage = 'productlist'; if ($search_type == '') $search_type = '0'; }

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new Product($db);
$hookmanager->initHooks(array('productservicelist'));
$extrafields = new ExtraFields($db);
$form = new Form($db);

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

if (empty($conf->global->PRODUIT_MULTIPRICES))
{
	$titlesellprice = $langs->trans("SellingPrice");
	if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES))
	{
		$titlesellprice = $form->textwithpicto($langs->trans("SellingPrice"), $langs->trans("DefaultPriceRealPriceMayDependOnCustomer"));
	}
}

$isInEEC = isInEEC($mysoc);

// Definition of fields for lists
$arrayfields = array(
		'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
		//'pfp.ref_fourn'=>array('label'=>$langs->trans("RefSupplier"), 'checked'=>1, 'enabled'=>(! empty($conf->barcode->enabled))),
		'p.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1, 'position'=>10),
		'p.fk_product_type'=>array('label'=>$langs->trans("Type"), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled) && !empty($conf->service->enabled)), 'position'=>11),
		'p.sellprice'=>array('label'=>$langs->trans("BaseSellingPrice"), 'checked'=>1, 'enabled'=>empty($conf->global->PRODUIT_MULTIPRICES), 'position'=>40),
		'discountlabel'=>array('label'=>$langs->trans("Discountrule"), 'checked'=>1,  'position'=>40),
		'discountproductprice'=>array('label'=>$langs->trans("NewProductPrice"), 'checked'=>1, 'position'=>50),
		'discountreductionamount'=>array('label'=>$langs->trans("DiscountRulePriceAmount"), 'checked'=>1, 'position'=>70),
		'discountsubprice'=>array('label'=>$langs->trans("NewSubPrice"), 'checked'=>1,  'position'=>80),
		'discountreduction'=>array('label'=>$langs->trans("DiscountPercent"), 'checked'=>1,  'position'=>90),
		'discountfinalsubprice'=>array('label'=>$langs->trans("FinalDiscountPrice"), 'checked'=>1,  'position'=>100),


//		'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
//		'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
		'p.tosell'=>array('label'=>$langs->trans("Status").' ('.$langs->trans("Sell").')', 'checked'=>1, 'position'=>1000)
);
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']))
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$rightskey = 'produit';
if ($type == Product::TYPE_SERVICE) $rightskey = 'service';

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$sall = "";
		$search_ref = "";
		$search_label = "";
		$search_barcode = "";
		$searchCategoryProductOperator = 0;
		$searchCategoryProductList = array();
		$search_tosell = "";
		$search_tobuy = "";
		$search_tobatch = '';
		$search_vatrate = "";
		//$search_type='';						// There is 2 types of list: a list of product and a list of services. No list with both. So when we clear search criteria, we must keep the filter on type.
		$fourn_id = '';
		$show_childproducts = '';
		$search_accountancy_code_sell = '';
		$search_accountancy_code_sell_intra = '';
		$search_accountancy_code_sell_export = '';
		$search_accountancy_code_buy = '';
		$search_accountancy_code_buy_intra = '';
		$search_accountancy_code_buy_export = '';
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Product';
	if ((string) $search_type == '1') { $objectlabel = 'Services'; }
	if ((string) $search_type == '0') { $objectlabel = 'Products'; }

	$permissiontoread = $user->rights->{$rightskey}->lire;
	$permissiontodelete = $user->rights->{$rightskey}->supprimer;
	$uploaddir = $conf->product->dir_output;
//	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$htmlother = new FormOther($db);

$title = $langs->trans("ProductsAndServices");

if ($search_type != '' && $search_type != '-1')
{
	if ($search_type == 1)
	{
		$texte = $langs->trans("Services");
	}
	else
	{
		$texte = $langs->trans("Products");
	}
}
else
{
	$texte = $langs->trans("ProductsAndServices");
}

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
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
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
//if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/product/card.php?id='.$id);
		exit;
	}

	$helpurl = '';
	if ($search_type != '')
	{
		if ($search_type == 0)
		{
			$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
		}
		elseif ($search_type == 1)
		{
			$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
		}
	}

	llxHeader('', $title, $helpurl, '');


	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&sall=".urlencode($sall);
	if ($searchCategoryProductOperator == 1) $param .= "&search_category_product_operator=".urlencode($searchCategoryProductOperator);
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		$param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
	}
	if ($search_ref) $param = "&search_ref=".urlencode($search_ref);
	if ($fk_company) $param.= "&socid=".urlencode($fk_company);
//	if ($search_ref_supplier) $param = "&search_ref_supplier=".urlencode($search_ref_supplier);
	if ($search_barcode) $param .= ($search_barcode ? "&search_barcode=".urlencode($search_barcode) : "");
	if ($search_label) $param .= "&search_label=".urlencode($search_label);
	if ($search_tosell != '') $param .= "&search_tosell=".urlencode($search_tosell);
	if ($search_tobuy != '') $param .= "&search_tobuy=".urlencode($search_tobuy);
	if ($search_tobatch) $param = "&search_tobatch=".urlencode($search_tobatch);
	if ($search_vatrate) $param = "&search_vatrate=".urlencode($search_vatrate);
	if ($fourn_id > 0) $param .= ($fourn_id ? "&fourn_id=".$fourn_id : "");
	//if ($seach_categ) $param.=($search_categ?"&search_categ=".urlencode($search_categ):"");
	if ($show_childproducts) $param .= ($show_childproducts ? "&search_show_childproducts=".urlencode($show_childproducts) : "");
	if ($type != '') $param .= '&type='.urlencode($type);
	if ($search_type != '') $param .= '&search_type='.urlencode($search_type);
	if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);

	// SIMULATION PARAMS
	if (!empty($from_quantity)) $param .= '&from_quantity='.urlencode($from_quantity);
	if (!empty($fk_country)) $param .= '&fk_country='.urlencode($fk_country);
	if (!empty($fk_project)) $param .= '&fk_project='.urlencode($fk_project);
	if (!empty($fk_c_typent)) $param .= '&fk_c_typent='.urlencode($fk_c_typent);
	if (!empty($TCategoryCompany) && is_array($TCategoryCompany)){
		foreach ($TCategoryCompany as $searchCategoryCompany) {
			$param .= "&TCategoryCompany[]=".urlencode($searchCategoryCompany);
		}
	}
	if (!empty($fk_company)) $param .= '&fk_company='.urlencode($fk_company);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
			'generate_doc'=>$langs->trans("ReGeneratePDF"),
		//'builddoc'=>$langs->trans("PDFMerge"),
		//'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->{$rightskey}->supprimer) $arrayofmassactions['predelete'] = "<span class='fa fa-trash paddingrightonly'></span>".$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
//	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	if ($type === "") $perm = ($user->rights->produit->lire || $user->rights->service->lire);
	elseif ($type == Product::TYPE_SERVICE) $perm = $user->rights->service->lire;
	elseif ($type == Product::TYPE_PRODUCT) $perm = $user->rights->produit->lire;
	if ($perm)
	{
		$btParams = array('attr'=> array('target' => '_blank'));
		$newcardbutton .= dolGetButtonTitle($langs->trans('ExportDiscountPrices'), '', 'fa fa-file-excel-o', dol_buildpath('discountrules/scripts/interface.php',1).'?action=export-price'.$param, '', 1, $btParams);
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	if (empty($arrayfields['p.fk_product_type']['checked'])) print '<input type="hidden" name="search_type" value="'.dol_escape_htmltag($search_type).'">';

	$picto = 'product';
	if ($type == 1) $picto = 'service';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, $newcardbutton, '', $limit, 0, 0, 1);



//	if (!empty($catid))
//	{
//		print "<div id='ways'>";
//		$c = new Categorie($db);
//		$ways = $c->print_all_ways(' &gt; ', 'product/list.php');
//		print " &gt; ".$ways[0]."<br>\n";
//		print "</div><br>";
//	}

	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	// PARTIE CHAMPS DE SIMULATION DE TARIFS
	$staticDiscountRule = new DiscountRule($db);
	$staticDiscountRule->fields['fk_project']['type'] = 'integer:Project:projet/class/project.class.php';

	$moreforfilter = '';

	$moreforfilter .= '<div class="divsearchfield" style="clear: both;"><small>'.$langs->trans('PriceSimulationInputs').' : </small></div>';

	$fieldList = array(
		'fk_company' => $fk_company,
		'from_quantity' => $from_quantity,
		'fk_project' => $fk_project,
		'fk_country' => $fk_country,
		'fk_c_typent' => $fk_c_typent
	);
	foreach ($fieldList as $key => $value){
		// affectation des valeurs postées
		$staticDiscountRule->{$key} = $value;

		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= '<span class="nowraponall" >'.$langs->trans($staticDiscountRule->fields[$key]['label']).': </span>';
		$moreforfilter .= $staticDiscountRule->showInputFieldQuick($key);
		$moreforfilter .= '</div>';
	}

	// cas particulier des categories client
	$staticDiscountRule->TCategoryCompany = $TCategoryCompany;
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= '<span class="nowraponall" >'.$langs->trans($staticDiscountRule->fields['all_category_company']['label']).': </span>';
	$moreforfilter .= $staticDiscountRule->showInputFieldQuick('all_category_company');
	$moreforfilter .= '</div>';


	$moreforfilter .= '<hr style="clear: both;" />';

	$moreforfilter .= '<div class="divsearchfield" style="clear: both;"><small>'.$langs->trans('SearchInputs').' : </small></div>';

	// Filter on supplier
	if (!empty($conf->fournisseur->enabled))
	{
		$moreforfilter .= '<div class="divsearchfield" >';
		$moreforfilter .= $langs->trans('Supplier').': ';
		$moreforfilter .= $form->select_company($fourn_id, 'fourn_id', '', '', 'supplier');
		$moreforfilter .= '</div>';
	}


	// Filter on categories
	if (!empty($conf->categorie->enabled))
	{
		$moreforfilter .= '<div class="divsearchfield" >';
		$moreforfilter .= $langs->trans('ProductCategories').': ';
		$categoriesProductArr = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', '', 64, 0, 1);
		$categoriesProductArr[-2] = '- '.$langs->trans('NotCategorized').' -';
		$moreforfilter .= Form::multiselectarray('search_category_product_list', $categoriesProductArr, $searchCategoryProductList, 0, 0, 'minwidth300');
		$moreforfilter .= ' <input type="checkbox" class="valignmiddle" name="search_category_product_operator" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories');
		$moreforfilter .= '</div>';
	}

	//Show/hide child products. Hidden by default
	if (!empty($conf->variants->enabled) && !empty($conf->global->PRODUIT_ATTRIBUTES_HIDECHILD)) {
		$moreforfilter .= '<div class="divsearchfield">';
		$moreforfilter .= '<input type="checkbox" id="search_show_childproducts" name="search_show_childproducts"'.($show_childproducts ? 'checked="checked"' : '').'>';
		$moreforfilter .= ' <label for="search_show_childproducts">'.$langs->trans('ShowChildProducts').'</label>';
		$moreforfilter .= '</div>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Lines with input filters
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}

	if (!empty($arrayfields['p.label']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_label" size="12" value="'.dol_escape_htmltag($search_label).'">';
		print '</td>';
	}

	// Type
	if (!empty($arrayfields['p.fk_product_type']['checked']))
	{
		print '<td class="liste_titre left">';
		$array = array('-1'=>'&nbsp;', '0'=>$langs->trans('Product'), '1'=>$langs->trans('Service'));
		print $form->selectarray('search_type', $array, $search_type);
		print '</td>';
	}

	// Sell price
	if (!empty($arrayfields['p.sellprice']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}

	// Discount label
	if (!empty($arrayfields['discountlabel']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}

	// Discount new product price
	if (!empty($arrayfields['discountproductprice']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}

	// Discount reduction amount
	if (!empty($arrayfields['discountreductionamount']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}

	// Discount new subprice
	if (!empty($arrayfields['discountsubprice']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}

	// Discount reduction percent
	if (!empty($arrayfields['discountreduction']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}

	// final price after discount
	if (!empty($arrayfields['discountfinalsubprice']['checked']))
	{
		print '<td class="liste_titre right">';
		print '</td>';
	}


	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['p.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['p.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (!empty($arrayfields['p.tosell']['checked']))
	{
		print '<td class="liste_titre right">';
		print $form->selectarray('search_tosell', array('0'=>$langs->trans('ProductStatusNotOnSellShort'), '1'=>$langs->trans('ProductStatusOnSellShort')), $search_tosell, 1);
		print '</td >';
	}

	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['p.ref']['checked'])) {
		print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	}

	if (!empty($arrayfields['p.label']['checked'])) {
		print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], "p.label", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.fk_product_type']['checked'])) {
		print_liste_field_titre($arrayfields['p.fk_product_type']['label'], $_SERVER["PHP_SELF"], "p.fk_product_type", "", $param, "", $sortfield, $sortorder);
	}

	if (!empty($arrayfields['p.sellprice']['checked'])) {
		print_liste_field_titre($arrayfields['p.sellprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}

	// DISCOUNT

	if (!empty($arrayfields['discountlabel']['checked'])) {
		print_liste_field_titre($arrayfields['discountlabel']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['discountproductprice']['checked'])) {
		print_liste_field_titre($arrayfields['discountproductprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['discountreductionamount']['checked'])) {
		print_liste_field_titre($arrayfields['discountreductionamount']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['discountsubprice']['checked'])) {
		print_liste_field_titre($arrayfields['discountsubprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['discountreduction']['checked'])) {
		print_liste_field_titre($arrayfields['discountreduction']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (!empty($arrayfields['discountfinalsubprice']['checked'])) {
		print_liste_field_titre($arrayfields['discountfinalsubprice']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}


	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['p.datec']['checked'])) {
		print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['p.tms']['checked'])) {
		print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['p.tosell']['checked'])) {
		print_liste_field_titre($arrayfields['p.tosell']['label'], $_SERVER["PHP_SELF"], "p.tosell", "", $param, '', $sortfield, $sortorder, 'right ');
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$product_static = new Product($db);
	$product_fourn = new ProductFournisseur($db);

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

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

		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $product_static->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Label
		if (!empty($arrayfields['p.label']['checked']))
		{
			print '<td class="">'.$obj->label.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Type
		if (!empty($arrayfields['p.fk_product_type']['checked']))
		{
			print '<td>';
			if ($obj->fk_product_type == 0) print $langs->trans("Product");
			else print $langs->trans("Service");
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Sell price
		if (!empty($arrayfields['p.sellprice']['checked']))
		{
			print '<td class="right nowraponall">';
			if ($obj->tosell)
			{
				if ($obj->price_base_type == 'TTC' && !empty(doubleval($obj->price_ttc))) print price($obj->price_ttc).' '.$langs->trans("TTC");
				elseif(!empty(doubleval($obj->price))) print price($obj->price).' '.$langs->trans("HT");
				else print '--';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// DISCOUNT

		// discount label
		if (!empty($arrayfields['discountlabel']['checked']))
		{
			print '<td class="right nowraponall">';
			if(is_object($discountSearchResult) && $discountSearchResult->result)
			{
				print $discountSearchResult->label;
			}
			elseif (!is_object($discountSearchResult)){
				print '<span class="error">'.$langs->trans('AnErrorOccurDuringSearchDiscount').'</span>';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}


		// discount reduction
		if (!empty($arrayfields['discountproductprice']['checked']))
		{
			print '<td class="right nowraponall">';
			if ($discountSearchResult->result && !empty($discountSearchResult->product_price))
			{
//				if ($obj->price_base_type == 'TTC') print price($obj->price_ttc).' '.$langs->trans("TTC");
//				else
				print price($discountSearchResult->product_price).' '.$langs->trans("HT");
			}
			else{
				print '--';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}


		// discount reduction
		if (!empty($arrayfields['discountreductionamount']['checked']))
		{
			print '<td class="right nowraponall">';
			if ($discountSearchResult->result && !empty($discountSearchResult->product_reduction_amount))
			{
//				if ($obj->price_base_type == 'TTC') print price($obj->price_ttc).' '.$langs->trans("TTC");
//				else
				print price($discountSearchResult->product_reduction_amount).' '.$langs->trans("HT");
			}
			else{
				print '--';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// discountrule subprice
		if (!empty($arrayfields['discountsubprice']['checked']))
		{
			print '<td class="right nowraponall">';
			if ($discountSearchResult->result && $discountSearchResult->subprice > 0)
			{
//				if ($obj->price_base_type == 'TTC') print price($obj->price_ttc).' '.$langs->trans("TTC");
//				else
				print price($discountSearchResult->subprice).' '.$langs->trans("HT");
			}
			else{
				print '--';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// discount reduction
		if (!empty($arrayfields['discountreduction']['checked']))
		{
			print '<td class="right nowraponall">';
			if ($discountSearchResult->result && !empty($discountSearchResult->reduction))
			{
				print $discountSearchResult->reduction;
			}
			else{
				print '--';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}


		// final price after discount
		if (!empty($arrayfields['discountfinalsubprice']['checked']))
		{
			print '<td class="right nowraponall">';

			if ($discountSearchResult->result)
			{
				$finalPrice = $discountSearchResult->calcFinalSubprice();
				print price($finalPrice).' '.$langs->trans("HT");
			}
			else{
				$finalPrice = DiscountRule::getProductSellPrice($product_static->id, $fk_company);
				if($finalPrice>0){
					print price($finalPrice).' '.$langs->trans("HT");
				}
				else{
					print '--';
				}
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['p.datec']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Date modification
		if (!empty($arrayfields['p.tms']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Status (to sell)
		if (!empty($arrayfields['p.tosell']['checked']))
		{
			print '<td class="right nowrap">';
			if (!empty($conf->use_javascript_ajax) && $user->rights->produit->creer && !empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
				print ajax_object_onoff($product_static, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
			} else {
				print $product_static->LibStatut($obj->tosell, 5, 0);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}

	$db->free($resql);

	print "</table>";
	print "</div>";
	print '</form>';
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
