<?php
/* Copyright (C) 2018 John BOTELLA
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    lib/discountrules.lib.php
 * \ingroup discountrules
 * \brief   Example module library.
 *
 * Put detailed description here.
 */

/**
 * Prepare admin pages header
 *
 * @return array
 */
function discountrulesAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("discountrules@discountrules");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/discountrules/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("ModulediscountrulesSettings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/discountrules/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, false, $head, $h, 'discountrules');

	return $head;
}

function discountrulesPrepareHead($object)
{
    global $langs, $conf;

    $langs->load("discountrules@discountrules");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/discountrules/discountrule_card.php", 1).'?id='.$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@discountrules:/discountrules/mypage.php?id=__ID__'
        //); // to remove a tab
        complete_head_from_modules($conf, $langs, $object, $head, $h, 'discountrules');

        return $head;
}



function discountRulesBannerTab(DiscountRule $object, $showNav = 1){
	global $langs, $form, $conf, $db;

	$onlybanner = 0;

	$linkbackUrl = dol_buildpath('/discountrules/discountrule_list.php',1) . '?t=t' . (! empty($socid) ? '&socid=' . $socid : '');
	if(!empty($object->fk_product)){ $linkbackUrl.= '&fk_product=' . intval($object->fk_product) ; }
	$linkback = '<a href="' . $linkbackUrl . '">' . $langs->trans("BackToList") . '</a>';


	$morehtmlref = '';


	$morehtmlref.='<div class="refidno">';

	if(!empty($object->fk_product))
	{

		if($object->product && (!is_object($object->product) || $object->id < 1) ){
			$object->product = new Product($db);
			if($object->product->fetch($object->fk_product) < 1)
			{
				$object->product = false;
			}
		}

		if($object->product){

			// Product / Service
			$morehtmlref.= $object->product->getNomUrl(2) . ' : ' . $object->product->label;
		}
	}

	if(!empty($object->fk_soc))
	{
		$soc = new Societe($object->db);
		if($soc->fetch($object->fk_soc)>0)
		{
			// Thirdparty
			$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(2);
		}
	}

	$morehtmlref.='</div>';

	$morehtmlstatus = $morehtmlright = $morehtmlleft = '';


	dol_banner_tab($object, 'id', $linkback, $showNav , 'rowid', 'label', $morehtmlref, '', 0, $morehtmlleft, $morehtmlstatus, 0, $morehtmlright);
}

/**
 * TODO : cette maniere contre intuitive de récupérer le libellé est tiré de la card des tiers, j'ai préféré factoriser pour pouvoir facilement le modifier plus tard vu que j'aime pas le style...
 * @param $fk_c_typent
 * @return bool|mixed
 */
