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
 * \file    class/actions_discountrules.class.php
 * \ingroup discountrules
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class Actionsdiscountrules
 */
class Actionsdiscountrules
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
     */
    public $error = '';
    /**
     * @var array Errors
     */
    public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
	    $this->db = $db;
	}


	/**
	 * @param array $parameters
	 * @param CommonObject $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$context = explode(':', $parameters['context']);
		$langs->loadLangs(array('discountrules'));


		// TODO : Fonctionnalité non complète à terminer et a mettre dans une methode
		// TODO : 13/01/2021 -> note pour plus tard : utiliser la class DiscountSearch($db);
		if (!empty($conf->global->DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_ALL_LINES)
				&& array_intersect(array('propalcard', 'ordercard', 'invoicecard'), $context)
		) {
			$confirm = GETPOST('confirm', 'alpha');
			dol_include_once('/discountrules/class/discountrule.class.php');
			include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			if ($action === 'askUpdateDiscounts') {

				// Vérifier les droits avant d'agir
				if(!self::checkUserUpdateObjectRight($user, $object)){
					setEventMessage('NotEnoughtRights');
					return -1;
				}


				global $delayedhtmlcontent;

				$form = new Form($this->db);
				$formconfirm = $form->formconfirm(
						$_REQUEST['PHP_SELF'] . '?id=' . $object->id . '&token=' . $_SESSION['newtoken'],
						$langs->trans('confirmUpdateDiscountsTitle'),
						$langs->trans('confirmUpdateDiscounts'),
						'doUpdateDiscounts',
						array(), // inputs supplémentaires
						'no', // choix présélectionné
						2 // ajax ou non
				);
				$delayedhtmlcontent .= $formconfirm;
			} elseif ($action === 'doUpdateDiscounts' && $confirm === 'yes') {

				// Vérifier les droits avant d'agir
				if(!self::checkUserUpdateObjectRight($user, $object)){
					setEventMessage('NotEnoughtRights');
					return -1;
				}

				$discountrule = new DiscountRule($this->db);
				$c = new Categorie($this->db);
				$client = new Societe($this->db);
				$client->fetch($object->socid);
				$TCompanyCat = $c->containing($object->socid, Categorie::TYPE_CUSTOMER, 'id');
				$TCompanyCat = DiscountRule::getAllConnectedCats($TCompanyCat);
				$updated = 0;
				$updaterror = 0;
				foreach ($object->lines as $line) {
					/** @var PropaleLigne|OrderLine|FactureLigne $line */

					$TProductCat = $c->containing($line->fk_product, Categorie::TYPE_PRODUCT, 'id');
					$TProductCat = DiscountRule::getAllConnectedCats($TProductCat);

					// TODO : cette recherche de réduction est incomplète voir interface.php
					// TODO : utiliser la class DiscountSearch($db);

					// fetchByCrit = cherche la meilleure remise qui corresponde aux contraintes spécifiées
					$res = $discountrule->fetchByCrit(
							$line->qty,
							$line->fk_product,
							$TProductCat,
							$TCompanyCat,
							$object->socid,
							time(),
							$client->country_id,
							$client->typent_id,
							$object->fk_project
					);


					if ($res > 0) {
						$oldsubprice = $line->subprice;
						$oldremise = $line->remise_percent;

						// TODO : Appliquer aussi les tarifs comme pour interface.php sinon celà va créer des incohérances voir des abérations
						$line->subprice = $discountrule->getProductSellPrice($line->fk_product, $object->socid) - $discountrule->product_reduction_amount;
						// ne pas appliquer les prix à 0 (par contre, les remises de 100% sont possibles)
						if ($line->subprice <= 0 && $oldsubprice > 0) {
							$line->subprice = $oldsubprice;
						}
						$line->remise_percent = $discountrule->reduction;
						// cette méthode appelle $object->updateline avec les bons paramètres
						// selon chaque type d’objet (proposition, commande, facture)

						// TODO : avant de mettre a jour, vérifier que c'est nécessaire car ça va peut-être déclencher des trigger inutilement
						$resUp = DiscountRuleTools::updateLineBySelf($object, $line);
						if($resUp<0){
							$updaterror ++;
							setEventMessage($langs->trans('DiscountUpdateLineError', $line->product_ref), 'errors');
						}
						else{
							$updated ++;
						}
					} else {
						continue;
					}
				}

				if($updated>0){
					setEventMessage($langs->trans('DiscountForLinesUpdated', $updated, count($object->lines)));
				}
				else if(empty($updated) && empty($updaterror)){
					setEventMessage($langs->trans('NoDiscountToApply'));
				}
			}
		}
	}

	/**
	 * @param array $parameters
	 * @param CommonObject $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 */
	public function formEditProductOptions ($parameters, &$object, &$action, $hookmanager){
		global $langs;
		$langs->loadLangs(array('discountrules'));
		$context = explode(':', $parameters['context']);
		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context) && $action != "edit")
		{
			?>
			<!-- handler event jquery on 'qty' udpating values for product  -->
			<script type="text/javascript">
			$( document ).ready(function() {
				var idProd = "<?php print $parameters['line']->fk_product; ?>";
				var idLine =  "<?php print $parameters['line']->id; ?>";

				// change Qty
				$("[name='qty']").change(function() {
					let FormmUpdateLine = 	!document.getElementById("addline");
					// si nous sommes dans le formulaire Modification
					if (FormmUpdateLine) {
						DiscountRule.fetchDiscountOnEditLine('<?php print $object->element; ?>',idLine,idProd,<?php print intval($object->socid); ?>,<?php print intval($object->fk_project); ?>,<?php print intval($object->country_id); ?>);
					}
				});

				$(document).on("click", ".suggest-discount",function(){
					var $inputPriceHt = $('#price_ht');
					var $inputRemisePercent = $('#remise_percent');

					$inputRemisePercent.val($(this).attr("data-discount")).addClassReload("discount-rule-change --info");
					if($(this).attr("data-subprice") > 0){
						$inputPriceHt.val($(this).attr("data-subprice")).addClassReload("discount-rule-change --info");
					}
				});
			});
			</script>
			<?php

		}
	}

	/**
	 * @param User $user
	 * @param CommonObject $object
	 * @return bool
	 */
	public static function checkUserUpdateObjectRight($user, $object, $rightToTest = 'creer'){
		$right = false;
		if($object->element == 'propal'){
			$right = $user->rights->propal->{$rightToTest};
		}
		elseif($object->element == 'commande'){
			$right = $user->rights->commande->{$rightToTest};
		}
		elseif($object->element == 'facture'){
			$right = $user->rights->facture->{$rightToTest};
		}
		
		return $right;
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$context = explode(':', $parameters['context']);

		$langs->loadLangs(array('discountrules'));
		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context) ) 
		{
			/** @var CommonObject $object */

			// STATUS DRAFT ONLY AND NOT IN EDIT MODE
		    if(!empty($object->statut) || $action=='editline'){
		        return 0;
		    }

		    // bouton permettant de rechercher et d'appliquer les règles de remises
			// applicables aux lignes existantes
			// TODO ajouter un droit type $user->rights->discountrules->[ex:propal]->updateDiscountsOnlines pour chaque elements gérés (propal commande facture)
			if($conf->global->DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_ALL_LINES)
			{
				$updateDiscountBtnRight = self::checkUserUpdateObjectRight($user, $object);
				$btnActionUrl = $_REQUEST['PHP_SELF'] . '?id=' . $object->id . '&action=askUpdateDiscounts&token=' . $_SESSION['newtoken'];
				print dolGetButtonAction($langs->trans("UpdateDiscountsFromRules"),'','default',$btnActionUrl,'',$user->rights->discountrules->read && $updateDiscountBtnRight);
			}

			// ADD DISCOUNT RULES SEARCH ON DOCUMENT ADD LINE FORM
			?>
		    <!-- MODULE discountrules -->
			<script type="text/javascript">
				$(document).ready(function(){
					// DISCOUNT RULES CHECK
					$( "#idprod, #qty" ).change(function() {
						if($('#idprod') == undefined || $('#qty') == undefined ){  return 0; }

						let defaultCustomerReduction = '<?php print floatval($object->thirdparty->remise_percent); ?>';
						let fk_company = '<?php print intval($object->socid); ?>';
						let fk_project = '<?php print intval($object->fk_project); ?>';
						DiscountRule.discountUpdate($('#idprod').val(), fk_company, fk_project, '#qty', '#price_ht', '#remise_percent', defaultCustomerReduction);
					});
				});
			</script>
			<!-- END MODULE discountrules -->
			<?php
		}
	}


	
	/*
	 * Overloading the printPDFline function
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$model, &$action, $hookmanager)
	{
	    global $langs, $conf;
	    // PRODUCTS MASSS ACTION
	    if (in_array($parameters['currentcontext'], array('productservicelist','servicelist','productlist')) && !empty($conf->category->enabled))
	    {
	        $ret='<option value="addtocategory">'.$langs->trans('massaction_add_to_category').'</option>';
	        $ret.='<option value="removefromcategory">'.$langs->trans('massaction_remove_from_category').'</option>';
	        
	        $this->resprints = $ret;
	    }

	    return 0;
	}

	
	/*
	 * Overloading the doMassActions function
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
	    global $db,$action,$langs;
	    
	    $massaction = GETPOST('massaction');
	    
	    // PRODUCTS MASSS ACTION
	    if (in_array($parameters['currentcontext'], array('productservicelist','servicelist','productlist')))
	    {
	        $TProductsId = $parameters['toselect'];
	        
	        // Clean
	        if(!empty($TProductsId)){
	            $TProductsId=array_map('intval', $TProductsId);
	        }else{ 
	            return 0; 
	        }
	        
	        // Mass action
	        if($massaction === 'addtocategory' || $massaction === 'removefromcategory'){
				$TSearch_categ = array();
				if(intval(DOL_VERSION) > 10){
					// After Dolibarr V10 it's a category multiselect field
					$TSearch_categ = GETPOST("search_category_product_list", 'array');
				}
	            else{
					$get_search_categ = GETPOST('search_categ', 'int');
					if(!empty($get_search_categ)){
						$TSearch_categ[] = $get_search_categ;
					}
				}

	            // Get current categories
	            require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

				$processed = 0;

	            if(!empty($TSearch_categ)){

	            	$TDiscountRulesMassActionProductCache = array();

	            	foreach ($TSearch_categ as $search_categ){

						$search_categ = intval($search_categ);

						$c = new Categorie($db);

						// Process
						if ($c->fetch($search_categ) > 0)
						{


							foreach($TProductsId as $id){

								// fetch product using cache for speed
								if(empty($TDiscountRulesMassActionProductCache[$id])){
									$product = new Product($db);
									if($product->fetch($id)>0)
									{
										$TDiscountRulesMassActionProductCache[$id] = $product;
									}
								}
								else{
									$product = $TDiscountRulesMassActionProductCache[$id];
								}

								$existing = $c->containing($product->id, Categorie::TYPE_PRODUCT, 'id');

								$catExist = false;

								// Diff
								if (is_array($existing))
								{
									if(in_array($search_categ, $existing)){
										$catExist = true;
									}
									else {
										$catExist = false;
									}
								}

								// Process
								if($massaction === 'removefromcategory' && $catExist){
									// REMOVE FROM CATEGORY
									$c->del_type($product, 'product');
									$processed++;
								}
								elseif($massaction === 'addtocategory' && !$catExist) {
									// ADD IN CATEGORY
									$c->add_type($product, 'product');
									$processed++;
								}
							}
						}
						else
						{
							setEventMessage($langs->trans('CategoryNotSelectedOrUnknow').' : '.$search_categ, 'errors');
						}
					}

					setEventMessage($langs->trans('NumberOfProcessed',$processed));
				}
	        }
	        
	    }

	    return 0;
	}

	/**
	 * Overloading the completeTabsHead function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;
		if(!empty($parameters['object']) && $parameters['mode'] === 'add')
		{
			$pObject = $parameters['object'];
			if ( in_array($pObject->element, array( 'product', 'societe')))
			{
				if ( $pObject->element == 'product' ){
					$column = 'fk_product';
				}
				elseif ( $pObject->element == 'societe' ){
					$column = 'fk_company';
				}

				if(!empty($parameters['head']))
				{
					foreach ($parameters['head'] as $h => $headV)
					{
						if($headV[2] == 'discountrules')
						{
							$nbRules = 0;
							$resql= $pObject->db->query('SELECT COUNT(*) as nbRules FROM '.MAIN_DB_PREFIX.'discountrule drule WHERE '.$column.' = '.intval($pObject->id).';');
							if($resql>0){
								$obj = $pObject->db->fetch_object($resql);
								$nbRules = $obj->nbRules;
							}

							if ($nbRules > 0)  $parameters['head'][$h][1] = $langs->trans('TabTitleDiscountRule').' <span class="badge">'.($nbRules).'</span>';

							$this->results = $parameters['head'];

							return 1;
						}
					}
				}
			}
		}

		return 0;
	}
}
