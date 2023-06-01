<?php
/* Copyright (C) 2018 John BOTELLA
 * Copyright (C) 2023 Sylvain Legrand - InfraS - technique@infras.fr
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

require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once('/discountrules/class/discountSearch.class.php');
dol_include_once('/discountrules/class/discountruletools.class.php');

/**
 *  Class of triggers for discountrules module
 */
class InterfaceDiscountrulestrigger extends DolibarrTriggers
{
	protected $db;	// Database handler @var DoliDB
	public $name				= '';	// Name of the trigger @var mixed|string
	public $description			= '';	// Description of the trigger @var string
	public $version				= self::VERSION_DEVELOPMENT;	// Version of the trigger @var string
	public $picto				= 'technic';	// Image of the trigger @var string
	public $family				= '';	// Category of the trigger @var string
	public $errors				= array();	// Errors reported by the trigger @var array
	const VERSION_DEVELOPMENT	= 'development';	// @var string module is in development
	const VERSION_EXPERIMENTAL	= 'experimental';	// @var string module is experimental
	const VERSION_DOLIBARR		= 'dolibarr';	// @var string module is dolibarr ready

	/************************************************
	*	Constructor
	*
	*	@param DoliDB $db Database handler
	************************************************/
	public function __construct($db)
	{
		$this->db			= $db;
		$this->name			= preg_replace('/^Interface/i', '', get_class($this));
		$this->family		= 'crm';
		$this->description	= 'The triggers of this module allow to modify the description of the line during the application of the discount rules';
		$this->version		= 'development';			// 'development', 'experimental', 'dolibarr' or version
		$this->picto		= 'discountrules@discountrules';
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
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
        if (empty($conf->discountrules->enabled))										return 0;     // Module not active, we do nothing
		if (!in_array($object->element, ['propaldet', 'commandedet', 'facturedet']))	return 0;
		$insert_actions																	= array('LINEPROPAL_INSERT', 'LINEPROPAL_MODIFY', 'LINEORDER_INSERT', 'LINEORDER_MODIFY', 'LINEBILL_INSERT', 'LINEBILL_MODIFY');
		$update_actions																	= array('LINEPROPAL_UPDATE', 'LINEORDER_UPDATE', 'LINEBILL_UPDATE');
		$authorizedActions																= array_merge($insert_actions, $update_actions);
		if (!in_array($action, $authorizedActions))										return 0;
		$element																		= null;
		switch ($object->element) {
			case 'propaldet' :
				$element	= new Propal($this->db);
				$element->fetch($object->fk_propal);
			break;
			case 'commandedet' :
				$element	= new Commande($this->db);
				$element->fetch($object->fk_commande);
			break;
			case 'facturedet' :
				$element	= new Facture($this->db);
				$element->fetch($object->fk_facture);
			break;
		}
		// insert line
		if (in_array($action, $insert_actions)) {
			dol_syslog('Trigger "'.$this->name.'" for action '.$action.' launched by '. __FILE__ .' id = '.$object->rowid);
			if (!empty($object->fk_product))	$res	= $this->updateDesc($element, $object, $action);
			return $res;
		}
		return 0;
	}

