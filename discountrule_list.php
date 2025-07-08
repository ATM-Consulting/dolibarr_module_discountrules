<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       discountrule_list.php
 *		\ingroup    discountrules
 *		\brief      List page for discountrule
 */

//if (! defined('NOREQUIREUSER'))          define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))            define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))           define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))          define('NOREQUIRETRAN','1');
//if (! defined('NOSCANGETFORINJECTION'))  define('NOSCANGETFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOSCANPOSTFORINJECTION')) define('NOSCANPOSTFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOCSRFCHECK'))            define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))           define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))         define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))          define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))          define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))          define('NOREQUIREAJAX','1');         // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');			// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', '1');		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("XFRAMEOPTIONS_ALLOWALL"))   define('XFRAMEOPTIONS_ALLOWALL', '1');			// Do not add the HTTP header 'X-Frame-Options: SAMEORIGIN' but 'X-Frame-Options: ALLOWALL'

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

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';


dol_include_once('/discountrules/class/discountrule.class.php');
dol_include_once('/discountrules/class/discountSearch.class.php');

// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules","other"));

$action     = GETPOST('action','alpha');
$massaction = GETPOST('massaction','alpha');
$show_files = GETPOST('show_files','int');
$confirm    = GETPOST('confirm','alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect   = GETPOST('toselect', 'array');
$contextpage= GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'discountrulelist';   // To manage different context of search

$id			= GETPOST('id','int');
$backtopage = GETPOST('backtopage');
$optioncss  = GETPOST('optioncss','alpha');

$fk_product = GETPOST('fk_product', 'int');
$fk_company = GETPOST('fk_company', 'int');

$displayRulesWithoutProduct = GETPOST('display-rules-without-product', 'int');

$searchCategoryProductOperator = GETPOST('searchCategoryProductOperator', 'int');
$searchCategorySocieteOperator = GETPOST('searchCategorySocieteOperator', 'int');
$TCategoryProduct = GETPOST('search_TCategoryProduct', 'array');
$TCategoryCompany = GETPOST('search_TCategoryCompany', 'array');


// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object=new DiscountRule($db);

// for this list
if(empty($fk_product)){
	$object->fields['fk_product']['visible'] = 1;
}
if(!empty($fk_company)){
	$object->fields['fk_company']['visible'] = 0;
	$object->fields['fk_c_typent']['visible'] = 0;
}

$discountRulesExtrafields = new ExtraFields($db);
$diroutputmassaction=$conf->discountrules->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('discountrulelist'));     // Note that conf->hooks_modules contains array


$extrafields = new ExtraFields($db);
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
//$extrafields->fetch_name_optionals_label($object->table_element_line);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Default sort order (if not yet defined by previous GETPOST)
if (! $sortfield) $sortfield="t.".key($object->fields);   // Set here default search field. By default 1st field in definition.
if (! $sortorder) $sortorder="ASC";

// Security check
if (!(isModEnabled('discountrules'))) accessforbidden('Module not enabled');
$socid=0;
if ($user->socid > 0 // Protection if external user
		|| !$user->hasRight('discountrules', 'read') // Check user right
)
{
	//$socid = $user->societe_id;
	accessforbidden();
}

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all",'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha') !== '') $search[$key] = GETPOST('search_'.$key, 'alpha');
}

/*$ASearchTest = array('fk_category_company','fk_category_product', 'fk_company');
foreach ($ASearchTest as $key)
{
    if(!empty($search[$key]) && $search[$key] < 0){
        unset($search[$key]);
    }
}*/




// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach($object->fields as $key => $val)
{
	if (!empty($val['searchall'])) $fieldstosearchall['t.'.$key]=$val['label'];
}

// Definition of fields for list
$arrayfields=array();
foreach($object->fields as $key => $val)
{
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) $arrayfields['t.'.$key] = array('label'=>$val['label'], 'checked'=>(($val['visible'] < 0) ? 0 : 1), 'enabled'=>($val['enabled'] && ($val['visible'] != 3)), 'position'=>$val['position']);
}

