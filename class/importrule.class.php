<?php

/**
 *		Class to set and validate values
 * 		from import Discount Rule
 */

include_once 'discountrule.class.php';
dol_include_once('/discountrules/htdocs/core/class/validate.class.php');
include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

class ImportRule{
	/**
	 * @var DoliDB
	 */
	public $db;

	public $filepath;
	/**
	 * @var Validate
	 */
	public $validate;
	/**
	 * @var array  type de tiers
	 */
	public $TTypent;

	/**
	 * @var int number of errors
	 */
	public $nbErrors;

	/**
	 * @var string csv separator char
	 */
	public $csvSeparator = ';';

	/**
	 * @var string csv category separator char
	 */
	public $csvCatSeparator = ',';

	/**
	 * @var int number imported lines
	 */
	public $nbImported;

	/**
	 * @param DoliDB $db
	 */
	public function __construct($db){
		global $langs;
		$this->db = $db;
		$this->validate = new Validate($db,$langs);
		$formcompany = new FormCompany($this->db);
		$this->TTypent = $formcompany->typent_array(0);

	}
	/**
	 * @param DoliDB $db
	 * @param string $filePath  Typically path to uploaded CSV file
	 * @param string $srcEncoding
	 * @param string $importKey
	 * @return array|null  Array with
	 */
	function idrGetDiscountFromCSV($filePath, $srcEncoding = 'latin1', $importKey='ecImportDiscountRule',$startLine,$endLine = 0 ) {

		/*
			Import / DiscountRules (création des règles de prix).
		*/

		global $conf, $user, $langs;

		$TLineValidated =array();
		$this->nbErrors = 0;

		if (!is_file($filePath)) {
			return array($this->newImportLogLine('error', 'CSVFileNotFound'));
		}

		$TImportLog = array();
		$csvFile = fopen($filePath, 'r');

		$this->db->begin();
		for ($i = 1; $csvValues = fgetcsv($csvFile, '64000', $this->csvSeparator, '"'); $i++) {
			$csvValues = array_map(
				function ($val) use ($srcEncoding) {
					if ($srcEncoding === 'UTF-8') return trim($val);
					return iconv($srcEncoding, 'UTF-8', trim($val));
				},
				$csvValues
			);
			$TcsvLine = $csvValues;

			if (empty(implode('', $csvValues))) continue;

			if ($i < $startLine) continue; // skip headers rows
			if ($endLine > 0 && $i > $endLine ) continue; // skip footers rows

			try {
				$objDiscount = $this->idrSetObjectFromCSVLine($i-1, $TcsvLine);
			} catch (ErrorException $e) {
				$TImportLog[] = $this->newImportLogLine('error', $e->getMessage());
				$this->nbErrors++;
				continue;
			}
			// si pas d'erreur sur cette ligne on l'ajoute dans le tableau object à mettre en base plus tard.
			if (count($TImportLog) == 0){
				$TLineValidated[] = $objDiscount;
			}
		}

		if ($this->nbErrors == 0){



			$date_import = dol_print_date(dol_now(), '%Y%m%d%H%M%S');
			// on injecte les données en bases
			foreach ($TLineValidated as $k => $line){
				try {
					//  create mouvement
					$successMessage = $this->idrRegisterDiscount($line, $k+1);
					$TImportLog[] = $this->newImportLogLine('info', $successMessage);
					$this->nbImported++;
				} catch (ErrorException $e) {
					$TImportLog[] = $this->newImportLogLine('error', $e->getMessage());
					$this->nbErrors++;
					$this->db->rollback();
					$TImportLog[] = $this->newImportLogLine('error', 'rollback db');
				}
			}
		}

		$this->db->commit();
		return $TImportLog;
	}

