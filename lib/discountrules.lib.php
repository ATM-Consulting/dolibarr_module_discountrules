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