// Extra fields
if (isset($discountRulesExtrafields->attributes[$object->element]['label']) && is_array($discountRulesExtrafields->attributes[$object->element]['label']) && count($discountRulesExtrafields->attributes[$object->element]['label'])) {
	foreach ($discountRulesExtrafields->attributes[$object->element]['label'] as $key => $val) {
		if (!empty($discountRulesExtrafields->attributes[$object->table_element]['list'][$key])) {
			$arrayfields["ef." . $key] = array(
					'label' => $discountRulesExtrafields->attributes[$object->element]['label'][$key],
					'checked' => $discountRulesExtrafields->attributes[$object->element]['list'][$key],
					'position' => $discountRulesExtrafields->attributes[$object->element]['pos'][$key],
					'enabled' =>$discountRulesExtrafields->attributes[$object->element]['perms'][$key]
			);
		}
	}
}

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') ||GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{

		$searchCategoryProductOperator = $searchCategorySocieteOperator = 0;
		$TCategoryProduct = $TCategoryCompany = array();

		foreach($object->fields as $key => $val)
		{
			$search[$key]='';
		}
		$toselect='';
		$search_array_options=array();
	}
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')
		|| GETPOST('button_search_x','alpha') || GETPOST('button_search.x','alpha') || GETPOST('button_search','alpha'))
	{
		$massaction='';     // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass actions
	$objectclass='discountrule';
	$objectlabel='discountrule';
	$permtoread = $user->hasRight('discountrules','read');
	$permtodelete = $user->hasRight('discountrules','delete');
	$uploaddir = $conf->discountrules->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';


	if($massaction === "delete" && is_array($toselect) && $user->hasRight('discountrules', 'delete')){
		$deleteCount = 0;
		$deleteErrorCount = 0;
		foreach ($toselect as $selectedId){
			$objectToDelete = new DiscountRule($db);
			$res = $objectToDelete->fetch($selectedId);
			if($res){
				$result=$objectToDelete->delete($user);
				if ($result > 0){
					$deleteCount++;
				}else{
					$deleteErrorCount++;
					if (! empty($objectToDelete->errors)) setEventMessages(null, $objectToDelete->errors, 'errors');
					else setEventMessages($objectToDelete->error, null, 'errors');
				}
			}
		}

		if(!empty($deleteCount)){
			setEventMessage($langs->trans('Deleted'));
		}
	}
}



/*
 * View
 */

/*************************************************************
 * TODO : recreate this page generate by modulebuilder and  DO SOMETHING USABLE !!!!!!!!! and not a printkenstein monster !! and Yes I'm angry!
 **************************************************************/


$form=new Form($db);

$now=dol_now();

//$help_url="EN:Module_discountrule|FR:Module_discountrule_FR|ES:Módulo_discountrule";
$help_url='';
$title = $langs->trans('ListOfDiscountRules');


// Build and execute select
// --------------------------------------------------------------------
$sql = 'SELECT ';
foreach($object->fields as $key => $val)
{
	$sql.='t.'.$key.', ';
}

$sql.='s.nom societeName ';
//$sql.='cs.label labelCatSociete, ';
//$sql.='cp.label labelCatProduit, ';

// Add fields from extrafields

