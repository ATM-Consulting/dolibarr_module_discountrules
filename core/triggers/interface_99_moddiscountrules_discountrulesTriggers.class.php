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
 * \file    core/triggers/interface_99_moddiscountrules_discountrulesTriggers.class.php
 * \ingroup discountrules
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_moddiscountrules_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for discountrules module
 */
class InterfaceDiscountrulesTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "discountrules triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'discountrules@discountrules';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$currentObject 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $currentObject, User $user, Translate $langs, Conf $conf)
	{

		if (!isModEnabled('discountrules')) {
			return 0; // If module is not enabled, we do nothing
		}

	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		#COMPATIBILITÉ V16
		if (in_array($action, array(
			'LINEBILL_UPDATE',
			'LINEORDER_UPDATE',
			'LINEBILL_UPDATE',
			'LINEBILL_SUPPLIER_UPDATE',
			'LINESUPPLIER_PROPOSAL_UPDATE',
			'LINEPROPAL_UPDATE',
			'LINEORDER_SUPPLIER_UPDATE',
			'LINECONTRACT_UPDATE',
			'USER_UPDATE_SESSION',
			'DON_UPDATE',
			'LINEFICHINTER_UPDATE'
		))){
			$action = str_replace( '_UPDATE', '_MODIFY', $action);
		}

        if ( in_array($action, array(
			'LINEBILL_INSERT', 'LINEBILL_MODIFY',
			'LINEPROPAL_INSERT', 'LINEPROPAL_MODIFY',
			'LINEORDER_INSERT', 'LINEORDER_MODIFY'
			))) {
			$forceUpdateDiscount = false;

			$line = $currentObject;

			if(getDolGlobalInt('DISCOUNTRULES_USE_MARKUP_MARGIN_RATE') && $action == 'LINEPROPAL_INSERT' && !getDolGlobalString('MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND')){

					//Define value for conf DISCOUNTRULES_MARKUP_MARGIN_RATE & DISCOUNTRULES_MINIMUM_RATE
					$options = array(
						0 => 'MarkRate',
						1 => 'MarginRate'
					);

					$valueConfMarkupMarginRate = $options[getDolGlobalInt('DISCOUNTRULES_MARKUP_MARGIN_RATE')];
					$minimumRate = getDolGlobalInt('DISCOUNTRULES_MINIMUM_RATE');

					if (!empty($valueConfMarkupMarginRate)){
						$costPrice = 0;
						if(empty($line->fk_product)){
							$costPrice = GETPOST('buying_price', 'int');
							if (empty($costPrice)){
								return 0;
							}

						} else {
							$myProduct = new Product($this->db);
							$myProduct->fetch($currentObject->fk_product);
							$costPrice = $myProduct->cost_price;
						}

						if ($costPrice) {
							//Subprice for MarkRate / MarginRate
							if ($valueConfMarkupMarginRate == 'MarkRate' ){
								$currentObject->subprice = price2num($costPrice / (1 - $minimumRate / 100));
							}elseif ($valueConfMarkupMarginRate == 'MarginRate'){
								$currentObject->subprice = price2num($costPrice * (1 + $minimumRate / 100));
							}
							//Total HT
							$currentObject->total_ht = $currentObject->subprice * $currentObject->qty;
							$res = $currentObject->update($user);
							if(!$res){
								setEventMessages($currentObject->error, $currentObject->errors, 'errors');
							}
						}
					}
			}

			// nothing to do if no product
			if(empty($line->fk_product)){
				return 0;
			}

			// Utilisation du mode forcé
			if(getDolGlobalInt('DISCOUNTRULES_FORCE_RULES_PRICES') && !$user->hasRight('discountrules', 'overrideForcedMod')){
				$forceUpdateDiscount = true;
			}

			// FOR TAKE POS there no Hook for udpate lines when changing qty
			// So use trigger to update line
			if(getDolGlobalInt('DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_TAKE_POS')
				&& static::getGlobalAction() == 'updateqty'
				&& strpos($_SERVER['PHP_SELF'], 'takepos/invoice.php') !== false
			){
				$forceUpdateDiscount = true;
			}


			if($forceUpdateDiscount){
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$line->id);

				if($line->element == 'facturedet'){
					/** @var FactureLigne $line  */
					$parentObject = new Facture($line->db);
					$resFetchParent = $parentObject->fetch($line->fk_facture);
				}
				elseif($line->element == 'commandedet'){
					/** @var OrderLine $line */
					$parentObject = new Commande($line->db);
					$resFetchParent = $parentObject->fetch($line->fk_commande);
				}
				elseif($line->element == 'propaldet'){
					/** @var PropaleLigne $line */
					$parentObject = new Propal($line->db);
					$resFetchParent = $parentObject->fetch($line->fk_propal);
				}
				else{
					// UNKNOW ELEMENT OR NOT COMPATIBLE
					return 0;
				}

				if($resFetchParent < 0){
					$this->errors[] = 'Error fetching parent document for discount rules';
					return -1;
				}

				$dateTocheck = time();
				if (!getDolGlobalInt('DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE')) $dateTocheck = $parentObject->date;


				$product = new Product($line->db);
				$resFetchProd = $product->fetch($line->fk_product);
				if($resFetchProd<=0){
					$this->errors[] = 'Fail Fectching product';
					return -1;
				}

				// Search discount
				require_once __DIR__ . '/../../class/discountSearch.class.php';
				$discountSearch = new DiscountSearch($line->db);
				$discountSearch->date = $dateTocheck;

				$discountSearchResult = $discountSearch->search($line->qty, $line->fk_product, $parentObject->socid, $parentObject->fk_project);

				DiscountRule::clearProductCache();
				$oldsubprice = $line->subprice;
				$oldremise = $line->remise_percent;
				$oldVat = $line->tva_tx;
				$line->tva_tx = $product->tva_tx;

				// ne pas appliquer les prix à 0 (par contre, les remises de 100% sont possibles)
				if ($discountSearchResult->subprice > 0) {
					$line->subprice = $discountSearchResult->subprice;
				}

				$line->remise_percent = $discountSearchResult->reduction;

				if($oldsubprice != $line->subprice
					|| $oldremise != $line->remise_percent
					|| $oldVat != $line->tva_tx
				){

					// mise à jour de la ligne
					$resUp = DiscountRuleTools::updateLineBySelf($parentObject, $line, 1);
					if ($resUp < 0) {
						$this->errors[] = $langs->trans('DiscountUpdateLineError', $line->product_ref);
						return -1;
					}
				}
			}
		}

		return 0;
	}

	/**
	 * return the global action var
	 * @return mixed
	 */
	public static function getGlobalAction(){
		global $action;
		if(!isset($action)) { return false; }
		return $action;
	}
}
