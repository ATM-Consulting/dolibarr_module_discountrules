<?php

/**
 * Collection of tools not directly related to DiscountRule
 *
 * Class DiscountRuleTools
 */
class DiscountRuleTools
{
	/**
	 * Helper method: calls $object->updateline() with the right parameters
	 * depending on the object's type (proposal, order or invoice).
	 * TODO: check compatibility with multicurrency
	 *
	 * @param CommonObject $object
	 * @param CommonObjectLine $line
	 * @param int $notrigger
	 * @return int > 0 = success; < 0 = failure
	 */
	static public function updateLineBySelf($object, $line, $notrigger=0)
	{
		if(empty($line->array_options)){
			$line->fetch_optionals();
		}

		$line->subprice_ht_devise = 0;

		if($object->element === 'propal'){
			/** @var Propal $object */
			return $object->updateline($line->id, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, $line->desc, 'HT', $line->info_bits, $line->special_code, $line->fk_parent_line, 0, $line->fk_fournprice, $line->pa_ht, $line->product_label, $line->product_type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, $line->subprice_ht_devise, $notrigger);
		}
		elseif($object->element === 'facture' ){
			/** @var Facture $object */
			return $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 'HT', $line->info_bits, $line->product_type, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->situation_percent, $line->fk_unit, $line->subprice_ht_devise, $notrigger);
		}
		elseif($object->element === 'commande' ){
			/** @var Commande $object */
			return $object->updateline($line->id, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, $line->localtax1_tx,$line->localtax2_tx, 'HT', $line->info_bits, $line->date_start, $line->date_end, $line->product_type, $line->fk_parent_line, $line->skip_update_total, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, $line->array_options, $line->fk_unit, $line->subprice_ht_devise, $notrigger);
		}

		return -1;

	}


	/**
	 * Return an object
	 *
	 * @param string $objecttype Type of object ('invoice', 'order', 'expedition_bon', 'myobject@mymodule', ...)
	 * @param $db
	 * @return int object of $objecttype
	 */
	public static function objectAutoLoad($objecttype, &$db)
	{
		global $conf;

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
	 * return
	 *
	 * @param CommonObject        $object
	 * @param Product $prod
	 * @return string
	 */
	public static function generateDescForNewDocumentLineFromProduct($object, $prod, $discountruledescription = '')
	{
		global $db, $conf, $langs;

		$proddesc = $prod->description;

		// Add custom code and origin country into description
		if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (!empty($prod->customcode) || !empty($prod->country_code)) && empty($conf->global->DISCOUNTRULES_ENABLE_RULE_DESCRIPTION))
		{
			$tmptxt = '(';
			// Define output language
			if (!empty($conf->global->MAIN_MULTILANGS) && !empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE)) {
				$outputlangs = $langs;
				$newlang = '';

				if (empty($newlang) && !empty($object->thirdparty))
					$newlang = $object->thirdparty->default_lang;
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
					$outputlangs->load('products');
				}
				if (!empty($prod->customcode))
					$tmptxt .= $outputlangs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
				if (!empty($prod->customcode) && !empty($prod->country_code))
					$tmptxt .= ' - ';
				if (!empty($prod->country_code))
					$tmptxt .= $outputlangs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $outputlangs, 0);
			} else {
				if (!empty($prod->customcode))
					$tmptxt .= $langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
				if (!empty($prod->customcode) && !empty($prod->country_code))
					$tmptxt .= ' - ';
				if (!empty($prod->country_code))
					$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code, 0, $db, $langs, 0);
			}
			$tmptxt .= ')';
			$proddesc = dol_concatdesc($prod->description, $tmptxt);
		}
		else if (!empty($conf->global->DISCOUNTRULES_ENABLE_RULE_DESCRIPTION) && !empty($discountruledescription)) {
			$proddesc = $discountruledescription;
		}

		return $proddesc;
	}
}