function getTypeEntLabel($fk_c_typent){
	global $db, $langs;

	if(empty($fk_c_typent)){
		return $langs->trans("AllTypeEnt");
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

	$fk_c_typent = intval($fk_c_typent);

	$formcompany = new FormCompany($db);
	$arr = $formcompany->typent_array();
	if(isset($arr[$fk_c_typent])){
		return $arr[$fk_c_typent];
	}

	return false;
}

/**
 * Return a compiled message of json product-discount info
 * @param Translate $langs
 * @param $jsonResponse
 * @param $action
 *
 * @return string
 */
function getDiscountRulesInterfaceMessageTpl(Translate $langs, $jsonResponse, $action){

	global $hookmanager, $db;

	$return = '';

	$TprepareTpMsg = array();

	if($jsonResponse->result && $jsonResponse->element === "discountrule") {

		$discount = new DiscountRule($db);

		// Title
		$TprepareTpMsg['title'] = $langs->transnoentities('Discountrule') . " : ";
		$TprepareTpMsg['label'] = "<strong>" . $jsonResponse->label . "</strong>";

		if(isset($discount->fields['priority_rank']['arrayofkeyval'][$jsonResponse->priority_rank])) {
			$TprepareTpMsg['priority'] = $langs->transnoentities('PriorityRuleRank') . " : ";
			if($jsonResponse->priority_rank>0){
				$TprepareTpMsg['priority'].= "<strong>" . $langs->transnoentities($discount->fields['priority_rank']['arrayofkeyval'][$jsonResponse->priority_rank]) . "</strong>";
			}else{
				$TprepareTpMsg['priority'].= $langs->transnoentities($discount->fields['priority_rank']['arrayofkeyval'][$jsonResponse->priority_rank]);
			}
		}

		if ($jsonResponse->fk_project > 0) {
			$TprepareTpMsg['InfoProject'] =$langs->transnoentities('InfosProject');
		}

		if ($jsonResponse->product_price > 0) {
			$TprepareTpMsg['product_price'] =  $langs->transnoentities('Price') . " : " . $jsonResponse->product_price;
		}

		if ($jsonResponse->product_reduction_amount > 0) {
			$TprepareTpMsg['product_reduction_amount'] = $langs->transnoentities('ReductionAmount') . ": -" . $jsonResponse->product_reduction_amount;
		}

		$TprepareTpMsg['discount'] = $langs->transnoentities('Discount') . " : " . $jsonResponse->reduction . "%" ;
		$TprepareTpMsg['FromQty']  =  $langs->transnoentities('FromQty') . " : " . $jsonResponse->from_quantity ;
		$TprepareTpMsg['ThirdPartyType'] = 	 $langs->transnoentities('ThirdPartyType') . " : " . $jsonResponse->typentlabel;

		if ($jsonResponse->fk_product > 0) {
			$TprepareTpMsg['fk_product'] = $langs->transnoentities('Product') . " : " . $jsonResponse->match_on->product_info;
		}

		$TprepareTpMsg['ProductCategory'] = $langs->transnoentities('ProductCategory') . " : " . $jsonResponse->match_on->category_product ;
		$TprepareTpMsg['ClientCategory'] = $langs->transnoentities('ClientCategory') . " : " . $jsonResponse->match_on->category_company ;
		$TprepareTpMsg['Customer']  = $langs->transnoentities('Customer') . " : " . $jsonResponse->match_on->company;

	}
	else if($jsonResponse->result && ($jsonResponse->element === "facture" || $jsonResponse->element === "commande" || $jsonResponse->element === "propal"  ))
	{
		$TprepareTpMsg['label'] = "<strong>" . $jsonResponse->label . "</strong>";
		$TprepareTpMsg['discount'] 	= $langs->transnoentities('Discount') . " : " . $jsonResponse->reduction . "%" ;
		$TprepareTpMsg['subprice'] 	= $langs->transnoentities('PriceUHT') . " : " . price($jsonResponse->subprice);
		$TprepareTpMsg['Date'] 		= $langs->transnoentities('Date') . " : " . $jsonResponse->date_object_human;
		$TprepareTpMsg['Qty'] 		= $langs->transnoentities('Qty') . " : " . $jsonResponse->qty;
	}
	else
	{
		if ($jsonResponse->defaultCustomerReduction > 0){
			$TprepareTpMsg['CustomerReduction'] = $langs->transnoentities('percentage')." : " .  $jsonResponse->defaultCustomerReduction + "%"
				. "<br/>"  . $langs->transnoentities('DiscountruleNotFoundUseCustomerReductionInstead');
		}else{
			$TprepareTpMsg['CustomerReduction'] = $langs->transnoentities('DiscountruleNotFound');
		}
	}

	if ($jsonResponse->fk_product > 0 && doubleval($jsonResponse->standard_product_price > 0)) {
		$TprepareTpMsg['InfosProduct'] = "<strong>" . $langs->transnoentities('InfosProduct') . "</strong>";
		$TprepareTpMsg['productPrice'] = $langs->transnoentities('ProductPrice') . " : " . $jsonResponse->standard_product_price;
	}

	if(!empty($TprepareTpMsg)){
		foreach($TprepareTpMsg as $key => $msg ){

			if(!empty($return)){
				$return .= '<br/>';
			}

			if($key == 'InfosProduct'){
				$return .= '<br/>';
			}

			$return .= $msg;
		}
	}


	// Note that $action and $object may be modified by hook
	// Utilisation initiale : interception pour remplissage customisé de $jsonResponse->tpMsg

	$parameters = array(
		'TprepareTpMsg' => $TprepareTpMsg,
	);

	$reshook = $hookmanager->executeHooks('discountRulesInterfaceMessage', $parameters, $jsonResponse, $action);
	if($reshook>0){
		$return = $hookmanager->resPrint;
	}
	else if ($reshook < 0)
	{
		// TODO : manage errors
		// $hookmanager->error;
		// $hookmanager->errors;
	}
	else{
		$return.= $hookmanager->resPrint;
	}


	return $return;
}

/**
 * return an ajax ready search table for product
 *	@param	string			$pageUrl				Page URL (in most cases provided with $_SERVER["PHP_SELF"])
 * @return string
 */
function discountProductSearchForm($pageUrl = ''){
global $langs, $conf, $db, $action;

	$output = '';

	if(empty($pageUrl)){
		$pageUrl = $_SERVER["PHP_SELF"];
	}

	// Load translation files required by the page
	$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies', 'stocks', 'margins'));

	if(!class_exists('Product')){
		include_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
	if (!empty($conf->categorie->enabled)){
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	}

	$form = new Form($db);

	$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : 10;
	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');
	$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
	if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
	$offset = $limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;
	if (!$sortfield) $sortfield = "p.ref";
	if (!$sortorder) $sortorder = "ASC";


	// LES FILTRES
	$search_type = '';
	$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
	$search_ref = GETPOST("search_ref", 'alpha');
	$search_supplierref = GETPOST("search_supplierref", 'alpha');
	$search_barcode = GETPOST("search_barcode", 'alpha');
	$search_label = GETPOST("search_label", 'alpha');

	$search_type = -1; // TODO $search_type = GETPOST("search_type", 'int');
//	$search_vatrate = GETPOST("search_vatrate", 'alpha');
	$searchCategoryProductOperator = (GETPOST('search_category_product_operator', 'int') ? GETPOST('search_category_product_operator', 'int') : 0);
	$searchCategoryProductList = GETPOST('search_category_product_list', 'array');
	$search_tosell = 1; // GETPOST("search_tosell", 'int'); // TODO
//	$search_tobuy = GETPOST("search_tobuy", 'int'); // TODO
	$fourn_id = GETPOST("fourn_id", 'int');
	$catid = GETPOST('catid', 'int');
//	$search_tobatch = GETPOST("search_tobatch", 'int');
//	$optioncss = GETPOST('optioncss', 'alpha');
	$type = GETPOST("type", "int");


	$fk_company = GETPOST("fk_company", "int");
	$fk_project = GETPOST("fk_project", "int");

	$element = GETPOST("element", 'aZ09');
	$fk_element = GETPOST("fk_element", "int");

	$object = discountruleObjectAutoLoad($element, $db);
	if($object > 0){
		if($object->fetch($fk_element)){
			$object->fetch_thirdparty();
			if($object->socid>0){
				$fk_company = $object->socid;
			}
			if($object->fk_project>0){
				$fk_project = $object->fk_project;
			}
		}
	}


	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&sall=".urlencode($sall);
	if ($searchCategoryProductOperator == 1) $param .= "&search_category_product_operator=".urlencode($searchCategoryProductOperator);
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		$param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
	}
	if ($search_ref) $param = "&search_ref=".urlencode($search_ref);
	if ($search_supplierref) $param = "&search_supplierref=".urlencode($search_supplierref);
	if ($fk_company) $param.= "&socid=".urlencode($fk_company);
//	if ($search_ref_supplier) $param = "&search_ref_supplier=".urlencode($search_ref_supplier);
	if ($search_barcode) $param .= ($search_barcode ? "&search_barcode=".urlencode($search_barcode) : "");
	if ($search_label) $param .= "&search_label=".urlencode($search_label);
	if ($search_tosell != '') $param .= "&search_tosell=".urlencode($search_tosell);
	if ($fourn_id > 0) $param .= ($fourn_id ? "&fourn_id=".$fourn_id : "");
	//if ($seach_categ) $param.=($search_categ?"&search_categ=".urlencode($search_categ):"");
	if ($type != '') $param .= '&type='.urlencode($type);
	if ($search_type != '') $param .= '&search_type='.urlencode($search_type);

	// REQUETTE SQL

	// List of fields to search into when doing a "search in all"
	$fieldstosearchall = array('p.ref','p.label','p.description',"p.note");

	// multilang
	if (!empty($conf->global->MAIN_MULTILANGS)){
		$fieldstosearchall+= array('pl.label','pl.description','pl.note');
	}

	if (!empty($conf->barcode->enabled)) {
		$fieldstosearchall+=  array('p.barcode','pfp.barcode');
	}

	// Filter on supplier
	if (!empty($conf->fournisseur->enabled)){
		$fieldstosearchall+=  array('pfp.ref_fourn');
	}

	// SELECT PART
	$sqlSelect = ' DISTINCT p.rowid, p.ref, p.label ';
	if (!empty($conf->global->PRODUCT_USE_UNITS))   $sqlSelect .= ' ,cu.label as cu_label';

	// SELECT COUNT PART
	$sqlSelectCount = ' COUNT(DISTINCT p.rowid) as nb_results ';

	$sql = ' FROM '.MAIN_DB_PREFIX.'product as p ';
	if (!empty($searchCategoryProductList) || !empty($catid)) $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON (p.rowid = cp.fk_product) "; // We'll need this table joined to the select in order to filter by categ
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (pfp.fk_product = p.rowid) ";
	// multilang
	if (!empty($conf->global->MAIN_MULTILANGS)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON (pl.fk_product = p.rowid AND pl.lang = '".$langs->getDefaultLang()."' )";
	if (!empty($conf->global->PRODUCT_USE_UNITS))   $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_units cu ON (cu.rowid = p.fk_unit)";

	$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
	if (isset($search_tosell) && dol_strlen($search_tosell) > 0 && $search_tosell != -1) $sql .= " AND p.tosell = ".((int) $search_tosell);
	if (isset($search_tobuy) && dol_strlen($search_tobuy) > 0 && $search_tobuy != -1)   $sql .= " AND p.tobuy = ".((int) $search_tobuy);

	if ($sall) $sql .= natural_search($fieldstosearchall, $sall);
	// if the type is not 1, we show all products (type = 0,2,3)
//	if (dol_strlen($search_type) && $search_type != '-1'){
//		if ($search_type == 1) $sql .= " AND p.fk_product_type = 1";
//		else $sql .= " AND p.fk_product_type <> 1";
//	}

	if ($search_ref)     $sql .= natural_search('p.ref', $search_ref);
	if ($search_label)   $sql .= natural_search('p.label', $search_label);
	if ($search_barcode) $sql .= natural_search('p.barcode', $search_barcode);
	// Filter on supplier
	if (!empty($conf->fournisseur->enabled) && !empty($search_supplierref)){
		$sql .= natural_search('pfp.ref_fourn', $search_supplierref);
	}

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

	$output.=  '<form id="product-search-dialog-form">';

	$output.=  '<input type="hidden" name="token" value="'.newToken().'">';
	$output.=  '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	$output.= '<input type="hidden" name="action" value="product-search-form">';
	$output.= '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	$output.= '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//$output.= '<input type="hidden" name="page" value="'.$page.'">';
	$output.= '<input type="hidden" name="type" value="'.$type.'">';
	$output.= '<input type="hidden" name="fk_company" value="'.$fk_company.'">';
	$output.= '<input type="hidden" id="discountrules-form-element" name="element" value="'.$element.'">';
	$output.= '<input type="hidden" id="discountrules-form-fk-element" name="fk_element" value="'.$fk_element.'">';
	$output.= '<input type="hidden" id="discountrules-form-fk-project" name="fk_project" value="'.$fk_project.'">';
	$output.= '<input type="hidden" id="discountrules-form-default-customer-reduction" name="default_customer_reduction" value="'.floatval($object->thirdparty->remise_percent).'">';

	$querySearchRes = $db->query('SELECT '.$sqlSelectCount.' '.$sql);
	$globalCountResult = 0;
	$curentCountResult = 0;
	if ($querySearchRes) {
		$obj = $db->fetch_object($querySearchRes);
		$globalCountResult = $obj->nb_results;

		$querySearchRes = $db->query('SELECT '.$sqlSelect.' '.$sql.$db->plimit($limit + 1, $offset));
		if ($querySearchRes) {
			$curentCountResult = $db->num_rows($querySearchRes);
		}
	}

	$morehtmlcenter= '<div class="discountrules-global-search-container" >';
	$morehtmlcenter.= '<input name="sall" value="'.dol_htmlentities($sall).'" id="search-all-form-input" class="discountrules-global-search-input" placeholder="'.$langs->trans('Search').'" autocomplete="off">';
	$morehtmlcenter.= '</div>';

	$picto = 'product';
	if ($type == 1) $picto = 'service';

	ob_start(); // parceque dolibarr aime tellement le print ...
	print_barre_liste($langs->trans('AdvanceProductSearch'), $page, $pageUrl, $param, $sortfield, $sortorder, $morehtmlcenter, $curentCountResult, $globalCountResult, $picto, 0, '', '', $limit, 0, 0, 0);
	$output.= ob_get_contents();
	ob_end_clean();



	if($globalCountResult > 0){
		$output.= '<div class="discountrules-productsearch__results-count">';
		if($globalCountResult>1){
			$output.= $langs->trans('resultsDisplayForNbResultsFounds', min($limit,$globalCountResult), $globalCountResult );
		}
		else{
			$output.= $langs->trans('OneResultDisplayForOneResultFounds', min($limit,$globalCountResult), $globalCountResult );
		}
		$output.= '</div>';
	}

	$moreforfilter = '';
	// Filter on supplier
	if (!empty($conf->fournisseur->enabled))
	{
		$moreforfilter .= '<div class="divsearchfield" >';
		$moreforfilter .= $langs->trans('Supplier').': ';
		$moreforfilter .= $form->select_company($fourn_id, 'fourn_id', '', 1, 'supplier');
		$moreforfilter .= '</div>';

		$moreforfilter .= '<div class="divsearchfield" >';
		$moreforfilter .= $langs->trans('SupplierRef').': ';
		$moreforfilter .= '<input type="text" name="search_supplierref" value="'.dol_htmlentities($search_supplierref).'" />';
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

//	$parameters = array();
//	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
//	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
//	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
		$output.= '<div class="liste_titre liste_titre_bydiv centpercent">';
		$output.= $moreforfilter;
		$output.= '</div>';
	}

	$colnumber = 8;

	$output.= '<table class="noborder centpercent advance-search-product-results" >';
	$output.= '<thead>';

	$output.= '<tr class="discount-search-product-row --search liste_titre">';

	$output.= '	<th class="discount-search-product-col --ref" >';
	$output.= ' <input type="text" class="flat"  name="search_ref" value="'.dol_htmlentities($search_ref).'" placeholder="'.$langs->trans('SearchRef').'" />';
	$output.= '	</th>';

	$output.= '	<th class="discount-search-product-col --label" >';
	$output.= ' <input type="text" class="flat"  name="search_label" value="'.dol_htmlentities($search_label).'" placeholder="'.$langs->trans('SearchLabel').'" />';
	$output.= '	</th>';


	if($conf->stock->enabled){
		$output.= '	<th class="discount-search-product-col --stock-reel center" ></th>';
		$output.= '	<th class="discount-search-product-col --stock-theorique center" ></th>';
	}

	if ($conf->fournisseur->enabled) {
		$output .= '	<th class="discount-search-product-col --buy-price" ></th>';
	}

	$output.= '	<th class="discount-search-product-col --subprice" ></th>';
	$output.= '	<th class="discount-search-product-col --discount" ></th>';
	$output.= '	<th class="discount-search-product-col --finalsubprice" ></th>';
	$output.= '	<th class="discount-search-product-col --qty" ></th>';

	if (!empty($conf->global->PRODUCT_USE_UNITS)) {
		$output.= '<th class="discount-search-product-col --unit" >';
		$output.= '</th>';
	}

	$output.= '	<th class="discount-search-product-col --finalprice" ></th>';
	$output.= '	<th class="discount-search-product-col --action" >';

	$output.= '	</th>';
	$output.= '</tr>';


	//---------------------------
	// LES TITTRES DES COLONNES -
	//---------------------------

	$classForSortLink = "discount-search-product-sort-link";

	$output.= '<tr class="discount-search-product-row --title liste_titre">';
	$output.= '	<th class="discount-search-product-col --ref" >'
		. getDialogColSortLink($langs->trans('Ref'), $pageUrl, "p.ref", $param, $sortfield, $sortorder, $classForSortLink)
		.'</th>';

	$output.= '	<th class="discount-search-product-col --label" >'
		. getDialogColSortLink($langs->trans('Label'), $pageUrl, "p.label", $param, $sortfield, $sortorder, $classForSortLink)
		.'</th>';

	if($conf->stock->enabled){
		$output.= '	<th class="discount-search-product-col --stock-reel center" >'.$langs->trans('RealStock').'</th>';
		$output.= '	<th class="discount-search-product-col --stock-theorique center" >'.$langs->trans('VirtualStock').'</th>';
		$colnumber+=2;
	}

	if ($conf->fournisseur->enabled) {
		$colnumber++;
		$output .= '	<th class="discount-search-product-col --buy-price" >' . $langs->trans('BuyPrice') . '</th>';
	}
	$output.= '	<th class="discount-search-product-col --subprice" >'.$langs->trans('Price').'</th>';
	$output.= '	<th class="discount-search-product-col --discount" >'.$langs->trans('Discount').'</th>';
	$output.= '	<th class="discount-search-product-col --finalsubprice" >'.$langs->trans('FinalDiscountSubPrice').'</th>';
	$output.= '	<th class="discount-search-product-col --qty" >'.$langs->trans('Qty').'</th>';

	if (!empty($conf->global->PRODUCT_USE_UNITS)) {
		$colnumber++;
		$output.= '<th class="discount-search-product-col --unit" >';
		$output.= $langs->trans('Unit');
		$output.= '</th>';
	}
	$output.= '	<th class="discount-search-product-col --finalprice" >'.$langs->trans('FinalDiscountPrice').'</th>';
	$output.= '	<th class="discount-search-product-col --action" >';
	$output.= '		<div class="nowrap">';
	$output.= '			<button type="submit" class="liste_titre button_search" name="button_search_x" value="x">';
	$output.= '				<span class="fa fa-search"></span>';
	$output.= '			</button>';
	$output.= '			<button type="submit" class="liste_titre button_removefilter" name="button_removefilter_x" value="x">';
	$output.= '				<span class="fa fa-remove"></span>';
	$output.= '			</button>';
	$output.= '		</div>';
	$output.= '	</th>';
	$output.= '</tr>';
	$output.= '</thead>';
	$output.= '<tbody>';

	$sqlList = 'SELECT '.$sqlSelect.' '
		.$sql.$db->order($sortfield, $sortorder)
		.$db->plimit($limit + 1, $offset);

	$querySearchRes = $db->query($sqlList);

	if ($querySearchRes)
	{
		if($curentCountResult > 0){
			while ($obj = $db->fetch_object($querySearchRes)){
				$product = new Product($db);
				$resProd = $product->fetch($obj->rowid);
				if($resProd > 0){
					$product->load_stock();
					$output.= '<tr class="discount-search-product-row --data" data-product="'.$product->id.'"  >';
					$output.= '<td class="discount-search-product-col --ref" >'. $product->getNomUrl(1).'</td>';
					$output.= '<td class="discount-search-product-col --label" >'. $product->label.'</td>';
					if($conf->stock->enabled) {
						$output .= '<td class="discount-search-product-col --stock-reel" >' . $product->stock_reel . '</td>';
						$output .= '<td class="discount-search-product-col --stock-theorique" >' . $product->stock_theorique . '</td>';
					}

					if ($conf->fournisseur->enabled) {
						$output .= '<td class="discount-search-product-col --buy-price" >';
						$TFournPriceList = getFournPriceList($product->id);
						if (!empty($TFournPriceList)) {
//						$output.= '<div class="default-visible" >'.price($product->pmp).'</div>';
//						$output.= '<div class="default-hidden" >';

							$selectArray = array();
							$idSelected = '';
							foreach ($TFournPriceList as $TpriceInfos) {
								$selectArray[$TpriceInfos['id']] = $TpriceInfos['label'];
								if ($TpriceInfos['id'] == 'pmpprice' && !empty($TpriceInfos['price'])) {
									$idSelected = 'pmpprice';
								}
							}


							$key_in_label = 0;
							$value_as_key = 0;
							$moreparam = '';
							$translate = 0;
							$maxlen = 0;
							$disabled = 0;
							$sort = '';
							$morecss = 'search-list-select';
							$addjscombo = 0;
							$output .= $form->selectArray('prodfourprice-' . $product->id, $selectArray, $idSelected, 0, $key_in_label, $value_as_key, $moreparam, $translate, $maxlen, $disabled, $sort, $morecss, $addjscombo);
//						$output.= '</div>';
						} else {
							$output .= price($product->pmp);
						}
						$output .= '</td>';
					}


					// Search discount
					$discountSearch = new DiscountSearch($db);
					$subprice = DiscountRule::getProductSellPrice($product->id, $fk_company);
					$discountSearchResult = $discountSearch->search(0, $product->id, $fk_company, $fk_project);
					if ($discountSearchResult->result && !empty($discountSearchResult->subprice)) {
						// Mise en page du résultat
						$discountSearchResult->tpMsg = getDiscountRulesInterfaceMessageTpl($langs, $discountSearchResult, $action);
					}

					//
					$output.= '<td class="discount-search-product-col --subprice right nowraponall" >';
					if ($discountSearchResult->result && !empty($discountSearchResult->subprice)) {
						$subprice = $discountSearchResult->subprice;
					}
					$output.= '<input id="discount-prod-list-input-subprice-'.$product->id.'"  data-product="'.$product->id.'"   class="discount-prod-list-input-subprice right on-update-calc-prices" type="number" step="any" min="0" maxlength="8" size="3" value="'.$subprice.'" placeholder="x" name="prodsubprice['.$product->id.']" />';
					$output.= ' '.$langs->trans("HT");
					$output.= '</td>';

					// REDUCTION EN %
					$output.= '<td class="discount-search-product-col --discount center" >';
					$reduction = '';
					if (!empty($discountSearchResult->reduction)) {
						$reduction = $discountSearchResult->reduction;
					}
					$output.= '<input id="discount-prod-list-input-reduction-'.$product->id.'"  data-product="'.$product->id.'"   class="discount-prod-list-input-reduction center on-update-calc-prices" type="number" step="any" min="0" max="100" maxlength="3" size="3" value="'.$reduction.'" placeholder="%" name="prodreduction['.$product->id.']" />';
					$output.= '%';
					$output.= '</td>';

					// FINAL SUBPRICE AFTER REDUCTION
					$output.= '<td class="discount-search-product-col --finalsubprice right" >';
					if ($discountSearchResult->result) {
						$finalFubprice = $discountSearchResult->calcFinalSubprice();
					} else {
						$finalFubprice = DiscountRule::getProductSellPrice($product->id, $fk_company);
					}
					$output.= '<span id="discount-prod-list-final-subprice-'.$product->id.'"  class="final-subpriceprice" >'.price(round($finalFubprice, $conf->global->MAIN_MAX_DECIMALS_UNIT)).'</span> '.$langs->trans("HT");
					$output.= '</td>';

					// QTY
					$output.= '<td class="discount-search-product-col --qty" >';
					$qty = 1;
					$output.= '<input id="discount-prod-list-input-qty-'.$product->id.'"  data-product="'.$product->id.'"  class="discount-prod-list-input-qty center on-update-calc-prices" type="number" step="any" min="0" maxlength="8" size="3" value="'.$qty.'" placeholder="x" name="prodqty['.$product->id.']" />';
					$output.= '</td>';

					// UNITE
					if (!empty($conf->global->PRODUCT_USE_UNITS)) {
						$output.= '<td class="discount-search-product-col --unit" >';
						$output.= $product->getLabelOfUnit();
						$output.= '</td>';
					}

					$output.= '<td class="discount-search-product-col --finalprice right" >';
					$finalPrice = $finalFubprice*$qty;
					$output.= '<span id="discount-prod-list-final-price-'.$product->id.'"  class="final-price" >'.price(round($finalPrice, $conf->global->MAIN_MAX_DECIMALS_TOT)).'</span> '.$langs->trans("HT");
					$output.= '</td>';

					$output.= '<td class="discount-search-product-col --action" >';
//					$output.= '<div class="default-hidden" >';
					$output.= ' <button type="button" title="'.$langs->trans('ClickToAddProductInDocument').'"  data-product="'.$product->id.'" class="discount-prod-list-action-btn" ><span class="fa fa-plus add-btn-icon"></span> '.$langs->trans('Add').'</button>';
//					$output.= '</div>';
					$output.= '</td>';

					$output.= '</tr>';
				}
				else{
					$output.= '<tr class="discount-search-product-row">';
					$output.= '<td class="discount-search-product-col-error center" colspan="'.$colnumber.'">'. $product->errorsToString() .'</td>';
					$output.= '</tr>';

				}

			}
		}
		else{
			$output.= '<tr class="discount-search-product-row">';
			$output.= '<td class="discount-search-product-col-no-result" colspan="'.$colnumber.'">'. $langs->trans("NoResults") .'</td>';
			$output.= '</tr>';

		}
	}
	else{
		$output.= '<tr class="discount-search-product-row">';
		$output.= '<td class="discount-search-product-col-error" colspan="'.$colnumber.'">'. $db->error() .'</td>';
		$output.= '</tr>';
	}

	$output.= '</tbody>';
	$output.= '</table>';
	$output.= '</form>';

	return $output;
}

/**
 * @param $label		Translation key of field
 * @param $pageUrl		Url used when we click on sort picto
 * @param $field		Field to use for new sorting. Empty if this field is not sortable. Example "t.abc" or "t.abc,t.def"
 * @param $param		Add more parameters on sort url links ("" by default)
 * @param $sortfield	Current field used to sort (Ex: 'd.datep,d.id')
 * @param $sortorder	Current sort order (Ex: 'asc,desc')
 * @return string
 */
function getDialogColSortLink($label, $pageUrl, $field, $param, $sortfield, $sortorder, $moreClass = ""){
	$sortorder = strtoupper($sortorder);
	$tmpsortfield = explode(',', $sortfield);
	$sortfield1 = trim($tmpsortfield[0]); // If $sortfield is 'd.datep,d.id', it becomes 'd.datep'
	$tmpfield = explode(',', $field);
	$field1 = trim($tmpfield[0]); // If $field is 'd.datep,d.id', it becomes 'd.datep'


	$sortordertouseinlink = '';
	if ($field1 != $sortfield1) // We are on another field than current sorted field
	{
		if (preg_match('/^DESC/i', $sortorder))
		{
			$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
		}
		else		// We reverse the var $sortordertouseinlink
		{
			$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
		}
	}
	else                        // We are on field that is the first current sorting criteria
	{
		if (preg_match('/^ASC/i', $sortorder))	// We reverse the var $sortordertouseinlink
		{
			$sortordertouseinlink .= str_repeat('desc,', count(explode(',', $field)));
		}
		else
		{
			$sortordertouseinlink .= str_repeat('asc,', count(explode(',', $field)));
		}
	}
	$sortordertouseinlink = preg_replace('/,$/', '', $sortordertouseinlink);


	$out = '<a class="reposition '.$moreClass.'" href="'.$pageUrl.'?sortfield='.$field.'&sortorder='.$sortordertouseinlink.$param.'" >';
	$out.= $label;
	$out.= '</a>';

	return $out;
}

/**
 * Return an object
 *
 * @param string $objecttype Type of object ('invoice', 'order', 'expedition_bon', 'myobject@mymodule', ...)
 * @param $db
 * @return int object of $objecttype
 */
function discountruleObjectAutoLoad($objecttype, &$db)
{
	global $conf, $langs;

	$ret = -1;
	$regs = array();

	// Parse $objecttype (ex: project_task)
	$module = $myobject = $objecttype;

	// If we ask an resource form external module (instead of default path)
	if (preg_match('/^([^@]+)@([^@]+)$/i', $objecttype, $regs)) {
		$myobject = $regs[1];
		$module = $regs[2];
	}


	if (preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs))
	{
		$module = $regs[1];
		$myobject = $regs[2];
	}

	// Generic case for $classpath
	$classpath = $module.'/class';

	// Special cases, to work with non standard path
	if ($objecttype == 'facture' || $objecttype == 'invoice') {
		$classpath = 'compta/facture/class';
		$module='facture';
		$myobject='facture';
	}
	elseif ($objecttype == 'commande' || $objecttype == 'order') {
		$classpath = 'commande/class';
		$module='commande';
		$myobject='commande';
	}
	elseif ($objecttype == 'propal')  {
		$classpath = 'comm/propal/class';
	}
	elseif ($objecttype == 'supplier_proposal')  {
		$classpath = 'supplier_proposal/class';
	}
	elseif ($objecttype == 'shipping') {
		$classpath = 'expedition/class';
		$myobject = 'expedition';
		$module = 'expedition_bon';
	}
	elseif ($objecttype == 'delivery') {
		$classpath = 'livraison/class';
		$myobject = 'livraison';
		$module = 'livraison_bon';
	}
	elseif ($objecttype == 'contract') {
		$classpath = 'contrat/class';
		$module='contrat';
		$myobject='contrat';
	}
	elseif ($objecttype == 'member') {
		$classpath = 'adherents/class';
		$module='adherent';
		$myobject='adherent';
	}
	elseif ($objecttype == 'cabinetmed_cons') {
		$classpath = 'cabinetmed/class';
		$module='cabinetmed';
		$myobject='cabinetmedcons';
	}
	elseif ($objecttype == 'fichinter') {
		$classpath = 'fichinter/class';
		$module='ficheinter';
		$myobject='fichinter';
	}
	elseif ($objecttype == 'task') {
		$classpath = 'projet/class';
		$module='projet';
		$myobject='task';
	}
	elseif ($objecttype == 'stock') {
		$classpath = 'product/stock/class';
		$module='stock';
		$myobject='stock';
	}
	elseif ($objecttype == 'inventory') {
		$classpath = 'product/inventory/class';
		$module='stock';
		$myobject='inventory';
	}
	elseif ($objecttype == 'mo') {
		$classpath = 'mrp/class';
		$module='mrp';
		$myobject='mo';
	}

	// Generic case for $classfile and $classname
	$classfile = strtolower($myobject); $classname = ucfirst($myobject);
	//print "objecttype=".$objecttype." module=".$module." subelement=".$subelement." classfile=".$classfile." classname=".$classname;

	if ($objecttype == 'invoice_supplier') {
		$classfile = 'fournisseur.facture';
		$classname = 'FactureFournisseur';
		$classpath = 'fourn/class';
		$module = 'fournisseur';
	}
	elseif ($objecttype == 'order_supplier') {
		$classfile = 'fournisseur.commande';
		$classname = 'CommandeFournisseur';
		$classpath = 'fourn/class';
		$module = 'fournisseur';
	}
	elseif ($objecttype == 'stock') {
		$classpath = 'product/stock/class';
		$classfile = 'entrepot';
		$classname = 'Entrepot';
	}
	elseif ($objecttype == 'dolresource') {
		$classpath = 'resource/class';
		$classfile = 'dolresource';
		$classname = 'Dolresource';
		$module = 'resource';
	}


	if (!empty($conf->$module->enabled))
	{
		$res = dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
		if ($res)
		{
			if (class_exists($classname)) {
				return new $classname($db);
			}
		}
	}
	return $ret;
}

/**
 * Return a list a founr price info for product
 * @param $idprod
 * @return array [
 *            'id' 		=> (int) 	for price id || (string) like pmpprice,costprice
 *            'price' 	=> (double)
 *            'label' 	=> (string) a long label
 *            'title' 	=> (string) a short label
 *         ]
 */
function getFournPriceList($idprod){
	global $db, $langs, $conf;
	$prices = array();

	if ($idprod > 0)
	{
		$producttmp = new ProductFournisseur($db);
		$producttmp->fetch($idprod);

		$sorttouse = 's.nom, pfp.quantity, pfp.price';
		if (GETPOST('bestpricefirst')) $sorttouse = 'pfp.unitprice, s.nom, pfp.quantity, pfp.price';

		$productSupplierArray = $producttmp->list_product_fournisseur_price($idprod, $sorttouse); // We list all price per supplier, and then firstly with the lower quantity. So we can choose first one with enough quantity into list.
		if (is_array($productSupplierArray))
		{
			foreach ($productSupplierArray as $productSupplier)
			{
				$price = $productSupplier->fourn_price * (1 - $productSupplier->fourn_remise_percent / 100);
				$unitprice = $productSupplier->fourn_unitprice * (1 - $productSupplier->fourn_remise_percent / 100);

				$title = $productSupplier->fourn_name.' - '.$productSupplier->fourn_ref.' - ';

				if ($productSupplier->fourn_qty == 1)
				{
					$title .= price($price, 0, $langs, 0, 0, -1, $conf->currency)."/";
				}
				$title .= $productSupplier->fourn_qty.' '.($productSupplier->fourn_qty == 1 ? $langs->trans("Unit") : $langs->trans("Units"));

				if ($productSupplier->fourn_qty > 1)
				{
					$title .= " - ";
					$title .= price($unitprice, 0, $langs, 0, 0, -1, $conf->currency)."/".$langs->trans("Unit");
					$price = $unitprice;
				}

				$label = price($price, 0, $langs, 0, 0, -1, $conf->currency)."/".$langs->trans("Unit");
				if ($productSupplier->fourn_ref) $label .= ' ('.$productSupplier->fourn_ref.')';

				$prices[] = array("id" => $productSupplier->product_fourn_price_id, "price" => price2num($price, 0, '', 0), "label" => $label, "title" => $title); // For price field, we must use price2num(), for label or title, price()
			}
		}

		// After best supplier prices and before costprice
		if (!empty($conf->stock->enabled))
		{
			// Add price for pmp
			$price = $producttmp->pmp;
			$prices[] = array("id" => 'pmpprice', "price" => price2num($price), "label" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency)); // For price field, we must use price2num(), for label or title, price()
		}

		// Add price for costprice (at end)
		$price = $producttmp->cost_price;
		$prices[] = array("id" => 'costprice', "price" => price2num($price), "label" => $langs->trans("CostPrice").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency)); // For price field, we must use price2num(), for label or title, price()
	}

	return $prices;
}
