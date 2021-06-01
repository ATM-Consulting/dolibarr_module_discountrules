<?php

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
	 * @param double   $qty
	 * @param int   $fk_product
	 * @param int   $fk_company
	 * @param int   $fk_project
	 * @param array $TProductCat
	 * @param array $TCompanyCat
	 * @param int   $fk_c_typent
	 * @param int   $fk_country
	 * @param bool  $nocache
	 * @return DiscountSearchResult|int
	 */
	public function search($qty = 0, $fk_product = 0, $fk_company = 0, $fk_project = 0, $TProductCat = array(), $TCompanyCat = array(), $fk_c_typent = 0, $fk_country = 0, $nocache = 0){
		$fk_product = intval($fk_product);
		$this->qty = $qty;
		$this->fk_product = $fk_product;

		if(!empty($fk_product)){
			$res = $this->feedByProduct($fk_product, $nocache);
			if(!$res){ return -1; }
		}
		else{
			// TODO : voir si je laisse là ou si on part du principe que si != de vide alors ça écrase les valeurs courantes mais si feedByProduct à fait des modifs
			$this->TProductCat = $TProductCat;
		}

		if(!empty($fk_company)){
			$res = $this->feedBySoc($fk_company, $nocache);
			if(!$res){ return -1; }
		}
		else{
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

		$useDocumentReduction = false;
		if (!empty($this->documentDiscount)) {
			$documentLastNetPrice = DiscountRule::calcNetPrice($this->documentDiscount->subprice, $this->documentDiscount->remise_percent);
			if($documentLastNetPrice>0) {
				$useDocumentReduction = true;
			}

			// Comparison of discounts resulting from the rules and those already made in documents
			if ($useDocumentReduction
				&& !empty($this->discountRule)
				&& $conf->global->DISCOUNTRULES_SEARCH_DOCUMENTS_PRIORITY_RANK <= $this->discountRule->priority_rank
			) {
				// Search product net price
				$productNetPrice = $this->discountRule->getNetPrice($this->fk_product, $this->fk_company);
				if(!empty($productNetPrice) && $documentLastNetPrice > $productNetPrice) {
					$useDocumentReduction = false;
				}
			}

			if($useDocumentReduction) {
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
				$this->result->fk_status = $this->documentDiscount->fk_status;
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
			$this->result->standard_product_price = DiscountRule::getProductSellPrice($this->fk_product, $this->fk_company);
			$this->result->product_reduction_amount = $this->discountRule->product_reduction_amount;
			$this->result->reduction = $this->discountRule->reduction;
			$this->result->entity = $this->discountRule->entity;
			$this->result->from_quantity = $this->discountRule->from_quantity;
			$this->result->fk_c_typent = $this->discountRule->fk_c_typent;
			$this->result->fk_project = $this->discountRule->fk_project;
			$this->result->priority_rank = $this->discountRule->priority_rank;

			$this->result->typentlabel  = getTypeEntLabel($this->discountRule->fk_c_typent);
			if(!$this->result->typentlabel ){ $this->result->typentlabel = ''; }

			$this->result->fk_status = $this->discountRule->fk_status;
			$this->result->fk_product = $this->discountRule->fk_product;
			$this->result->date_creation = $this->discountRule->date_creation;
			$this->result->match_on = $this->discountRule->lastFetchByCritResult;
			if (!empty($this->discountRule->lastFetchByCritResult)) {
				// Here there are matching parameters for product categories or company categories
				// ADD humain readable informations from search result
				$this->result->match_on->product_info = '';
				if($this->product && !empty($this->discountRule->fk_product) && $this->product->id == $this->discountRule->fk_product ){
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

		$TAllProductCat = DiscountRule::getAllConnectedCats($this->TProductCat);
		$TCompanyCat = DiscountRule::getAllConnectedCats($this->TCompanyCat);

		$this->debugLog($TAllProductCat); // pass get var activatedebug or set activatedebug to show log
		$this->debugLog($TCompanyCat); // pass get var activatedebug or set activatedebug to show log

		$discountRes = new DiscountRule($this->db);
		$res = $discountRes->fetchByCrit($this->qty, $this->fk_product, $TAllProductCat, $TCompanyCat, $this->fk_company,  time(), $this->fk_country, $this->fk_c_typent, $this->fk_project);
		$this->debugLog($discountRes->error);
		if ($res > 0) {
			$this->discountRule = $discountRes;
		}
		else{
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

		if($this->fk_product) {

			$from_quantity 	= empty($conf->global->DISCOUNTRULES_SEARCH_IN_DOCUMENTS_QTY_EQUIV) ? 0 : $this->qty;

			$fk_project = 0; // Search documents in all projects
			if (!empty($conf->global->DISCOUNTRULES_SEARCH_IN_DOCUMENTS_PROJECT_EQUIV)){
				if (!empty($this->fk_project)) {
					$fk_project = $this->fk_project; // Search documents not linked to project
				} else {
					$fk_project = -1; // Search documents not linked to project
				}
			}

			if (!empty($conf->global->DISCOUNTRULES_SEARCH_IN_ORDERS)) {
				$commande = DiscountRule::searchDiscountInDocuments('commande', $this->fk_product, $this->fk_company, $from_quantity, $fk_project);
				$this->documentDiscount = $commande;
			}
			if (!empty($conf->global->DISCOUNTRULES_SEARCH_IN_PROPALS)) {
				$propal = DiscountRule::searchDiscountInDocuments('propal', $this->fk_product, $this->fk_company, $from_quantity, $fk_project);
				if (!empty($propal)
					&& (empty($this->documentDiscount) || DiscountRule::calcNetPrice($this->documentDiscount->subprice, $this->documentDiscount->remise_percent) > DiscountRule::calcNetPrice($propal->subprice, $propal->remise_percent) ))
				{
					$this->documentDiscount = $propal;
				}
			}
			if (!empty($conf->global->DISCOUNTRULES_SEARCH_IN_INVOICES)) {
				$facture = DiscountRule::searchDiscountInDocuments('facture', $this->fk_product, $this->fk_company, $from_quantity, $fk_project);
				if (!empty($facture)
					&& (empty($this->documentDiscount)|| DiscountRule::calcNetPrice($this->documentDiscount->subprice, $this->documentDiscount->remise_percent) > DiscountRule::calcNetPrice($facture->subprice, $facture->remise_percent) ) )
				{
					$this->documentDiscount = $facture;
				}
			}
		}

		return $this->documentDiscount;
	}


	/**
	 * Add company info to search query
	 *
	 * @param int $fk_company
	 * @param bool $nocache
	 * @return boolean
	 */
	public function feedBySoc($fk_company, $nocache){

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
			}
			else{
				$this->societe = false;
				return false;
			}
		}

		return false;
	}

	/**
	 * Add product info to search query
	 *
	 * @param int $fk_product
	 * @param int $nocache
	 * @return boolean
	 */
	public function feedByProduct($fk_product, $nocache = 0){
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
			}else {
				$this->product = false;
				return false;
			}
		}

		return false;
	}


	/**
	 * @param string $log
	 */
	public function debugLog($log = null){
		if(!empty($log)) $this->TDebugLog[] = $log;
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
	public function calcFinalSubprice(){
		return $this->subprice - $this->subprice*$this->reduction/100;
	}
}