	/************************************************
	* Change line description if needed
	*
	* @param	CommonObject	$element	The object to process (an invoice, a propale, etc...)
	* @param	CommonObject	$object		The line to process (an invoice line, a propale line, etc...)
	* @param	string			$action		Event action code
	* @return	int							< 0 on error, > 0 on success
	************************************************/
	private function updateDesc($element, $object, $action)
	{
		global $conf, $langs, $user;

		$dateTocheck	= empty($conf->global->DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE) ? $element->date : time();
		$product		= new Product($this->db);
		$resFetchProd	= $product->fetch($object->fk_product);
		if ($resFetchProd <= 0) {
			setEventMessage('RequestError');
			return -1;
		}
		$discountSearch			= new DiscountSearch($this->db);
		$discountSearch->date	= $dateTocheck;
		$discountSearchResult	= $discountSearch->search($object->qty, $object->fk_product, $element->socid, $element->fk_project);
		$newProductDesc			= discountruletools::generateDescForNewDocumentLineFromProduct($element, $product, $discountSearchResult->description);
		if ($line->desc != $newProductDesc) {
			if ($element->element === 'propal')		$result	= $element->updateline($object->id,				// $rowid
																				$object->subprice,			// $pu_ht
																				$object->qty,				// $qty
																				$object->remise_percent,	// $remise_percent
																				$object->tva_tx,			// $txtva
																				$object->localtax1_tx,		// $txlocaltax1
																				$object->localtax2_tx,		// $txlocaltax2
																				$newProductDesc,			// $desc
																				'HT',						// $price_base_type
																				$object->info_bits,			// $info_bits
																				$object->special_code,		// $special_code
																				$object->fk_parent_line,	// $fk_parent_line
																				0,							// $skip_update_total
																				$object->fk_fournprice,		// $fk_fournprice
																				$remise,					// $pa_ht
																				$object->label,				// $label
																				$object->product_type,		// $type
																				$object->date_start,		// $date_start
																				$object->date_end,			// $date_end
																				$object->array_options,		// $array_options
																				$object->fk_unit,			// $fk_unit
																				0,							// $pu_ht_devise
																				1							// $notrigger
																				);
			if ($element->element === 'commande')	$result	= $element->updateline($object->id,				// $rowid
																				$newProductDesc,			// $desc
																				$object->subprice,			// $pu_ht
																				$object->qty,				// $qty
																				$object->remise_percent,	// $remise_percent
																				$object->tva_tx,			// $txtva
																				$object->localtax1_tx,		// $txlocaltax1
																				$object->localtax2_tx,		// $txlocaltax2
																				'HT',						// $price_base_type
																				$object->info_bits,			// $info_bits
																				$object->date_start,		// $date_start
																				$object->date_end,			// $date_end
																				$object->product_type,		// $type
																				$object->fk_parent_line,	// $fk_parent_line
																				0,							// $skip_update_total
																				$object->fk_fournprice,		// $fk_fournprice
																				$remise,					// $pa_ht
																				$object->label,				// $label
																				$object->special_code,		// $special_code
																				$object->array_options,		// $array_options
																				$object->fk_unit,			// $fk_unit
																				0,							// $pu_ht_devise
																				1,							// $notrigger
																				$object->ref_ext				// $ref_ext
																				);
			if ($element->element === 'facture')		$result	= $element->updateline($object->id,			// $rowid
																				$newProductDesc,			// $desc
																				$object->subprice,			// $pu_ht
																				$object->qty,				// $qty
																				$object->remise_percent,	// $remise_percent
																				$object->date_start,		// $date_start
																				$object->date_end,			// $date_end
																				$object->tva_tx,			// $txtva
																				$object->localtax1_tx,		// $txlocaltax1
																				$object->localtax2_tx,		// $txlocaltax2
																				'HT',						// $price_base_type
																				$object->info_bits,			// $info_bits
																				$object->product_type,		// $type
																				$object->fk_parent_line,	// $fk_parent_line
																				0,							// $skip_update_total
																				$object->fk_fournprice,		// $fk_fournprice
																				$remise,					// $pa_ht
																				$object->label,				// $label
																				$object->special_code,		// $special_code
																				$object->array_options,		// $array_options
																				$object->situation_percent,	// $situation_percent
																				$object->fk_unit,			// $fk_unit
																				0,							// $pu_ht_devise
																				1,							// $notrigger
																				$object->ref_ext			// $ref_ext
																				);
			if ($result > 0) {
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {	// genere le document pdf
					// Define output language
					$outputlangs	= $langs;
					if (!empty($conf->global->MAIN_MULTILANGS)) {
						$outputlangs	= new Translate('', $conf);
						$newlang		= (GETPOST('lang_id', 'aZ09') ? GETPOST('lang_id', 'aZ09') : $element->thirdparty->default_lang);
						$outputlangs->setDefaultLang($newlang);
					}
					$ret			= $element->fetch($element->id); // Reload to get new records
					if ($ret > 0)	$element->fetch_thirdparty();
					$element->generateDocument($element->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}
		}
		return 1;
	}
}
