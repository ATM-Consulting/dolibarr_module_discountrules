<?php
/* Copyright (C) 2025 ATM Consulting
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

require_once __DIR__ . '/discountrule.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once __DIR__ . '/../lib/discountrules.lib.php';

/**
 * A class to search in discounts
 *
 * Class DiscountSearch
 */
class DiscountSearch
{

	/**
	 * @var string[] $TDebugLog
	 */
	public $TDebugLog = array();

	/** Search input
	 * @var integer[] $TCompanyCat
	 */
	public $TCompanyCat = array();

	/** Search input
	 * @var integer[] $TProductCat
	 */
	public $TProductCat = array();

	/** Search input
	 * @var integer[] $TProjectCat
	 */
	public $TProjectCat = array();


	/** Searched input
	 * @var int $fk_country
	 */
	public $fk_country = 0;

	/** Searched input
	 * @var int $fk_c_typent
	 */
	public $fk_c_typent = 0;

	/** Searched input
	 * @var double $qty
	 */
	public $qty = 0;

	/** Searched input
	 * @var int $fk_product
	 */
	public $fk_product = 0;

	/** Searched input
	 * @var int $fk_company
	 */
	public $fk_company = 0;

	/** Searched input
	 * @var int $fk_project
	 */
	public $fk_project = 0;



	/**
	 * @var DoliDb	$db	Database handler (result of a new DoliDB)
	 */
	protected $db;

	/**
	 * @var Product $product
	 */
	public $product;

	/**
	 * @var Societe $societe
	 */
	public $societe;

	/**
	 * @var Project $project
	 */
	public $project;



	/**
	 * @var DiscountSearchResult $result
	 */
	public $result;

	/**
	 * @var DiscountRule|false $discountRule
	 */
	public $discountRule;

	/**
	 * @var object $documentDiscount
	 */
	public $documentDiscount;

	/**
	 * @var double $defaultCustomerReduction
	 */
	public $defaultCustomerReduction = 0;