	/**
	 * @param $objDiscount
	 * @param $lineNumber
	 * @param $date_import
	 * @return void
	 */
   function idrRegisterDiscount($objDiscount, $lineNumber) {

	   global $db, $langs,$user;
	   $resfetch  = $objDiscount->createCommon($user);

	   if ($resfetch < 0) {
		   throw new ErrorException($langs->trans(
			   'CreateDiscountRuleError',
			   $lineNumber,
			   $objDiscount->label,
			   $objDiscount->error . '<br>' . $db->lasterror())
		   );
	   }
	   return $langs->trans('CSVDiscountRuleCreateSuccess',$lineNumber, $objDiscount->label);

   }
	/**
	 * @param int $lineNumber
	 * @param array $lineArray
	 * @return object  Object representing the parsed CSV line
	 * @throws ErrorException
	 */
	function idrSetObjectFromCSVLine($lineNumber, $lineArray) {
		global $db, $langs;

		$objDiscount = new DiscountRule($db);

		$label 				= trim($lineArray[0]);
		$ref_project 		= trim($lineArray[1]);
		$ref_product 		= trim($lineArray[2]);
		$ref_company 		= trim($lineArray[3]);
		$code_country 		= trim($lineArray[4]);
		$priorityRank 		= trim($lineArray[5]);
		$cTypeEnt 			= trim($lineArray[6]);
		$cat_products 		= trim($lineArray[7]);
		$cat_companies 		= trim($lineArray[8]);
		$reduction 			= $lineArray[9];
		$fromQty			= $lineArray[10];
		$productPrice  		= $lineArray[11];
		$productReducAmount	= $lineArray[12];
		$dateFrom 			= $lineArray[13];
		$dateTo 			= $lineArray[14];
		$activation			= $lineArray[15];

		// LABEL
		try {
			$this->setLabel($label, $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// PROJET
		try {
			$this->setProject($ref_project , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//PRODUCT and CAT_PRODUCT
		try {
			$this->setProduct($ref_product, $cat_products, $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//COMPANY and CAT_COMPANY
		try {
			$this->setCompany($ref_company, $cat_companies, $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// COUNTRY by code
		try {
			$this->setCountry($code_country , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// PRIORITY_RANK
		try {
			$this->setPriorityRank($priorityRank , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//C_TYPEENT
		try {
			$this->setCTypeEnt($cTypeEnt , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//***************************************************************************************************

		// PRODUCT_PRICE
		try {
			$this->setProductPrice($productPrice , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// REDUCTION
		try {
			$this->setReduction($reduction , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// PRODUCT REDUCTION AMOUNT
		try {
			$this->setProductReductionAmmount($productReducAmount , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// Validation rules  on this particulars $reduction,  $productPrice, $productReducAmount
		try {
			$this->validatePriceProcess($ref_product,$reduction,  $productPrice, $productReducAmount , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		//***************************************************************************************************

		// FROM QUANTITY
		try {
			$this->setFromQuantity($fromQty , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// DATE FROM
		try {
			$this->setDateFrom($dateFrom , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// DATE TO
		try {
			$this->setDateTo($dateTo , $langs, $lineNumber,$objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}

		// activation
		try {
			$this->setActivation($activation, $objDiscount);
		}catch( ErrorException $e){
			throw $e;
		}


		return $objDiscount;
	}

	/**
	 * @param string $type  'error', 'warning', 'info'
	 * @param string $msg   Message
	 * @return array
	 */
	function newImportLogLine($type, $msg) {
		return array('type' => $type, 'msg' => $msg);
	}

	/**
	 * @param $label
	 * @param $langs
	 * @param $lineNumber
	 * @return void
	 * @throws ErrorException
	 */
	function setLabel($label, $langs, $lineNumber, &$objDiscount){

		if (!$this->validate->isNotEmptyString($label)) {
			throw new ErrorException($langs->trans(
				'EmptyLabelError',
				$lineNumber+1));
		}else{

			$objDiscount->label = $label;
		}
	}

	/**
	 * @param $ref_product
	 * @param $catProducts
	 * @param $langs
	 * @param $lineNumber
	 * @return int
	 * @throws ErrorException
	 */
	function setProduct($ref_product, $catProducts, $langs, $lineNumber, &$objDiscount)
	{
		// fk_product  +  categories product error
		if ($this->validate->isNotEmptyString($ref_product) &&  $this->validate->isNotEmptyString($catProducts)){
			throw new ErrorException($langs->trans(
				'ProductRefAndCatProductError',
				$lineNumber+1,
				$ref_product));
		}

		// default value will be modified later if needed
		$objDiscount->all_category_product = 0;

		// la ref  n'est pas vide
		if ($this->validate->isNotEmptyString($ref_product)){
			//la ref  n'existe pas
			if  ( !$this->validate->isInDb($ref_product,"product","ref")) {
					throw new ErrorException($langs->trans('ProductRefError', $lineNumber + 1, $ref_product));
			}else{
				// load fk_product dans l'obj discount
				$p = new Product($this->db);
				// we are protected by the throwed exception top line
				$p->fetch(0,$ref_product);
				$objDiscount->fk_product = $p->id;
			}

		//categories produits
		}else{
			if ($this->validate->isNotEmptyString($catProducts)){
				// Test des catégories
				$TCactProducts = explode($this->csvCatSeparator,$catProducts);

				foreach ($TCactProducts as $catproduct){
					// in the were param we can't pass $cat->MAP_ID[$cat::TYPE_PRODUCT] instead of 0 ...
					if (!$this->isCatInType($catproduct,"label",0)){
						throw new ErrorException($langs->trans(
							'catProductRefError',
							$lineNumber+1,
							$catproduct));
					}
				}

				// all cat ok store them in object via Tcatprod ou un truc dans le genre
				foreach ($TCactProducts as $cactproduct){
					$c = new Categorie($this->db);
					// no need to test the result here.
					// we are protected by the top for loop throwed exception
					$c->fetch(0,$cactproduct);
					$objDiscount->TCategoryProduct[] = $c->id;
				}
			}else{
				// no fk_product and no cat product we update  all_categories_product to 1
				$objDiscount->all_category_product = 1;
			}

		}
	}

	/**
	 * règles de de validation des champs reduction, product_price, product_reduction_ammount  selon la presence de  fk_product
	 * @param $ref_product
	 * @param $catProducts
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function validatePriceProcess($ref_product, $reduction,  $price, $reduction_amount , $langs, $lineNumber,  &$objDiscount){
		// ref product is up
		 if ($this->validate->isNotEmptyString($ref_product)) {

		 	if ($this->validate->isInDb($ref_product,"product","ref") ){
				 //au moins un des trois pour un produit
				 if (!$this->validate->isNotEmptyString($reduction) && !$this->validate->isNotEmptyString($price) && !$this->validate->isNotEmptyString($reduction_amount) ){
					 throw new ErrorException($langs->trans('atLeastOneofThemToRuleTheProductError', $lineNumber + 1, $ref_product));
				 }
			 }
		 }else{ // no ref product

			 // prix present mais pas de product
			 if ( $this->validate->isNotEmptyString($price)  ){
				 throw new ErrorException($langs->trans('NoPriceIfNoProductInsertedError', $lineNumber + 1));
			 }
			 // pas de remise %
			 if ( !$this->validate->isNotEmptyString($reduction)  ){
				 throw new ErrorException($langs->trans('NoReductionError', $lineNumber + 1));
			 }
		 }
	}

	/**
	 * @param $ref_company
	 * @param $catCompanies
	 * @param $langs
	 * @param $lineNumber
	 * @return int
	 * @throws ErrorException
	 */
	function setCompany($ref_company, $catCompanies, $langs, $lineNumber, &$objDiscount)
	{
		// fk_company  +  categories company error
		if ($this->validate->isNotEmptyString($ref_company) &&  $this->validate->isNotEmptyString($catCompanies)){
			throw new ErrorException($langs->trans(
				'CompanyRefAndCatCompanytError', $lineNumber + 1, $ref_company));
		}

		// default value will be modified later if needed
		$objDiscount->all_category_company = 0;

		// la ref  n'est pas vide
		if ($this->validate->isNotEmptyString($ref_company)){
			//la ref  n'existe pas
			if  ( !$this->validate->isInDb($ref_company,"societe","nom")) {
				throw new ErrorException($langs->trans('CompanyRefError', $lineNumber + 1, $ref_company));
			}else{
				// load fk_product dans l'obj discount
				$s = new Societe($this->db);
				// we are protected by the throwed exception top line
				$s->fetch(0,$ref_company);
				$objDiscount->fk_company = $s->id;
			}

			//categories companies
		}else{
			if ($this->validate->isNotEmptyString($catCompanies)){
				// Test des catégories
				$TCactCompanies = explode($this->csvCatSeparator,$catCompanies);

				foreach ($TCactCompanies as $catcompany){
					if (!$this->isCatInType($catcompany,"label",2)){
						throw new ErrorException($langs->trans('catCompanyRefError', $lineNumber + 1, $catcompany ));
					}
				}

				// all cat ok store them in object via Tcatprod ou un truc dans le genre
				foreach ($TCactCompanies as $cactcompany){
					$c = new Categorie($this->db);
					// no need to test the result here.
					// we are protected by the top for-loop throwed exception
					$c->fetch(0,$cactcompany);
					$objDiscount->TCategoryCompany[] = $c->id;
				}
			}else{
				// no fk_product and no cat product we update  all_categories_product to 1
				$objDiscount->all_category_company = 1;
			}

		}
	}

	/**
	 * @param $ref
	 * @param $langs
	 * @param $lineNumber
	 * @return void
	 * @throws ErrorException
	 */
	function setProject($ref, $langs, $lineNumber, &$objDiscount){
		// project
		$objDiscount->fk_project = 0;

		if ($this->validate->isNotEmptyString($ref)  &&  !$this->validate->isInDb($ref,"projet","ref")){
			throw new ErrorException($langs->trans(
				'ProjectRefError', $lineNumber + 1, $ref));
		}else{
			$p = new Project($this->db);
			$p->fetch(0,"$ref");
			// update fk_project
			$objDiscount->fk_project = $p->id;
		}

	}

	/**
	 * @param $code
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setCountry($code, $langs, $lineNumber, &$objDiscount){
		// fk_country default
		$objDiscount->fk_country = 0;

		if ($this->validate->isNotEmptyString($code)  &&  !$this->validate->isInDb($code,"c_country","code")){
			throw new ErrorException($langs->trans('countryCodefError', $lineNumber + 1, $code));
		}else{
			$c = new Ccountry($this->db);
			$c->fetch(0,$code);
			// update fk_country
			$objDiscount->fk_country = $c->id;
		}
	}

	/**
	 * @param $priorityRank
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setPriorityRank($priorityRank , $langs, $lineNumber, $objDiscount)
	{
		// vide pour pas de prio
		if ($priorityRank === '0' || !$this->validate->isNotEmptyString($priorityRank)) {
			$objDiscount->priority_rank = 0;
			return 1;
		}

		if ($this->parseNumberFromCSV($priorityRank,"int") == null) {
			throw new ErrorException($langs->trans('priorityRankNumberError', $lineNumber + 1, $priorityRank));
		}

		if ( $priorityRank < 0 ) {
			throw new ErrorException($langs->trans('priorityRankNumberLevelNegativeValueError', $lineNumber + 1, $priorityRank));
		}

		if ( $priorityRank > 5 ) {
			throw new ErrorException($langs->trans('priorityRankNumberLevelPrioError', $lineNumber + 1, $priorityRank));
		}

		$objDiscount->priority_rank = $priorityRank;

	}

	/**
	 * @param $reduction
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setReduction($reduction , $langs, $lineNumber, $objDiscount){

		if (!$this->validate->isNotEmptyString($reduction)){
			$objDiscount->reduction = 0;
			return 1;
		}

		// reduction en %
		if ($this->parseNumberFromCSV($reduction,"double") == null){
				throw new ErrorException($langs->trans('reductionTypeNumericError', $lineNumber + 1, $reduction));
		}

		$objDiscount->reduction = $reduction;
	}

	/**
	 * @param $reduction
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setProductPrice($productPrice , $langs, $lineNumber, $objDiscount){

		if (!$this->validate->isNotEmptyString($productPrice)){
			$objDiscount->product_price = 0;
			return 1;
		}

		if ($this->parseNumberFromCSV($productPrice,"double") == null){
			throw new ErrorException($langs->trans('productPriceTypeNumericError', $lineNumber + 1, $productPrice));
		}

		$objDiscount->product_price = $productPrice;
	}

	/**
	 * @param $productReducAmount
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return int|void
	 * @throws ErrorException
	 */
	function setProductReductionAmmount($productReducAmount , $langs, $lineNumber, $objDiscount){
		if (!$this->validate->isNotEmptyString($productReducAmount)){
			$objDiscount->product_reduction_amount = 0;
			return 1;
		}

		if ($this->parseNumberFromCSV($productReducAmount,"double") == null ){
			throw new ErrorException($langs->trans('productReducAmountTypeNumericError', $lineNumber + 1, $productReducAmount));
		}

		$objDiscount->product_reduction_amount = $productReducAmount;
	}

	/**
	 * @param $fromQty
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return int|void
	 * @throws ErrorException
	 */
	function setFromQuantity($fromQty , $langs, $lineNumber, $objDiscount){
		if (!$this->validate->isNotEmptyString($fromQty)){
			$objDiscount->from_quantity = 0;
			return 1;
		}

		if ($this->parseNumberFromCSV($fromQty,"int") == null){
			throw new ErrorException($langs->trans('FromQtyTypeNumericError', $lineNumber + 1, $fromQty));
		}

		 $objDiscount->from_quantity = $fromQty;
	}

	/**
	 * @param $cTypeEnt
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setCTypeEnt($cTypeEnt , $langs, $lineNumber, $objDiscount){

		if ($this->validate->isNotEmptyString($cTypeEnt)){

			if (!in_array($cTypeEnt, $this->TTypent)){
				throw new ErrorException($langs->trans('cTypeEntError', $lineNumber + 1, $cTypeEnt));
			}

			$key = array_search($cTypeEnt,$this->TTypent);
			$objDiscount->fk_c_typent = $key;

		}else{
			$objDiscount->fk_c_typent = 0;
		}

	}

	/**
	 * @param $dateFrom
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setDateFrom($dateFrom , $langs, $lineNumber, $objDiscount){

		if (!$this->validate->isNotEmptyString($dateFrom)){
			$objDiscount->date_from = null;
			return 1;
		}
		if (!preg_match("^\\d{1,2}/\\d{2}/\\d{4}^", $dateFrom)){
			throw new ErrorException($langs->trans('dateFromFormatError', $lineNumber + 1, $dateFrom));
		}

		$date = DateTime::createFromFormat('d/m/Y', $dateFrom);
		$timestamp = $date->getTimestamp();
		$objDiscount->date_from = dol_print_date($timestamp, "%Y-%m-%d 00:00:00");
	}

	/**
	 * @param $dateTo
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setDateTo($dateTo , $langs, $lineNumber, $objDiscount){

		if (!$this->validate->isNotEmptyString($dateTo)){
			$objDiscount->date_to = null;
			return 1;
		}
		if (!preg_match("^\\d{1,2}/\\d{2}/\\d{4}^", $dateTo)){
			throw new ErrorException($langs->trans('dateToFormatError', $lineNumber + 1, $dateTo));
		}

		$date = DateTime::createFromFormat('d/m/Y', $dateTo);
		$timestamp = $date->getTimestamp();

		$objDiscount->date_to = dol_print_date($timestamp, "%Y-%m-%d %H:%M:%S");

	}

	/**
	 * @param $activation
	 * @param $langs
	 * @param $lineNumber
	 * @param $objDiscount
	 * @return void
	 */
	function setActivation($activation,$objDiscount){

		if (intval($activation)){
			$objDiscount->fk_status = 1;
			return 1;
		}

		$objDiscount->fk_status = 0;
	}

	/**
	 * @param $value
	 * @return float
	 */
	function parseNumberFromCSV($value, $type) {
		global $langs;
		$value = str_replace(',', '.', $value);
		if (!is_numeric($value)) {
			return null;
		}
		if ($type === 'double') return doubleval($value);
		if ($type === 'int') return intval($value);
	}

	function isCatInType( $val, $col, $type){

		$sql = 'SELECT ' . $col . ' FROM ' . $this->db->prefix() . "categorie " . " WHERE ";
		$sql .=  $col ." = '" . $this->db->escape($val) . "'";
		$sql .=  " AND type = ". $type ;
		$resql = $this->db->getRow($sql);
		return $resql;
	}
}
