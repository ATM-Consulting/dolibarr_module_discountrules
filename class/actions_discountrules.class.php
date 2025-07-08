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
require_once __DIR__ . '/../backport/v19/core/class/commonhookactions.class.php';
class Actionsdiscountrules extends \discountrules\RetroCompatCommonHookActions
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
	 * @var array Hook results. Propagated to $this->results for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * @param array $parameters
	 * @param Facture $object
	 * @param string $action
	 * @param HookManager $hookmanager
	 */
	public function completeTakePosAddLine($parameters, &$object, &$action, $hookmanager)
	{
		global $line, $qty, $conf; // because $parameters['line'] isn't get by reference modification is useless so we use $line instead as global

		/**
		 * @var Product $product
		 */
		$product = $parameters['prod'];

		if ($action == "addline" && getDolGlobalInt('DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_TAKE_POS')) {

			require_once __DIR__ . '/discountSearch.class.php';

			$dateTocheck = time();
			if (!getDolGlobalInt('DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE')){
				$dateTocheck = $object->date;
			}

			// Search discount
			$discountSearch = new DiscountSearch($object->db);
			$discountSearch->date = $dateTocheck;
			$discountSearchResult = $discountSearch->search($qty, $product->id, $object->socid, $object->fk_project);
			DiscountRule::clearProductCache();

			// ne pas appliquer les prix à 0 (par contre, les remises de 100% sont possibles)
			if ($discountSearchResult->subprice > 0) {
				$line['price'] = $discountSearchResult->subprice;
				$line['price_ttc'] = (1 + floatval($line['tva_tx'])/100) * $discountSearchResult->subprice;
			}

			$line['remise_percent'] = $discountSearchResult->reduction;
		}

		return 0;
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

		if (getDolGlobalInt('DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_ALL_LINES')
				&& array_intersect(array('propalcard', 'ordercard', 'invoicecard'), $context)
		) {
			$confirm = GETPOST('confirm', 'alpha');
			dol_include_once('/discountrules/class/discountrule.class.php');
			include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

			if ($action == 'doUpdateDiscounts') {

				$TLinesCheckbox = GETPOST("line_checkbox", 'array');
				$priceReapply = GETPOST("price-reapply", 'int');
				$productDescriptionReapply = GETPOST("product-reapply", 'int');
				$productBuyPriceReapply = GETPOST("buy-price-reapply", 'int');

				if(empty($TLinesCheckbox)){
					setEventMessage('RequestError');
					return -1;
				}

				// Vérifier les droits avant d'agir
				if (!self::checkUserUpdateObjectRight($user, $object)) {
					setEventMessage('NotEnoughtRights');
					return -1;
				}

				// TODO Mis en commentaire le 23/08/2023 car normalement useless à virer en 2024 si tjs là
//				$discountrule = new DiscountRule($this->db);
//				$c = new Categorie($this->db);
//				$client = new Societe($this->db);
//				$client->fetch($object->socid);
//				$TCompanyCat = $c->containing($object->socid, Categorie::TYPE_CUSTOMER, 'id');
//				$TCompanyCat = DiscountRule::getAllConnectedCats($TCompanyCat);

				$updated = 0;
				$updaterror = 0;

				$dateTocheck = time();
				if (!getDolGlobalInt('DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE')) $dateTocheck = $object->date;

				foreach ($object->lines as $line) {
					/** @var PropaleLigne|OrderLine|FactureLigne $line */

					$lineToUpdate = false;

					if(!in_array($line->id, $TLinesCheckbox) || empty($line->fk_product)){
						continue;
					}

					$product = new Product($object->db);
					$resFetchProd = $product->fetch($line->fk_product);
					if($resFetchProd<=0){
						setEventMessage('RequestError');
						return -1;
					}

					// Re-apply buiy price
					if($productBuyPriceReapply){
						$newBuyPrice = discountRuleGetDefaultBuyPrice($product);
						if($newBuyPrice === false){
							setEventMessage('GetDefaultPriceError');
							return -1;
						}
						elseif(!empty($newBuyPrice) || getDolGlobalInt('DISCOUNTRULES_MASS_LINE_ALLOW_UPDATE_ON_ZERO')){
							// TODO : check also fk_supplier_price
							$line->pa_ht = $newBuyPrice;
							$lineToUpdate = true;
						}
					}


					// RE-Appliquer la description si besoin
					if($productDescriptionReapply) {
						$newProductDesc = discountruletools::generateDescForNewDocumentLineFromProduct($object, $product);
						if($line->desc != $newProductDesc){
							$line->desc = $newProductDesc;
							$lineToUpdate = true;
						}
					}

					// Met à jour le prix de vente suivant les conditions tarifaires
					if($priceReapply) {

						// Search discount
						require_once __DIR__ . '/discountSearch.class.php';
						$discountSearch = new DiscountSearch($object->db);
						$discountSearch->date = $dateTocheck;

						$discountSearchResult = $discountSearch->search($line->qty, $line->fk_product, $object->socid, $object->fk_project);

						DiscountRule::clearProductCache();
						$oldSubPrice = $line->subprice;
						$oldRemise = $line->remise_percent;
						$oldVat = $line->tva_tx;
						$line->tva_tx = $product->tva_tx;

						$line->subprice = $discountSearchResult->subprice;
						// Conf : ne pas appliquer les prix à 0 (par contre, les remises de 100% sont possibles)
						if (getDolGlobalInt('DISCOUNTRULES_MASS_LINE_ALLOW_UPDATE_ON_ZERO') == 0 && $line->subprice <= 0 && $oldSubPrice > 0) {
							$line->subprice = $oldSubPrice;
						}
						$line->remise_percent = $discountSearchResult->reduction;

						if($oldSubPrice != $line->subprice
								|| $oldRemise != $line->remise_percent
								|| $oldVat != $line->tva_tx
						){
							$lineToUpdate = true;
						}
					}

					// Mise à jour uniquement si besoin pour ne pas déclencher de triggers inutilement
					if($lineToUpdate) {
						// mise à jour de la ligne
						$resUp = DiscountRuleTools::updateLineBySelf($object, $line);
						if ($resUp < 0) {
							$updaterror++;
							setEventMessage($langs->trans('DiscountUpdateLineError', $line->product_ref), 'errors');
						} else {
							$updated++;
						}
					}
				}

				if ($updated > 0) {
					setEventMessage($langs->trans('DiscountForLinesUpdated', $updated, count($object->lines)));
				} else if (empty($updated) && empty($updaterror)) {
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
	public function formEditProductOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $conf;
		$langs->loadLangs(array('discountrules'));
		$context = explode(':', $parameters['context']);

		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context) && $action != "edit")
		{

			$dateTocheck = time();
			if (!getDolGlobalInt('DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE')) $dateTocheck = $object->date;

			?>
			<!-- handler event jquery on 'qty' udpating values for product  -->
			<script type="text/javascript">
				$(document).ready(function () {
					var idProd = "<?php print $parameters['line']->fk_product; ?>";
					var idLine = "<?php print $parameters['line']->id; ?>";

					let subpriceSelector = '#price_ht';
					let remiseSelector = '#remise_percent';
					let subpriceTTCSelector = '#price_ttc';

					if(idProd > 0 && DiscountRule.config.useForcedMod == 1) {
						$(subpriceSelector+', '+remiseSelector+', '+subpriceTTCSelector).prop('readonly', true).addClass('--discount-for-readonly');
					}

					// change Qty
					$("[name='qty']").change(function () {
						let FormmUpdateLine = !document.getElementById("addline");
						// si nous sommes dans le formulaire Modification
						if (FormmUpdateLine) {
							DiscountRule.fetchDiscountOnEditLine('<?php print $object->element; ?>', idLine, idProd,<?php print intval($object->socid); ?>,<?php print intval($object->fk_project); ?>,<?php print intval($object->country_id); ?>,<?php print $dateTocheck; ?>);
						}
					});

					$(document).on("click", ".suggest-discount", function () {
						var $inputPriceHt = $('#price_ht');
						var $inputRemisePercent = $('#remise_percent');

						$inputRemisePercent.val($(this).attr("data-discount")).addClassReload("discount-rule-change --info");
						if ($(this).attr("data-subprice") > 0) {
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
	public static function checkUserUpdateObjectRight($user, $object, $rightToTest = 'creer') : bool
	{
		$right = false;
        $el = $object->element;
        if(in_array($el, array('propal', 'commande', 'facture'))) {
            $right = $user->hasRight($el, $rightToTest);
        }
	    return $right;
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param array()         $parameters     Hook metadatas (context, etc...)
	 * @param CommonObject $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$context = explode(':', $parameters['context']);

		$langs->loadLangs(array('discountrules'));
		if (in_array('propalcard', $context) || in_array('ordercard', $context) || in_array('invoicecard', $context)) {
			/** @var CommonObject $object */

			// STATUS DRAFT ONLY AND NOT IN EDIT MODE
			if (!empty($object->statut) || $action == 'editline') {
				return 0;
			}

			// bouton permettant de rechercher et d'appliquer les règles de remises
			// applicables aux lignes existantes
			// TODO ajouter un droit type $user->rights->discountrules->[ex:propal]->updateDiscountsOnlines pour chaque elements gérés (propal commande facture)
            // Dans les options du module activer l'option Chercher la dernière réduction appliquée sur les Propositions commerciales.
			if (getDolGlobalInt('DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_ALL_LINES') && !empty($object->lines) && $object->statut == 0) {
				$updateDiscountBtnRight = self::checkUserUpdateObjectRight($user, $object);
				$btnActionUrl = '';
				//$btnActionUrl = $_REQUEST['PHP_SELF'] . '?id=' . $object->id . '&action=askUpdateDiscounts&token=' . $_SESSION['newtoken'];

				$params = array(
						'attr' => array(
								'data-document-url' => $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&token=' . newToken(),
								'data-target-id' => $object->id,
								'data-target-element' => $object->element,
								'title' => $langs->transnoentities("drreapplyDescription"),
								'class' => "classfortooltip",
						)
				);
				print dolGetButtonAction($langs->trans("UpdateDiscountsFromRules"), '<span class="suggest-discount"></span> ' . $langs->trans("UpdateDiscountsFromRules"), 'default', $btnActionUrl, 'discount-rules-reapply-all', $user->hasRight('discountrules', 'read') && $updateDiscountBtnRight, $params);
			}

			$dateTocheck = time();
			if (!getDolGlobalInt('DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE')) $dateTocheck = $object->date;

			// ADD DISCOUNT RULES SEARCH ON DOCUMENT ADD LINE FORM
			?>
			<!-- MODULE discountrules -->
			<script src="<?php echo dol_buildpath("/discountrules/js/popinReapply.js.php", 1) ?>"></script>
			<script type="text/javascript">
				$(document).ready(function () {
					// DISCOUNT RULES CHECK
					$("#idprod, #qty, #prod_entry_mode_free").change(function () {

						let subpriceSelector = '#price_ht';
						let remiseSelector = '#remise_percent';
						let subpriceTTCSelector = '#price_ttc';

						if(DiscountRule.config.useForcedMod == 1){
							if(idprod > 0){
								$(subpriceSelector + ', ' + remiseSelector + ', ' + subpriceTTCSelector).prop('readonly', true).addClass('--discount-for-readonly');
							}else{
								$(subpriceSelector + ', ' + remiseSelector + ', ' + subpriceTTCSelector).prop('readonly', false).removeClass('--discount-for-readonly');
							}
						}

						if ($('#idprod') == undefined || $('#qty') == undefined) {
							return 0;
						}

						let defaultCustomerReduction = '<?php print floatval($object->thirdparty->remise_percent); ?>';
						let fk_company = '<?php print intval($object->socid); ?>';
						let fk_project = '<?php print intval($object->fk_project); ?>';
						DiscountRule.discountUpdate($('#idprod').val(), fk_company, fk_project, '#qty', subpriceSelector, remiseSelector, defaultCustomerReduction, '<?php echo $dateTocheck; ?>');
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
		if (in_array($parameters['currentcontext'], array('productservicelist', 'servicelist', 'productlist')) && isModEnabled('category')) {
			$ret = '<option value="addtocategory">' . $langs->trans('massaction_add_to_category') . '</option>';
			$ret .= '<option value="removefromcategory">' . $langs->trans('massaction_remove_from_category') . '</option>';

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
		global $db, $action, $langs;

		$massaction = GETPOST('massaction');

		// PRODUCTS MASSS ACTION
		if (in_array($parameters['currentcontext'], array('productservicelist', 'servicelist', 'productlist'))) {
			$TProductsId = $parameters['toselect'];

			// Clean
			if (!empty($TProductsId)) {
				$TProductsId = array_map('intval', $TProductsId);
			} else {
				return 0;
			}

			// Mass action
			if ($massaction === 'addtocategory' || $massaction === 'removefromcategory') {
				$TSearch_categ = array();
				if (intval(DOL_VERSION) > 10) {
					// After Dolibarr V10 it's a category multiselect field
					$TSearch_categ = GETPOST("search_category_product_list", 'array');
				}

				// Get current categories
				require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

				$processed = 0;

				if (!empty($TSearch_categ)) {

					$TDiscountRulesMassActionProductCache = array();

					foreach ($TSearch_categ as $search_categ) {

						$search_categ = intval($search_categ);

						$c = new Categorie($db);

						// Process
						if ($c->fetch($search_categ) > 0) {


							foreach ($TProductsId as $id) {

								// fetch product using cache for speed
								if (empty($TDiscountRulesMassActionProductCache[$id])) {
									$product = new Product($db);
									if ($product->fetch($id) > 0) {
										$TDiscountRulesMassActionProductCache[$id] = $product;
									}
								} else {
									$product = $TDiscountRulesMassActionProductCache[$id];
								}

								$existing = $c->containing($product->id, Categorie::TYPE_PRODUCT, 'id');

								$catExist = false;

								// Diff
								if (is_array($existing)) {
									if (in_array($search_categ, $existing)) {
										$catExist = true;
									} else {
										$catExist = false;
									}
								}

								// Process
								if ($massaction === 'removefromcategory' && $catExist) {
									// REMOVE FROM CATEGORY
									$c->del_type($product, 'product');
									$processed++;
								} elseif ($massaction === 'addtocategory' && !$catExist) {
									// ADD IN CATEGORY
									$c->add_type($product, 'product');
									$processed++;
								}
							}
						} else {
							setEventMessage($langs->trans('CategoryNotSelectedOrUnknow') . ' : ' . $search_categ, 'errors');
						}
					}

					setEventMessage($langs->trans('NumberOfProcessed', $processed));
				}
			}

		}

		return 0;
	}

	/**
	 * Overloading the completeTabsHead function : replacing the parent's function with the one below
	 *
	 * @param array()         $parameters     Hook metadatas (context, etc...)
	 * @param CommonObject $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function completeTabsHead($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs, $db;
		if (!empty($parameters['object']) && $parameters['mode'] === 'add') {
			$pObject = $parameters['object'];
			if (in_array($pObject->element, array('product', 'societe'))) {
				if ($pObject->element == 'product') {
					$column = 'fk_product';
				} elseif ($pObject->element == 'societe') {
					$column = 'fk_company';
				}

				if (!empty($parameters['head'])) {
					foreach ($parameters['head'] as $h => $headV) if(!empty($headV)){
						if ($headV[2] == 'discountrules') {
							$nbRules = 0;
							$resql = $pObject->db->query('SELECT COUNT(*) as nbRules FROM ' . MAIN_DB_PREFIX . 'discountrule drule WHERE ' . $column . ' = ' . intval($pObject->id) . ';');

							if($column == 'fk_company') {
							    dol_include_once('/discountrules/class/discountSearch.class.php');
							    $sql = 'SELECT COUNT(*) as nbRules FROM '.MAIN_DB_PREFIX.'discountrule t WHERE 1=1';
							    $sql .= DiscountSearch::getCompanySQLFilters($pObject->id);
                            } else {
							    $sql = 'SELECT COUNT(*) as nbRules FROM '.MAIN_DB_PREFIX.'discountrule drule WHERE '.$column.' = '.intval($pObject->id).';';
                            }
							$resql= $pObject->db->query($sql);
							if($resql>0){
								$obj = $pObject->db->fetch_object($resql);
								$nbRules = $obj->nbRules;
							}

							if ($nbRules > 0) $parameters['head'][$h][1] = $langs->trans('TabTitleDiscountRule') . ' <span class="badge">' . ($nbRules) . '</span>';
						}
					}
				}
			}
		}

		return 0;
	}

	public function llxFooter($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $langs;

		//Recup le taux selectionné + Taux minimum
		$options = array(0 => 'MarkRate', 1 => 'MarginRate');
		$valueConfMarkupMarginRate = $options[getDolGlobalInt('DISCOUNTRULES_MARKUP_MARGIN_RATE')];
		$minimumRate = getDolGlobalFloat('DISCOUNTRULES_MINIMUM_RATE');

		?>
		<script type="text/javascript">
			$(document).ready(function () {
				// Get config value from PHP (either 'MarginRate' or 'MarkRate')
				let valueConf = '<?php echo addslashes($valueConfMarkupMarginRate); ?>';

				// Get warning image with translated message
				let imgWarning = '<?php echo img_warning($langs->trans("WarningDiscountrulesMinimumRate", ($langs->trans($valueConfMarkupMarginRate)), $minimumRate)); ?>';

				// Select all <tr> rows whose id starts with "row"
				let tr = $('tr[id^="row"]');

				// Initialize the array of target <td> cells (depending on valueConf)
				let tdArray = [];
				if (valueConf === 'MarginRate') {
					tdArray = $('td.linecolmargin2.margininfos');
				}
				if (valueConf === 'MarkRate') {
					tdArray = $('td.linecolmark1.margininfos');
				}

				// Loop over all margin cells to append the warning icon if the value is below the minimum
				$(tdArray).each(function (index, td) {
					let raw = $(td).text().trim();
					let value = parseFloat(raw.replace(',', '.').replace('%', ''));

					if (value < <?php echo $minimumRate ?>) {
						$(td).append(imgWarning);
					}
				});

				// Loop over each row in the table
				$(tr).each(function (indexTr, elementTr) {
					let tdChildren = $(elementTr).children('td');

					// Depending on the configuration, find the related margin or mark column in the same row
					let margeMarkTd;
					if (valueConf === 'MarginRate') {
						margeMarkTd = $(elementTr).find('td.linecolmargin2');
					} else if (valueConf === 'MarkRate') {
						margeMarkTd = $(elementTr).find('td.linecolmark1');
					}

					// Attach a change event handler to each <td> in the row
					$(tdChildren).each(function (indexTd, elementTd) {
						$(elementTd).on('change', function () {

							// Delay execution to let other scripts (e.g., quickcustomerprice) update the DOM first
							setTimeout(function () {
								let rawMargeMarkTd = $(margeMarkTd).text().trim();
								let value = parseFloat(rawMargeMarkTd.replace(',', '.').replace('%', ''));

								// If value is below threshold, show warning icon
								if (value < <?php echo $minimumRate ?>) {
									$(margeMarkTd).children('span').remove();
									$(margeMarkTd).append($(imgWarning));
								} else {
									// Otherwise, remove any existing warning
									$(margeMarkTd).children('span').remove();
								}
							}, 200); // Delay must be enough to ensure other DOM updates complete
						});
					});
				});
			});
		</script>

		<?php
	}
}