	public $date;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->result = new DiscountSearchResult();
	}


	/**
	 * Search discount rules and applied document discounts.
	 *
	 * @param int    $qty          Quantity of product
	 * @param int    $fk_product   Product id
	 * @param int    $fk_company   Company id
	 * @param int    $fk_project   Project id
	 * @param array  $TProductCat  Product category ids
	 * @param array  $TCompanyCat  Company category ids
	 * @param int    $fk_c_typent  Company type id
	 * @param int    $fk_country   Country id
	 * @param int    $nocache      Disable cache flag
	 * @param string $date         Date used for search (timestamp or string)
	 * @param array  $TProjectCat  Project category ids
	 * @return DiscountSearchResult|int Result object or -1 on error
	 */
	public function search($qty = 0, $fk_product = 0, $fk_company = 0, $fk_project = 0, $TProductCat = array(), $TCompanyCat = array(), $fk_c_typent = 0, $fk_country = 0, $nocache = 0, $date = '', $TProjectCat = array())
	{
		$fk_product = intval($fk_product);
		$this->qty = $qty;
		$this->fk_product = $fk_product;

		if (empty($this->date)) $this->date = time();
		if (! empty($date)) $this->date = $date;

		if (!empty($fk_product)) {
			$res = $this->feedByProduct($fk_product, $nocache);
			if (!$res) { return -1; }
		} else {
			// TODO : voir si je laisse là ou si on part du principe que si != de vide alors ça écrase les valeurs courantes mais si feedByProduct à fait des modifs
			$this->TProductCat = $TProductCat;
		}

		if (!empty($fk_project)) {
			$res = $this->feedByProject($fk_project, $nocache);
			if (!$res) { return -1; }
		} else {
			// TODO : voir si je laisse là ou si on part du principe que si != de vide alors ça écrase les valeurs courantes mais si feedByProject à fait des modifs
			$this->TProjectCat = $TProjectCat;
		}

		if (!empty($fk_company)) {
			$res = $this->feedBySoc($fk_company, $nocache);
			if (!$res) { return -1; }
		} else {
			// TODO : voir si je laisse là ou si on part du principe que si != de vide alors ça écrase les valeurs courantes mais si feedBySoc à fait des modifs
			$this->TCompanyCat = $TCompanyCat;
			$this->fk_country = $fk_country;
			$this->fk_c_typent = $fk_c_typent;
		}

		$this->fk_project = $fk_project;

		return $this->launchSearch();
	}

	/**
	 * Launch search
	 * @return DiscountSearchResult
	 */
	private function launchSearch()
	{
		global $langs, $conf;

		$this->result = new DiscountSearchResult();
		$this->result->defaultCustomerReduction = $this->defaultCustomerReduction;

		$this->launchSearchRule(); // will set $this->discountRule
		$this->launchSearchDocumentsDiscount();  // will set $this->documentDiscount


		$this->result->standard_product_price = DiscountRule::getProductSellPrice($this->fk_product, $this->fk_company);


		$useDocumentReduction = false;
		if (!empty($this->documentDiscount)) {
			$documentLastNetPrice = DiscountRule::calcNetPrice($this->documentDiscount->subprice, $this->documentDiscount->remise_percent);
			if ($documentLastNetPrice>0) {
				$useDocumentReduction = true;
			}

			// Comparison of discounts resulting from the rules and those already made in documents
			if ($useDocumentReduction
				&& !empty($this->discountRule)
			) {
				if (getDolGlobalInt('DISCOUNTRULES_SEARCH_DOCUMENTS_PRIORITY_RANK') == $this->discountRule->priority_rank) {
					// Search product net price
					$discountNetPrice = $this->discountRule->getDiscountSellPrice($this->fk_product, $this->fk_company) - $this->discountRule->product_reduction_amount;
					if (!empty($discountNetPrice) && $discountNetPrice > 0 && $documentLastNetPrice > $discountNetPrice) {
						$useDocumentReduction = false;
					}
				} elseif (getDolGlobalInt('DISCOUNTRULES_SEARCH_DOCUMENTS_PRIORITY_RANK') < $this->discountRule->priority_rank) {
					$useDocumentReduction = false;
				}
			}

			if ($useDocumentReduction) {
				$this->discountRule = false;

				$this->result->result = true;
				$this->result->element = $this->documentDiscount->element;
				$this->result->id = $this->documentDiscount->rowid;
				$this->result->label = $this->documentDiscount->ref;
				$this->result->qty = $this->documentDiscount->qty;
				$this->result->subprice = doubleval($this->documentDiscount->subprice);
				$this->result->product_reduction_amount = 0;
				$this->result->reduction = $this->documentDiscount->remise_percent;
				$this->result->entity = $this->documentDiscount->entity;
				$this->result->fk_status = $this->documentDiscount->fk_status ?? '';
				$this->result->date_object = $this->documentDiscount->date_object;
				$this->result->date_object_human = dol_print_date($this->documentDiscount->date_object, '%d %b %Y');
			}
		}


		/**
		 * PREPARE RESULT
		 */

		if (!empty($this->discountRule)) {
			$this->result->result = true;
			$this->result->element = 'discountrule';
			$this->result->id = $this->discountRule->id;
			$this->result->label = $this->discountRule->label;

			$this->result->subprice = $this->discountRule->getDiscountSellPrice($this->fk_product, $this->fk_company) - $this->discountRule->product_reduction_amount;
			$this->result->product_price = $this->discountRule->product_price;
			$this->result->product_reduction_amount = $this->discountRule->product_reduction_amount;
			$this->result->reduction = $this->discountRule->reduction;
			$this->result->entity = $this->discountRule->entity;
			$this->result->from_quantity = $this->discountRule->from_quantity;
			$this->result->fk_c_typent = $this->discountRule->fk_c_typent;
			$this->result->fk_project = $this->discountRule->fk_project;
			$this->result->priority_rank = $this->discountRule->priority_rank;

			$this->result->typentlabel  = getTypeEntLabel($this->discountRule->fk_c_typent);
			if (!$this->result->typentlabel ) { $this->result->typentlabel = ''; }

			$this->result->fk_status = $this->discountRule->fk_status;
			$this->result->fk_product = $this->discountRule->fk_product;
			$this->result->date_creation = $this->discountRule->date_creation;
			$this->result->match_on = $this->discountRule->lastFetchByCritResult;
			if (!empty($this->discountRule->lastFetchByCritResult)) {
				// Here there are matching parameters for product categories or company categories
				// ADD humain readable informations from search result
				$this->result->match_on->product_info = '';
				if ($this->product && !empty($this->discountRule->fk_product) && $this->product->id == $this->discountRule->fk_product ) {
					$this->result->match_on->product_info = $this->product->ref . ' - '.$this->product->label;
				}

				$this->result->match_on->category_product = $langs->transnoentities('AllProductCategories');
				if (!empty($this->discountRule->lastFetchByCritResult->fk_category_product)) {
					$c = new Categorie($this->db);
					$c->fetch($this->discountRule->lastFetchByCritResult->fk_category_product);
					$this->result->match_on->category_product = $c->label;
				}

				$this->result->match_on->category_company = $langs->transnoentities('AllCustomersCategories');
				if (!empty($this->discountRule->lastFetchByCritResult->fk_category_company)) {
					$c = new Categorie($this->db);
					$c->fetch($this->discountRule->lastFetchByCritResult->fk_category_company);
					$this->result->match_on->category_company = $c->label;
				}

				$this->result->match_on->company = $langs->transnoentities('AllCustomers');
				if (!empty($this->discountRule->lastFetchByCritResult->fk_company)) {
					$s = new Societe($this->db);
					$s->fetch($this->discountRule->lastFetchByCritResult->fk_company);

					$this->result->match_on->company = $s->name ? $s->name : $s->nom;
					$this->result->match_on->company .= !empty($s->name_alias) ? ' (' . $s->name_alias . ')' : '';
				}

				if (!empty($this->discountRule->lastFetchByCritResult->fk_project)) {
					$p = new Project($this->db);
					$p->fetch($this->discountRule->lastFetchByCritResult->fk_project);
					$this->result->match_on->project = $p->ref . ' : '.$p->title;
				}
			}
		}

		// revoit au minimum des infos de prix produit et réduction client
		if (!$this->result->result) {
			$this->result->subprice = $this->result->product_price = $this->result->standard_product_price;
			$this->result->reduction = $this->result->defaultCustomerReduction;
		}

		return $this->result;
	}

	/**
	 * Launch search rule
	 * @return DiscountRule|false
	 */
	private function launchSearchRule()
	{
		if (empty($this->qty)) $this->qty = 1;

		if (empty($this->TProductCat)) {
			$this->TProductCat = array(0); // force searching in all cat
		} else {
			$this->TProductCat[] = 0; // search in all cat too
		}

		$this->debugLog($this->TProductCat); // pass get var activatedebug or set activatedebug to show log
		$this->debugLog($this->TCompanyCat); // pass get var activatedebug or set activatedebug to show log
		$this->debugLog($this->TProjectCat); // pass get var activatedebug or set activatedebug to show log

		$TAllProductCat = DiscountRule::getAllConnectedCats($this->TProductCat);
		$TAllCompanyCat = DiscountRule::getAllConnectedCats($this->TCompanyCat);
		$TAllProjectCat = DiscountRule::getAllConnectedCats($this->TProjectCat);

		$this->debugLog($TAllProductCat); // pass get var activatedebug or set activatedebug to show log
		$this->debugLog($TAllCompanyCat); // pass get var activatedebug or set activatedebug to show log
		$this->debugLog($TAllProjectCat); // pass get var activatedebug or set activatedebug to show log

		$discountRes = new DiscountRule($this->db);
		$res = $discountRes->fetchByCrit($this->qty, $this->fk_product, $TAllProductCat, $TAllCompanyCat, $this->fk_company,  $this->date, $this->fk_country, $this->fk_c_typent, $this->fk_project, $TAllProjectCat);
		$this->debugLog($discountRes->error);
		if ($res > 0) {
			$this->discountRule = $discountRes;
		} else {
			$this->discountRule = false;
			$this->result->log[] = $discountRes->error;
		}

		return $this->discountRule;
	}


	/**
	 * SEARCH ALREADY APPLIED DISCOUNT IN DOCUMENTS (need setup option activated)
	 * @return object
	 */
	private function launchSearchDocumentsDiscount()
	{
		global $conf;

		if (empty($this->qty)) $this->qty = 1;

		$this->documentDiscount = false;

		if ($this->fk_product) {
			$from_quantity 	= getDolGlobalInt('DISCOUNTRULES_SEARCH_IN_DOCUMENTS_QTY_EQUIV') ? $this->qty : 0;

			$fk_project = 0; // Search documents in all projects
			if (getDolGlobalInt('DISCOUNTRULES_SEARCH_IN_DOCUMENTS_PROJECT_EQUIV')) {
				if (!empty($this->fk_project)) {
					$fk_project = $this->fk_project; // Search documents not linked to project
				} else {
					$fk_project = -1; // Search documents not linked to project
				}
			}

			if (getDolGlobalInt('DISCOUNTRULES_SEARCH_IN_ORDERS')) {
				$commande = DiscountRule::searchDiscountInDocuments('commande', $this->fk_product, $this->fk_company, $from_quantity, $fk_project);
				$this->documentDiscount = $commande;
			}
			if (getDolGlobalInt('DISCOUNTRULES_SEARCH_IN_PROPALS')) {
				$propal = DiscountRule::searchDiscountInDocuments('propal', $this->fk_product, $this->fk_company, $from_quantity, $fk_project);
				if (!empty($propal)
					&& (empty($this->documentDiscount) || DiscountRule::calcNetPrice($this->documentDiscount->subprice, $this->documentDiscount->remise_percent) > DiscountRule::calcNetPrice($propal->subprice, $propal->remise_percent) )) {
					$this->documentDiscount = $propal;
				}
			}
			if (getDolGlobalInt('DISCOUNTRULES_SEARCH_IN_INVOICES')) {
				$facture = DiscountRule::searchDiscountInDocuments('facture', $this->fk_product, $this->fk_company, $from_quantity, $fk_project);
				if (!empty($facture)
					&& (empty($this->documentDiscount)|| DiscountRule::calcNetPrice($this->documentDiscount->subprice, $this->documentDiscount->remise_percent) > DiscountRule::calcNetPrice($facture->subprice, $facture->remise_percent) ) ) {
					$this->documentDiscount = $facture;
				}
			}
		}

		return $this->documentDiscount;
	}


	/**
	 * Add company info to search query
	 *
	 * @param int  $fk_company Company id
	 * @param bool $nocache    Disable cache flag
	 * @return boolean True if company found, false otherwise
	 */
	public function feedBySoc($fk_company, $nocache)
	{

		$this->fk_company = 0;

		if (!empty($fk_company)) {
			$this->societe = DiscountRule::getSocieteCache($fk_company, $nocache);
			if ($this->societe) {
				$c = new Categorie($this->db);
				$this->TCompanyCat = $c->containing($fk_company, Categorie::TYPE_CUSTOMER, 'id');

				if (empty($this->fk_country)) {
					$this->fk_country = $this->societe->country_id;
				}

				if (empty($this->fk_c_typent)) {
					$this->fk_c_typent = $this->societe->typent_id;
				}

				$this->defaultCustomerReduction = $this->societe->remise_percent;
				$this->fk_company = $this->societe->id;
				return true;
			} else {
				$this->societe = false;
				return false;
			}
		}

		return false;
	}

	/**
	 * Add product info to search query
	 *
	 * @param int $fk_product Product id
	 * @param int $nocache    Disable cache flag
	 * @return boolean True if product found, false otherwise
	 */
	public function feedByProduct($fk_product, $nocache = 0)
	{
		// GET product infos and categories
		$this->product = false;
		$this->fk_product = 0;

		if (!empty($fk_product)) {
			$this->product = DiscountRule::getProductCache($fk_product, $nocache);
			if ($this->product) {
				$this->fk_product = $this->product->id;

				// Get current categories
				$c = new Categorie($this->db);
				$this->TProductCat = $c->containing($this->product->id, Categorie::TYPE_PRODUCT, 'id');
				return true;
			} else {
				$this->product = false;
				return false;
			}
		}

		return false;
	}

	/**
	 * Add project info to search query
	 *
	 * @param int $fk_project Project id
	 * @param int $nocache    Disable cache flag
	 * @return boolean True if project found, false otherwise
	 */
	public function feedByProject($fk_project, $nocache = 0)
	{
		// GET product infos and categories
		$this->project = false;
		$this->fk_project = 0;

		if (!empty($fk_project)) {
			$this->project = DiscountRule::getProjectCache($fk_project, $nocache);
			if ($this->project) {
				$this->fk_project = $this->project->id;

				// Get current categories
				$c = new Categorie($this->db);
				$this->TProjectCat = $c->containing($this->project->id, Categorie::TYPE_PROJECT, 'id');
				return true;
			} else {
				$this->project = false;
				return false;
			}
		}

		return false;
	}


	/**
	 * Add a debug entry into internal log array.
	 *
	 * @param mixed $log Message or data to log
	 * @return void
	 */
	public function debugLog($log = null)
	{
		if (!empty($log)) $this->TDebugLog[] = $log;
	}

	/**
	 * Build SQL filter for a given company.
	 *
	 * @param int $id Company id
	 * @return string SQL filter fragment
	 */
	public static function getCompanySQLFilters($id)
	{
		global $db;

		$sql = '';
		$sql .= ' AND ( t.fk_company = '.intval($id).' ';
		$sql .= ' OR  ((t.fk_c_typent = (SELECT fk_typent FROM '.$db->prefix().'societe WHERE rowid = '.intval($id).') OR t.fk_c_typent = 0)'; //0 => Tous
		$sql .= ' AND  (t.fk_country = (SELECT fk_pays FROM '.$db->prefix().'societe WHERE rowid = '.intval($id).') OR t.fk_country = 0)';
		$sql .= ' AND  (t.fk_project IN (SELECT rowid FROM '.$db->prefix().'projet WHERE fk_soc = '.intval($id).') OR t.fk_project = 0 )  ';
		$sql .= ' AND  (t.rowid IN (SELECT dcc.fk_discountrule
                                FROM '.$db->prefix().'discountrule_category_company dcc
                                LEFT JOIN '.$db->prefix().'categorie_societe cs ON (dcc.fk_category_company = cs.fk_categorie)
                                WHERE cs.fk_soc =  '.intval($id).') OR t.all_category_company = 1))
                AND t.fk_company = 0) '; //Si pas de tiers associé alors vérifie sur les autres params
		return $sql;
	}
}


