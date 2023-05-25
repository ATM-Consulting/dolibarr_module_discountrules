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
class InterfacediscountrulesTriggers extends DolibarrTriggers
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
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
        if (!empty($conf->discountrules->enabled))										return 0;     // Module not active, we do nothing
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
			$res	= $this->updateDesc($element, $object, $action);
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

		$dateTocheck			= empty($conf->global->DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE) ? $element->date : time();
		$product				= new Product($this->db);
		$resFetchProd			= $product->fetch($object->fk_product);
		if ($resFetchProd <= 0) {
			setEventMessage('RequestError');
			return -1;
		}
		$discountSearch			= new DiscountSearch($this->db);
		$discountSearch->date	= $dateTocheck;
		$discountSearchResult	= $discountSearch->search($object->qty, $object->fk_product, $element->socid, $element->fk_project);
		$newProductDesc			= discountruletools::generateDescForNewDocumentLineFromProduct($element, $product, $discountSearchResult->description);
		if ($object->desc != $newProductDesc){
			$object->desc	= $newProductDesc;
		}
		return 1;
	}
}