if (!empty($discountRulesExtrafields->attributes[$object->table_element]['label'])) {
	foreach ($discountRulesExtrafields->attribute_label as $key => $val) $sql.=($discountRulesExtrafields->attribute_type[$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql=preg_replace('/, $/','', $sql);
$sql.= " FROM ".MAIN_DB_PREFIX."discountrule as t";
if (isset($discountRulesExtrafields->attribute_label) && is_array($discountRulesExtrafields->attribute_label) && count($discountRulesExtrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."discountrule_extrafields as ef on (t.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on (s.rowid = t.fk_company)";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as cs on (cs.rowid = t.fk_category_company  AND  cs.type = 2 )";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."categorie as cp on (cp.rowid = t.fk_category_product  AND  cp.type = 0 )";

$sql.= " WHERE t.entity IN (".getEntity('discountrule').") ";


if($displayRulesWithoutProduct) {
	$sql.= ' AND t.fk_product = 0 ';
}elseif(!empty($fk_product)) {
	$sql.= ' AND t.fk_product = ' . intval($fk_product) . ' ';
}

if(!empty($fk_company)) {
   $sql .= DiscountSearch::getCompanySQLFilters($fk_company);
}
elseif (!empty($search['fk_company'])){
	$sql .= natural_search('s.nom', $search['fk_company']);
}

if(!empty($TCategoryProduct)){
	$TCategoryProduct = array_map('intval', $TCategoryProduct);

	$sql.= ' AND t.rowid IN (SELECT dcp.fk_discountrule 
	FROM '.MAIN_DB_PREFIX.'discountrule_category_product dcp 
	WHERE dcp.fk_category_product IN ('.implode(',', $TCategoryProduct).')
	)';
}

if(!empty($TCategoryCompany)){
	$TCategoryCompany = array_map('intval', $TCategoryCompany);

	$sql.= ' AND t.rowid IN (SELECT dcc.fk_discountrule 
	FROM '.MAIN_DB_PREFIX.'discountrule_category_company dcc 
	WHERE dcc.fk_category_company IN ('.implode(',', $TCategoryCompany).')
	)';
}


foreach($search as $key => $val)
{
	if ($key == 'fk_status' && $search[$key] == -1) continue;

	if (in_array($key, array('all_category_product', 'all_category_company', 'fk_company' ))) continue;

	$mode_search = (($object->isInt($object->fields[$key]) || $object->isFloat($object->fields[$key])) ? 1 : 0);
	if (strpos($object->fields[$key]['type'], 'integer:') === 0
		|| (!empty($object->fields[$key]['arrayofkeyval']) && is_array($object->fields[$key]['arrayofkeyval']))
	) {
		if ($search[$key] == '-1') $search[$key] = '';
		$mode_search = 2;
	}
	if ($search[$key] != '') $sql .= natural_search($key, $search[$key], (($key == 'status') ? 2 : $mode_search));
}
if ($search_all) $sql.= natural_search(array_keys($fieldstosearchall), $search_all);
//$sql.= dolSqlDateFilter("t.field", $search_xxxday, $search_xxxmonth, $search_xxxyear);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;

/* If a group by is required
$sql.= " GROUP BY ";
foreach($object->fields as $key => $val)
{
    $sql.='t.'.$key.', ';
}
// Add fields from extrafields
if (! empty($discountRulesExtrafields->attributes[$object->table_element]['label'])) {
	foreach ($discountRulesExtrafields->attribute_label as $key => $val) $sql.=($discountRulesExtrafields->attribute_type[$key] != 'separate' ? ",ef.".$key : '');
}
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListGroupBy',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql=preg_replace('/,\s*$/','', $sql);
*/

$sql.=$db->order($sortfield,$sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST'))
{
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit)))
{
	$num = $nbtotalofrecords;
}
else
{
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

	$resql=$db->query($sql);
	if (! $resql)
	{
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Direct jump if only one record found
if ($num == 1 && getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/discountrules/discountrule_card.php?id='.$id);
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

if(!empty($fk_product)){

	$product = new Product($db);
	$product->fetch($fk_product);

	$object->fk_product = $fk_product;
	$object->initFieldsParams();
	$head=product_prepare_head($product);
	$titre=$langs->trans("CardProduct".$product->type);
	$picto=($product->type== Product::TYPE_SERVICE?'service':'product');
	dol_fiche_head($head, 'discountrules', $titre, -1, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$shownav = 0; // remove this because not implemented yet
	if ($user->socid && ! in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) $shownav=0;

	dol_banner_tab($product, 'ref', $linkback, $shownav, 'ref');
}

if(!empty($fk_company)){

	$societe = new Societe($db);
	$res = $societe->fetch($fk_company);
    if($res > 0) {
        $object->fk_soc = $fk_company;
        $object->initFieldsParams();
        $head = societe_prepare_head($societe);
        $titre = $langs->trans("ThirdParty");
        $picto = $societe->picto;
        dol_fiche_head($head, 'discountrules', $titre, -1, $picto);

        $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        dol_banner_tab($societe, 'ref', $linkback, 0, 'ref');
    }
}


$arrayofselected=is_array($toselect)?$toselect:array();

$param='';
if (!empty($fk_product)) $param .= '&fk_product=' . $fk_product;
if (!empty($fk_company)) $param .= '&fk_company=' . $fk_company;
if (!empty($displayRulesWithoutProduct)) $param .= '&display-rules-without-product=' . $displayRulesWithoutProduct;
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
foreach($search as $key => $val)
{
	if (is_array($search[$key]) && count($search[$key])) foreach ($search[$key] as $skey) $param .= '&search_'.$key.'[]='.urlencode($skey);
	else $param .= '&search_'.$key.'='.urlencode($search[$key]);
}
if ($optioncss != '')     $param .= '&optioncss='.urlencode($optioncss);

if ($searchCategoryProductOperator == 1) $param .= "&search_category_product_operator=".urlencode($searchCategoryProductOperator);
foreach ($TCategoryProduct  as $searchCategoryProduct) {
	$param .= "&search_TCategoryProduct[]=".urlencode($searchCategoryProduct);
}

if ($searchCategorySocieteOperator == 1) $param .= "&search_category_societe_operator=".urlencode($searchCategorySocieteOperator);
foreach ($TCategoryCompany  as $searchCategoryProduct) {
	$param .= "&search_TCategoryCompany[]=".urlencode($searchCategoryProduct);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$arrayofmassactions =  array();
if ($user->hasRight('discountrules', 'delete')) $arrayofmassactions['delete']=$langs->trans("Delete");
if ($massaction == 'presend') $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
// print '<input type="hidden" name="token" value="'.newToken().'">'; // Dolibarr V12
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="fk_product" value="'.$fk_product.'">'; // utilisé dans le cas d'un onglet produit
print '<input type="hidden" name="fk_company" value="'.$fk_company.'">'; // utilisé dans le cas d'un onglet produit
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

// LIST TITLE BUTTONS
$newcardbutton='';
$urlNew = dol_buildpath('discountrules/discountrule_card.php',1).'?action=create';
if(!empty($fk_product))
{
	$urlNew.= '&fk_product=' . intval($fk_product) ;
}
if(!empty($fk_company))
{
	$urlNew.= '&fk_company=' . intval($fk_company) ;
}

if(function_exists('dolGetButtonTitle'))
{
	$newcardbutton.= dolGetButtonTitle($langs->trans('NewDiscountRule'), '', 'fa fa-plus-circle', $urlNew, '', $user->hasRight('discountrules', 'create'));
}
elseif ($user->hasRight('discountrules', 'create'))
{
	$newcardbutton.= '<a class="butActionNew" href="'.$urlNew.'"><span class="valignmiddle">'.$langs->trans('NewDiscountRule').'</span>';
	$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
	$newcardbutton.= '</a>';
}
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'discountrules@discountrules', 0, $newcardbutton, '', $limit);


if ($search_all)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
$moreforfilter.= '</div>';*/

$parameters=array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (! empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$countNbCols = 0;
foreach($object->fields as $key => $val){
	if(empty($val['visible'])){ continue; }
	if (! empty($arrayfields['t.'.$key]['checked'])){
		$countNbCols++;
	}
}

if (isModEnabled('categorie'))
{
	$moreforfilter = true;
	print '<div class="liste_titre liste_titre_bydiv centpercent" >';


	print '<table style="width: 100%" >';
	if(empty($fk_product)) {
		// Filtre catégories produit
		print '<tr>';
		print '<td>';
		print $langs->trans($object->fields['all_category_product']['label']);
		print '</td><td style="min-width: 300px;">';
		$object->TCategoryProduct = $TCategoryProduct;
		print $object->showInputField($object->fields['all_category_product'], 'all_category_product', $TCategoryProduct, '', '', 'search_', 'minwidth300', 1);
		print '</td>';
//	print '<td>';
//  print ' <label><input type="checkbox" class="valignmiddle" name="search_category_product_operator" value="1"'.($searchCategoryProductOperator == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories').'</label>';
//	print '</td>';
		print '</tr>';
	}
	if(empty($fk_company)) {
        // Filtre catégories societe
        print '<tr>';
        print '<td  >';
        print $langs->trans($object->fields['all_category_company']['label']);
        print '</td><td style="min-width: 300px;">';
        $object->TCategoryCompany = $TCategoryCompany;
        print $object->showInputField($object->fields['all_category_company'], 'all_category_company', $TCategoryCompany, '', '', 'search_', 'minwidth300', 1);
        print '</td>';
//	print '<td>';
//	print ' <label><input type="checkbox" class="valignmiddle" name="search_category_societe_operator" value="1"'.($searchCategorySocieteOperator == 1 ? ' checked="checked"' : '').'/> '.$langs->trans('UseOrOperatorForCategories').'</label>';
//	print '</td>';
        print '</tr>';
    }




	print '</table>';

	print '</div>';
}

print '<div class="div-table-responsive">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach($object->fields as $key => $val)
{
	if(empty($val['visible'])){ continue; }

	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';

	if (! empty($arrayfields['t.'.$key]['checked']))
	{
		print '<td class="nowrap liste_titre'.($cssforfield ? ' '.$cssforfield : '').'">';

		if (!in_array($key, array('all_category_product', 'all_category_company'))) {
            if(empty($search[$key])) $search[$key] = '';
			if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) print Form::selectarray('search_'.$key, $val['arrayofkeyval'], $search[$key], 1, 0, 0, '', 1, 0, 0, '', 'maxwidth75');
			elseif (strpos($val['type'], 'integer:') === 0 && !in_array($key, array('fk_company')) || in_array($key, array('fk_country', 'fk_product'))) {
				$object->{$key} = $search[$key];
				print $object->showInputField($val, $key, $search[$key], '', '', 'search_', 'maxwidth150', 1);

				if ($key == 'fk_product') {
					print '<input class="classfortooltip" title="'.$langs->trans('DoNotDisplayRulesWithProduct').'" type="checkbox" name="display-rules-without-product" value="1" '.(!empty($displayRulesWithoutProduct)?'checked':'').'>';
				}
			}
			elseif (!preg_match('/^(date|timestamp)/', $val['type'])) print '<input type="text" class="flat maxwidth75" name="search_'.$key.'" value="'.dol_escape_htmltag($search[$key]).'">';
		}

		print '</td>';
	}
}

?>
	<style type="text/css" >
		#s2id_search_fk_category_product{
			max-width: 300px;
		}
	</style>
<?php
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters=array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print '</tr>'."\n";


// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
foreach($object->fields as $key => $val)
{
	if(empty($val['visible'])){ continue; }
	$cssforfield = (empty($val['css']) ? '' : $val['css']);
	if ($key == 'fk_status') $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
	elseif (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
	elseif (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $val['label'] != 'TechnicalID') $cssforfield .= ($cssforfield ? ' ' : '').'right';
	if (!empty($arrayfields['t.'.$key]['checked']))
	{
		print getTitleFieldOfList($arrayfields['t.'.$key]['label'], 0, $_SERVER['PHP_SELF'], 't.'.$key, '', $param, ($cssforfield ? 'class="'.$cssforfield.'"' : ''), $sortfield, $sortorder, ($cssforfield ? $cssforfield.' ' : ''))."\n";
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";


// Detect if we need a fetch on each output line
$needToFetchEachLine=0;
if (!empty($extrafields->attributes[$object->table_element]['computed']) && is_array($extrafields->attributes[$object->table_element]['computed']) && count($extrafields->attributes[$object->table_element]['computed']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['computed'] as $key => $val)
	{
		if (preg_match('/\$object/',$val)) $needToFetchEachLine++;  // There is at least one compute field that use $object
	}
}


// Loop on record
// --------------------------------------------------------------------
$i=0;
$totalarray=array();
while ($i < ($limit ? min($num, $limit) : $num))
{
	$obj = $db->fetch_object($resql);
	if (empty($obj)) break; // Should not happen

	// Store properties in $object
	$object->setVarsFromFetchObj($obj);

	$object->fetch_categoryCompany();
	$object->fetch_categoryProduct();

	// Show here line of result
	print '<tr class="oddeven" id="discountrule-row-'.$object->id.'" >';
    if(empty($totalarray['nbfield']))$totalarray['nbfield']=0;
	foreach($object->fields as $key => $val)
	{
		if(empty($val['visible'])){ continue; }
		$cssforfield = (empty($val['css']) ? '' : $val['css']);
		if (in_array($val['type'], array('date', 'datetime', 'timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'center';
		elseif ($key == 'fk_status') $cssforfield .= ($cssforfield ? ' ' : '').'center';

		if (in_array($val['type'], array('timestamp'))) $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';
		elseif ($key == 'label') $cssforfield .= ($cssforfield ? ' ' : '').'nowrap';

		if (in_array($val['type'], array('double(24,8)', 'double(6,3)', 'integer', 'real', 'price')) && $key != 'fk_status') $cssforfield .= ($cssforfield ? ' ' : '').'right';
		//if (in_array($key, array('fk_soc', 'fk_user', 'fk_warehouse'))) $cssforfield = 'tdoverflowmax100';

		if (!empty($arrayfields['t.'.$key]['checked']))
		{
			print '<td'.($cssforfield ? ' class="'.$cssforfield.'"' : '').'>';
			if ($key == 'fk_status') print $object->getLibStatut(5);
			elseif ($key == 'label') print $object->getNomUrl(1);
			else print $object->showOutputField($val, $key, $object->$key, '');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! empty($val['isameasure']))
			{
				if (! $i) $totalarray['pos'][$totalarray['nbfield']]='t.'.$key;
				$totalarray['val']['t.'.$key] += $object->$key;
			}
		}
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'object'=>$object, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
		$selected=0;
		if (in_array($object->id, $arrayofselected)) $selected = 1;
		print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}
	print '</td>';
	if (! $i) $totalarray['nbfield']++;

	print '</tr>'."\n";

	$i++;
}

// Show total line
if(file_exists(DOL_DOCUMENT_ROOT. '/core/tpl/list_print_total.tpl.php')) {
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
}

// If no record found
if ($num == 0)
{
	$colspan=1;
	foreach($arrayfields as $key => $val) { if (! empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}


$db->free($resql);

$parameters=array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