/**
 * A class to manage results
 * only for IDE auto complete
 *
 * USED to return result compatible ajax json usage
 *
 * Class DiscountRuleSearch
 */
class DiscountSearchResult
{
	public $result = false;
	public $log = array();
	public $tpMsg;


	public $defaultCustomerReduction = 0;

	/**
	 * @var string $element discountrule|commande|propal|facture
	 */
	public $element;
	public $id;
	public $label;
	public $qty;

	/** @var double $standard_product_price  Prix unitaire de vente standard du produit pour la société (incluant les niveaux de prix et hors prix spécifiques produit appliqués par discountrules) */
	public $standard_product_price;

	/** @var double $product_price  Prix unitaire produit appliqué par discountrules sans aucune réduction */
	public $product_price;
	/** @var double $product_reduction_amount  Montant de la réduction à appliquer au prix unitaire avant réduction en pourcentage */
	public $product_reduction_amount = 0;

	/** @var double $subprice  Prix unitaire produit hors réduction en pourcentage (le prix produit) */
	public $subprice;
	/** @var int $reduction  Réduction en pourcentage */
	public $reduction;

	/** @var int $entity */
	public $entity;

	/** @var int $fk_status */
	public $fk_status;
	public $date_object;
	public $date_object_human;
	public $from_quantity;
	public $fk_c_typent;
	public $fk_project;
	public $priority_rank ;

	public $typentlabel;
	public $fk_product;
	public $date_creation;

	/**
	 * @var object $match_on
	 */
	public $match_on;

	/**
	 * get final subprice price after reductions
	 * and return it
	 *
	 * @return double
	 */
	public function calcFinalSubprice()
	{
		return $this->subprice - $this->subprice*$this->reduction/100;
	}
}
