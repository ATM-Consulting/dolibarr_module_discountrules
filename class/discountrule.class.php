<?php
/* Copyright (C) 2007-2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file        class/discountrule.class.php
 * \ingroup     discountrules
 * \brief       This file is a CRUD class file for discountrule (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once __DIR__ . '/../lib/discountrules.lib.php';
require_once __DIR__ . '/discountruletools.class.php';


/**
 * Class for discountrule
 */
class DiscountRule extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'discountrules_discountrule';
	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'discountrule';
	const table_element_category_product = 'discountrule_category_product';
	const table_element_category_company = 'discountrule_category_company';
	const table_element_category_project = 'discountrule_category_project';

	/**
	 * @var array  Does this field is linked to a thirdparty ?
	 */
	protected $isnolinkedbythird = 1;


	/**
	 * @var array  Does discountrule support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;
	/**
	 * @var string String with name of icon for discountrule
	 */
	public $picto = 'discountrules@discountrules';

	public $reserror;

    /**
     * Activate status
     */
    const STATUS_ACTIVE = 1;
    /**
     * Disabled status
     */
    const STATUS_DISABLED = 0;

    public $rowid;
    public $entity;
	/**
	 * @deprecated use fk_status
	 * @var $status;
	 */
    public $status;
    public $label;
    public $priority_rank;
    public $date_creation;
    public $tms;
    public $import_key;

    public $fk_country;
    public $fk_company;
	public $fk_c_typent;

    public $fk_product;
	/** @var Product $product */
    public $product;
    public $from_quantity;
    public $reduction;
    public $product_price;
    public $product_reduction_amount;
    public $fk_reduction_tax; // Actuelement non utilisée :  type de taxe utilisée pour $product_price && $product_reduction_amount :  0 = TTC, 1 = HT

    public $date_from;
    public $date_to;

	public $all_category_product;
	public $all_category_company;
	public $all_category_project;

    public $TCategoryProduct = array();
    public $TCategoryProject = array();
    public $TCategoryCompany = array();
    public $fk_project;
    public $fk_status;
    public $lastFetchByCritResult;

	/**
	 *  'type' is the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *
	 * Special discountrule
	 *  'onnullvalue'  if set, store this value when field is null instead of null
	 */


	/**
	 * @var array  Array with all fields and their property
	 */
	public $fields=array(
		'rowid' => array(
		      'type'=>'integer',
		    'label'=>'TechnicalID',
		    'visible'=> 0,
		    'enabled'=>1,
		    'position'=>1,
		    'notnull'=>1,
		    'index'=>1,
		    'comment'=>'Id',
		    'search'=>1,
		),

		'entity' => array(
			'type' => 'integer',
			'label' => 'Entity',
			'enabled' => 1,
			'visible' => 0,
			'default' => 1,
			'notnull' => 1,
			'index' => 1,
			'position' => 20
		),

	    'label' => array(
			'type' => 'varchar(255)',
			'label' => 'Label',
			'enabled' => 1,
			'visible' => 1,
			'position' => 1,
			'searchall' => 1,
			'notnull'=>1,
			'css' => 'minwidth200',
			'help' => 'DiscountRuleLabelHelp',
			'showoncombobox' => 1
	    ),
		'fk_product' => array(
			'type' => 'integer:Product:product/class/product.class.php:1',
			'label' => 'Product',
			'enabled' => 1,
			'visible' => 0,
			'default' => 0,
			'notnull' => 0,
			'nullvalue'=>0,
			'index' => 1,
			'position' => 2
		),
		'priority_rank' => array(
	        'type'=>'integer',
	        'label'=>'PriorityRuleRank',
			'help' => 'PriorityRuleRankHelp',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>5,
	        'notnull'=>0,
			'nullvalue'=>0,
	        'default_value' => 0, 'default' => 0, // for compatibility
			'arrayofkeyval'=>array(
				'0'=>'NoDiscountRulePriority',
				'1'=>'DiscountRulePriorityLevel01',
				'2'=>'DiscountRulePriorityLevel02',
				'3'=>'DiscountRulePriorityLevel03',
				'4'=>'DiscountRulePriorityLevel04',
				'5'=>'DiscountRulePriorityLevel05'
			),
	        'langfile' => 'discountrules@discountrules',
	        'search'=>1,
	    ),
		'fk_project' => array(
			'type' => 'integer:Project:projet/class/project.class.php:1',
			'label' => 'Project',
			'enabled' => 1,
			'visible' => 1,
			'default' => 0,
			'notnull' => 0,
			'nullvalue'=>0,
			'index' => 1,
			'position' => 100
		),
	    'from_quantity' => array(
	        'type'=>'integer',
	        'label'=>'FromQty',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>40,
	        'notnull'=>0,
			'nullvalue'=>0,
	        'default_value' => 1,
	        'search'=>1,
	    ),


		'product_price' =>array(
			'type'=>'double(24,8)',
			'label'=>'DiscountRulePrice',
			'visible'=>5,
			'enabled'=>1,
			'position'=>50,
			'notnull'=>0,
			'index'=>0,
			'comment'=>'',
			'search'=>1,
			'help' => 'DiscountRulePriceHelp',
		),

		// TODO : it's not a bug, it's a feature
		'product_reduction_amount' =>array(
			'type'=>'double(24,8)',
			'label'=>'DiscountRulePriceAmount',
			'visible'=>1,
			'enabled'=>1,
			'position'=>60,
			'notnull'=>0,
			'index'=>0,
			'comment'=>'',
			'search'=>1,
			'help' => 'DiscountRulePriceAmountHelp',
		),

	    'reduction' =>array(
	        'type'=>'double(24,8)',
	        'label'=>'DiscountPercent',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>70,
	        'notnull'=>1,
	        'index'=>0,
	        'comment'=>'',
	        'search'=>1,
			'help' => 'DiscountPercentHelp',
	    ),


		'fk_country' =>array(
			'type'=>'integer',
			'label'=>'Country',
			'visible'=>1,
			'enabled'=>1,
			'position'=>80,
			'onnullvalue'=>0,
			'index'=>1,
			'comment'=>'Country ID',
			'default'=>1,
			'nullvalue'=>0,
			'search'=>1,
		),

		'fk_company' =>array(
			'type' => 'integer:Societe:societe/class/societe.class.php',
			'label' => 'Customer',
			'visible' => 1,
			'enabled' => 1,
			'position' => 90,
			'nullvalue'=>0,
			'default'=>0,
			'index' => 1,
			//'help' => 'CustomerHelp'
		),

		'fk_c_typent' =>array(
			'type' => 'integer',
			'label' => 'ThirdPartyType',
			'visible' => 1,
			'enabled' => 1,
			'position' => 91,
			'nullvalue'=>0,
			'default'=>0,
			'index' => 1,
			//'help' => 'CustomerHelp'
		),

	    'date_from' =>array(
	        'type'=>'date',
	        'label'=>'DiscountRuleDateFrom',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>100,
	        'notnull'=>0,
	        'index'=>0,
	        'comment'=>'date from',
			'help' => 'DiscountRuleDateFromHelp'
	    ),

	    'date_to' =>array(
	        'type'=>'date',
	        'label'=>'DiscountRuleDateEnd',
	        'visible'=>1,
	        'enabled'=>1,
	        'position'=>101,
	        'notnull'=>0,
	        'index'=>0,
	        'comment'=>'',
			'help' => 'DiscountRuleDateEndHelp'
	    ),

		// also used to display categories
		// Note : category search is disabled directly on list
		'all_category_product' =>array(
			'type' => 'integer',
			'label' => 'ProductCategory',
			'enabled' => 0, // see _construct()
			'notnull' => 0,
			'nullvalue'=>0,
			'default' => -1,
			'visible' => 0,
			'position' => 115,
			'help' => 'ProductCategoryHelp'
		),

		// also used to display categories
		// Note : category search is disabled directly on list
		'all_category_company' =>array(
			'type' => 'integer',
			'label' => 'ClientCategory',
			'enabled' => 0, // see _construct()
			'notnull' => 0,
			'nullvalue'=>0,
			'default' => -1,
			'visible' => -1,
			'position' => 115,
			'help' => 'ClientCategoryHelp'
		),

		// also used to display categories
		// Note : category search is disabled directly on list
		'all_category_project' =>array(
			'type' => 'integer',
			'label' => 'ProjectCategory',
			'enabled' => 0, // see _construct()
			'notnull' => 0,
			'nullvalue'=>0,
			'default' => -1,
			'visible' => -1,
			'position' => 116,
			'help' => 'ProjectCategoryHelp'
		),

		'date_creation' => array(
			'type'=>'datetime',
			'label'=>'DateCreation',
			'visible'=> 0,
			'enabled'=>1,
			'position'=>500,
			'notnull'=>1,
		),

		'tms' => array(
			'type'=>'timestamp',
			'label'=>'DateModification',
			'visible'=> 0,
			'enabled'=> 1,
			'position'=> 500,
			'notnull'=> 1,
		),

		'import_key' => array(
			'type'=>'varchar(14)',
			'label'=>'ImportKey',
			'visible'=> 0,
			'enabled'=> 1,
			'position'=> 1000,
			'index'=> 1,
			'search'=> 1,
		),


		'fk_status' => array(
			'type' => 'integer',
			'label' => 'Status',
			'enabled' => 1,
			'visible' => 2,
			'notnull' => 1,
			'default' => 0,
			'index' => 1,
			'position' => 2000,
			'langfile' => 'discountrules@discountrules',
			'arrayofkeyval' =>  array(
				self::STATUS_DISABLED => 'Disable',
				self::STATUS_ACTIVE => 'Enable'
			)
	    ),
	);
	





	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf;

		$this->db = $db;
		$this->ismultientitymanaged = 1;
		$this->initFieldsParams();

	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
     * @param string $morewhere More SQL filters (' AND ...')
     * @param int $noextrafields 0=Default to load extrafields, 1=No extrafields
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $morewhere = '', $noextrafields = 0)
	{
	    $return = parent::fetchCommon($id,$ref, $morewhere, $noextrafields);
	    
	    if($return > 0){
	        $this->fetch_categoryCompany();
	        $this->fetch_categoryProduct();
	        $this->fetch_categoryProject();

			$this->initFieldsParams();
	    }
	    
	    return $return;
	}

	/**
	 * Init this class fields params values
	 */
	public function initFieldsParams(){
		global $conf;


		// visible param
		// 0=Not visible,
		// 1=Visible on list and create/update/view forms,
		// 2=Visible on list only,
		// 3=Visible on create/update/view form only (not list),
		// 4=Visible on list and update/view form only (not create).
		// Using a negative value means field is not shown by default on list but can be selected for viewing)

		if(isModEnabled('categorie')){
			// visibility
			$this->fields['all_category_product']['visible'] = 1; // set to 0 if fk_product is defined
			$this->fields['all_category_product']['enabled'] = 1; // set to 0 if fk_product is defined
			$this->fields['all_category_company']['visible'] = 1;
			$this->fields['all_category_company']['enabled'] = 1;
			$this->fields['all_category_project']['visible'] = 1;
			$this->fields['all_category_project']['enabled'] = 1;
		}

		if(!empty($this->fk_product)){
			// visibility
			$this->fields['product_price']['visible'] = 1;
			$this->fields['product_reduction_amount']['visible'] = 1;
			$this->fields['all_category_product']['visible'] = 0; // if fk_product is defined it can create a self incompatible rule
			$this->fields['all_category_product']['enabled'] = 0; // if fk_product is defined it can create a self incompatible rule

			// special
			$this->fields['reduction']['notnull'] = 0;

			$this->fields['product_price']['visible'] = 1;
			$this->fields['fk_product']['visible'] = 1;
		}



		if(!empty($this->fk_project)){
			// visibility
			$this->fields['all_category_project']['visible'] = 1;// if fk_project is defined it can create a self incompatible rule
			$this->fields['all_category_project']['enabled'] = 1;// if fk_project is defined it can create a self incompatible rule
		}
	}

	
	/**
	 *	Delete
	 *
	 *	@param	User	$user        	Object user that delete
	 *	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return	int						1 if ok, otherwise if error
	 */
	function delete($user, $notrigger=0)
	{
	    global $conf;
	    
	    $error=0;
	    
	    $this->db->begin();
	    
	    if (! $notrigger)
	    {
	        // Call trigger
	        $result=$this->call_trigger('DISCOUNTRULE_DELETE',$user);
	        if ($result < 0) { $error++; }
	        // End call triggers
	    }
	    
	    $this->TCategoryProject = array();
	    if ($this->update_categoryProject(1) < 0){
	        $error++;
	    }

		$this->TCategoryProduct = array();
	    if ($this->update_categoryProduct(1) < 0){
	        $error++;
	    }

	    $this->TCategoryCompany = array();
	    if ($this->update_categoryCompany(1) < 0){
	        $error++;
	    }
	    
	    if (! $error)
	    {
	        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".$this->id;
			$res = $this->db->query($sql);
	        if ($res)
	        {
	            $this->db->commit();
				$this->db->free($res);
	            return 1;
	        }
	        else
	        {
	            $this->error=$this->db->lasterror();
	            $this->db->rollback();
	            return -2;
	        }
	    }
	    else
	    {
	        $this->db->rollback();
	        return -1;
	    }
	}


    /**
     * @param User  $user   User object
     * @return int
     */
    public function setDisabled($user)
    {
        $this->fk_status = self::STATUS_DISABLED;
        $ret = $this->updateCommon($user);
        return $ret;
    }

    /**
     * @param User  $user   User object
     * @return int
     */
    public function setActive($user)
    {
        $this->fk_status = self::STATUS_ACTIVE;
        $ret = $this->updateCommon($user);
        return $ret;
    }

	/**
	 * Function to prepare the values to insert.
	 * Note $this->${field} are set by the page that make the createCommon or the updateCommon.
	 *
	 * @return array
	 */
	private function set_save_query()
	{
	    global $conf;
	    
	    $queryarray=array();
	    foreach ($this->fields as $field=>$info)	// Loop on definition of fields
	    {
	        // Depending on field type ('datetime', ...)
	        if($this->isDate($info))
	        {
	            if(empty($this->{$field}))
	            {
	                $queryarray[$field] = NULL;
	            }
	            else
	            {
	                $queryarray[$field] = $this->db->idate($this->{$field});
	                
	                if($field == 'date_to'){
	                    $queryarray[$field] = empty($this->{$field})?'':dol_print_date($this->{$field},"%Y-%m-%d 23:59:59");
	                }
	                
	                if($field == 'date_from'){
	                    $queryarray[$field] = empty($this->{$field})?'':dol_print_date($this->{$field},"%Y-%m-%d 00:00:00");
	                }
	            }
	        }
	        else if($this->isArray($info))
	        {
	            $queryarray[$field] = serialize($this->{$field});
	        }
	        else if($this->isInt($info))
	        {
	            if ($field == 'entity' && is_null($this->{$field})) $queryarray[$field]=$conf->entity;
	            else
	            {
	                $queryarray[$field] = (int) price2num($this->{$field});
	                if (empty($queryarray[$field])) $queryarray[$field]=0;		// May be rest to null later if property 'nullifempty' is on for this field.
	            }
	        }
	        else if($this->isFloat($info))
	        {
	            $queryarray[$field] = (double) price2num($this->{$field});
	            if (empty($queryarray[$field])) $queryarray[$field]=0;
	        }
	        else
	        {
	            $queryarray[$field] = $this->{$field};
	        }
	        
	        if ($info['type'] == 'timestamp' && empty($queryarray[$field])) unset($queryarray[$field]);
	        if (! empty($info['nullifempty']) && empty($queryarray[$field])) $queryarray[$field] = null;
	    }
	    
	    return $queryarray;
	}
	

	
	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function createCommon(User $user, $notrigger = false)
	{

		// null is forbiden
		$this->from_quantity = doubleval($this->from_quantity);

	    $res = parent::createCommon($user, $notrigger);
        $error= 0;
	    if($res)
	    {
	        if ($this->update_categoryProduct(1) < 0){
	            $error++;
	        }

			if ($this->update_categoryCompany(1) < 0){
				$error++;
			}

			if ($this->update_categoryProject(1) < 0){
				$error++;
			}
	    }
	    else{
	        $error++;
	    }

	    
	    if ($error) {
	        return -1 * $error;
	    } else {
	        return 1;
	    }
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function updateCommon(User $user, $notrigger = false)
	{
	    $error = 0;

	    $fieldvalues = $this->set_save_query();
	    unset($fieldvalues['rowid']);	// We don't update this field, it is the key to define which record to update.
	    unset($fieldvalues['date_creation']);
	    unset($fieldvalues['entity']);
	    
	    foreach ($fieldvalues as $k => $v) {
	        $tmp[] = $k.'='.$this->quote($v, $this->fields[$k]);
	    }
	    $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET '.implode( ',', $tmp ).' WHERE rowid='.$this->id ;

	    $this->db->begin();
	    if (! $error)
	    {
	        $res = $this->db->query($sql);
	        if ($res===false)
	        {
	            $error++;
	        }

			// Update extrafield
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

	        if ($this->update_categoryProduct(1) < 0)
	        {
	            $error++;
	        }

			if ($this->update_categoryProject(1) < 0)
			{
				$error++;
			}

	        if ($this->update_categoryCompany(1) < 0)
	        {
	            $error++;
	        }
			$this->db->free($res);
	    }

	    if (! $error && ! $notrigger) {
	        // Call triggers
	        $result=$this->call_trigger(strtoupper(get_class($this)).'_MODIFY',$user);
	        if ($result < 0) { $error++; } //Do also here what you must do to rollback action if trigger fail
	        // End call triggers
	    }

	    // Commit or rollback
	    if ($error) {
	        $this->db->rollback();
	        return -1 * $error;
	    } else {
	        $this->db->commit();
	        return $this->id;
	    }
	}
	
	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto			Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option				On what the link point to
     *  @param	int  	$notooltip			1=Disable tooltip
     *  @param  string  $morecss            Add more css on link
	 *	@return	string						String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $notooltip=0, $morecss='', $urlOnly = 0)
	{
		global $db, $conf, $langs;
        global $dolibarr_main_authentication, $dolibarr_main_demo;
        global $menumanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';

        $label = '<u>' . $langs->trans("discountrules") . '</u>';
        $label.= '<br>';
        $label.= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->label;

        $url = $url = dol_buildpath('/discountrules/discountrule_card.php',1).'?id='.$this->id;

        $linkclose='';
        if (empty($notooltip))
        {
            if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER'))
            {
                $label=$langs->trans("Showdiscountrule");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
        }
        else $linkclose = ($morecss?' class="'.$morecss.'"':'');

        if($urlOnly) return $url;
        
		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

        if ($withpicto)
        {
            $result.=($linkstart.img_object(($notooltip?'':$label), 'discountrules@discountrules', ($notooltip?'':'class="classfortooltip"')).$linkend);
            if ($withpicto != 2) $result.=' ';
		}
		$result.= $linkstart . $this->label . $linkend;
		return $result;
	}

	/**
	 *  Retourne le libelle du status d'un user (actif, inactif)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of fk_status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->fk_status,$mode);
	}

	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
	 *  @return string 			       	Label of status
	 */
	static function LibStatut($status,$mode=0)
	{
		global $langs;

        $statusLabel = $statusType = "";

        if ($status == 1){
            $statusLabel = $langs->trans('Enabled');
            $statusType = 'status4';
        }
        if ($status == 0){
            $statusLabel = $langs->trans('Disabled');
            $statusType = 'status5';
        }

        return dolGetStatus($statusLabel, '', '', $statusType, $mode);
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}

	
	
	/**
	 * @param unknown $cat
	 * @param number $deep
	 * @return array|NULL[]
	 */
	static function getCategoryChild($cat,$deep=0)
	{
	    global $db;
	    
	    dol_include_once('categories/class/categorie.class.php');
	    
	    $Tlist = array();
	    
	    $category = new Categorie($db);
	    $res = $category->fetch($cat);
	    
	    $Tfilles = $category->get_filles();
	    if(!empty($Tfilles) && $Tfilles>0)
	    {
	        foreach ($Tfilles as &$fille)
	        {
	            $Tlist[] = $fille->id;
	            
	            $Tchild = self::getCategoryChild($fille->id,$deep++);
	            if(!empty($Tchild)){
	                $Tlist = array_merge($Tlist,$Tchild);
	            }
	        }
	    }
	    
	    return $Tlist;
	    
	}
	
	/**
	 * @param int $cat
	 * @param number $isParent
	 * @param number $reverse
	 * @return array
	 */
	static function getCategoryParent($cat,$isParent = 0, $reverse = 0)
	{
	    global $db;
	    
	    dol_include_once('categories/class/categorie.class.php');
	    
	    $Tlist = array();
	    
	    if($isParent){
	        $Tlist[] = $cat;
	    }
	    
	    $category = new Categorie($db);
	    $res = $category->fetch($cat);
	   
	    if($res > 0 && !empty( $category->fk_parent ) )
	    {
	        $TParent = self::getCategoryParent($category->fk_parent, 1, 0);
	        if(!empty($TParent)){
                $Tlist = array_merge($Tlist,$TParent);
            }
	    }
	    
	    if($reverse){ 
	        $Tlist = array_reverse ($Tlist); 
	    }
	    
	    return $Tlist;
	    
	}

	static public function getAllConnectedCats($TCat){
		$TAllCat = array();
		foreach ($TCat as $cat) {
			$TAllCat[] = $cat;

			// SEARCH AT PARENT
			if(!empty($cat)){ // To avoid strange behavior
				$parents = DiscountRule::getCategoryParent($cat);
				if (!empty($parents)) {
					foreach ($parents as $parentCat) {
						$TAllCat[] = $parentCat;
					}
				}
			}
		}

		return array_unique($TAllCat);
	}

	/**
	 * @param string $col database col
	 * @param string $val value to search
	 * @param int $ignoreEmpty si true et que la valeur cherchée est "vide" alors la recherche s'éffecture sur 0 (tous)
	 * @return string
	 */
	static function prepareSearch($col, $val, $ignoreEmpty = 0)
	{
	    $sql = '';
	    
	    if($ignoreEmpty && empty($val) ) return '';
	    
	    $in = '0';
	    if(!empty($val)){
	        
	        if(is_array($val)){
	            $val = array_map('intval', $val);
	            $in.= ','.implode(',', $val);
	        }
	        else {
	            $in.= ','.intval($val);
	        }
	        
	    }
	    $sql.= ' AND '.$col.' IN ('.$in.') '; 
	    
	    return $sql;
	}
	
	/**
	 * @param string $col
	 * @param string $val
	 * @return string
	 */
	static function prepareOrderByCase($col, $val)
	{
	    $sql = $col.' DESC ';
	    
	    if(!empty($val) && is_array($val) && count($val)>1)
	    {
	        
	        $sql = ' CASE ';
	        
	        $i = 0;
	        foreach($val as $id)
	        {
	            $i++;
	            if (empty($id)){
	                continue;
	            }
	            
	            $sql.= ' WHEN '.$col.' = '.intval($id).' THEN '.$i.' ';

	        }
	        
	        $sql.= ' ELSE '.PHP_INT_SIZE.' END DESC';
	        
	    }
	    
	    return $sql;
	}

	/**
	 * @param $fk_product
	 * @param $fk_company
	 */
	function getDiscountSellPrice($fk_product, $fk_company){
		global $conf;

		if(!empty($this->product_price)){
			return round($this->product_price, getDolGlobalInt('MAIN_MAX_DECIMALS_UNIT'));
		}
		else{
			return self::getProductSellPrice($fk_product, $fk_company);
		}
	}


	/**
	 * @param $fk_product
	 * @param $fk_company
	 * @return bool|float|mixed
	 */
	public static function getProductSellPrice($fk_product, $fk_company){ // TODO add Cache for result
		global $mysoc, $conf;
		$product = self::getProductCache($fk_product);
		$societe = self::getSocieteCache($fk_company);

		if(!empty($product)) {

			// Dans le cas d'une règle liée à un produit, c'est le prix net qui sert de base de comparaison

			// récupération du prix client
			if ($societe) {
				$TSellPrice = $product->getSellPrice($mysoc, $societe);
				if (!empty($TSellPrice)) {
					$baseSubprice = $TSellPrice['pu_ht'];
				}
			}

			// si pas de prix client alors on force sur le prix de la fiche produit
			if (empty($baseSubprice)) {
				// prevent fatal
				// on ne veut pas passer null à la fonction priceToNum dans le cas ou pas de price
				$baseSubprice = $product->price ?? 0;
			}

			return round(price2num($baseSubprice), getDolGlobalInt('MAIN_MAX_DECIMALS_UNIT'));
		}

		return false;
	}


	/**
	 * @param int $from_quantity
	 * @param int $fk_product
	 * @param int|int[] $fk_category_product
	 * @param int|int[] $fk_category_company
	 * @param int $fk_company
	 * @param int $date
	 * @param int $fk_country
	 * @param int $fk_c_typent
	 * @param int $fk_project
	 * @param int|int[] $fk_category_project
	 * @return int <0 if KO, 0 if not found, > 0 if OK
	 * @see $this->lastFetchByCritResult: last fetched database object
	 */
	public function fetchByCrit($from_quantity = 1, $fk_product = 0, $fk_category_product = 0, $fk_category_company = 0, $fk_company = 0, $date = 0, $fk_country = 0, $fk_c_typent = 0, $fk_project = 0, $fk_category_project = 0)
	{
		global $mysoc;

		$sql = 'SELECT d.*, cc.fk_category_company, cp.fk_category_product';

		$product = $this->getProductCache($fk_product);

	    $baseSubprice = 0;
	    if(!empty($product)){

	    	// Dans le cas d'une règle liée à un produit, c'est le prix net qui sert de base de comparaison

			// récupération du prix client
			$baseSubprice = $this->getProductSellPrice($fk_product, $fk_company);

			$sql.= ', CASE ';
			$sql.= ' 	WHEN d.reduction > 0 AND d.product_price > 0';
			$sql.= ' 		THEN ( d.product_price - d.product_reduction_amount  ) - ( d.product_price - d.product_reduction_amount  ) * d.reduction / 100 ';
			$sql.= ' 	WHEN (d.reduction <= 0 OR  d.reduction IS NULL) AND d.product_price > 0';
			$sql.= ' 		THEN d.product_price - d.product_reduction_amount ';
			$sql.= ' 	WHEN d.reduction > 0 AND  (d.product_price <=0 OR d.product_price IS NULL)';
			$sql.= ' 		THEN ('.doubleval($baseSubprice).' - d.product_reduction_amount ) - ( '.doubleval($baseSubprice).' - d.product_reduction_amount  ) * d.reduction / 100 ';
//			$sql.= ' 	WHEN (d.product_price <=0 OR d.product_price IS NULL) AND (d.reduction <= 0 OR  d.reduction IS NULL) ';
//			$sql.= ' 		THEN '.doubleval($baseSubprice).' - d.product_reduction_amount )';
			$sql.= ' 	ELSE '.doubleval($baseSubprice).' - d.product_reduction_amount';
			$sql.= ' END as net_subprice ';
		}

		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' d ';

	    // Les conditions de jointure sont dans le WHERE car il y a une condition
	    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.self::table_element_category_company.' cc ON ( cc.fk_discountrule = d.rowid ) ';
	    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.self::table_element_category_product.' cp ON ( cp.fk_discountrule = d.rowid ) ';
	    $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.self::table_element_category_project.' cpj ON ( cpj.fk_discountrule = d.rowid ) ';

	    $sql.= ' WHERE from_quantity <= '.floatval($from_quantity).' AND `fk_status` = 1 ' ;

	    $sql.= self::prepareSearch('fk_country', $fk_country);
	    $sql.= self::prepareSearch('fk_c_typent', $fk_c_typent);
		$sql.= self::prepareSearch('fk_company', $fk_company);
		$sql.= self::prepareSearch('fk_project', $fk_project);

		$sql.= self::prepareSearch('fk_product', $fk_product);


	    $this->lastFetchByCritResult = false;

	    
	    if(!empty($date)){
	        $date = $this->db->idate($date);
	    }
	    else {
	        $date = $this->db->idate(time()); 
	    }

	    $sql.= ' AND ( date_from <= \''.$date.'\'  OR date_from IS NULL  OR YEAR(`date_from`) = 0 )'; // le YEAR(`date_from`) = 0 est une astuce MySQL pour chercher les dates vides le tout compatible avec les diférentes versions de MySQL
	    $sql.= ' AND ( date_to >= \''.$date.'\' OR date_to IS NULL OR YEAR(`date_to`) = 0 )'; // le YEAR(`date_to`) = 0 est une astuce MySQL pour chercher les dates vides le tout compatible avec les diférentes versions de MySQL

		// test for "FOR ALL CAT"
        $sql.= ' AND ( (d.all_category_product > 0 AND cp.fk_category_product IS NULL) OR (d.all_category_product = 0 AND cp.fk_category_product > 0 '.self::prepareSearch('cp.fk_category_product', $fk_category_product).' )) ';
		$sql.= ' AND ( (d.all_category_company > 0 AND cc.fk_category_company IS NULL) OR (d.all_category_company = 0 AND cc.fk_category_company > 0 '.self::prepareSearch('cc.fk_category_company', $fk_category_company).' )) ';
		$sql.= ' AND ( (d.all_category_project > 0 AND cpj.fk_category_project IS NULL) OR (d.all_category_project = 0 AND cpj.fk_category_project > 0 '.self::prepareSearch('cpj.fk_category_project', $fk_category_project).' )) ';

		$sql.= ' ORDER BY ';

		// Prise en compte des priorités de règles
		$sql.= ' priority_rank DESC, ' ;

	    // Ce qui nous intéresse c'est le meilleur prix pour le client
		if(!empty($fk_product)){
			$sql.= ' net_subprice ASC, ' ;
		}
	    $sql.= ' reduction DESC, from_quantity DESC, fk_company DESC, '.self::prepareOrderByCase('fk_category_company', $fk_category_company).', '.self::prepareOrderByCase('fk_category_product', $fk_category_product);

	    $sql.= ' LIMIT 1';

	    $res = $this->db->query($sql);
		$this->lastquery = $this->db->lastquery;
	    if($res)
	    {
	        if ($obj = $this->db->fetch_object($res))
	        {
	            $this->lastFetchByCritResult = $obj; // return search result object to know exactly matching parameters in JOIN part
	            return $this->fetch($obj->rowid);
	        }
			$this->db->free($res);
	    }
	    else
	    {
			$this->error = $this->db->error();
	    }

	    return 0;
	}

	/**
	 * Clear product cache
	 */
	public static function clearProductCache(){
		global $discountRuleProductCache;

		if(!empty($discountRuleProductCache) && is_array($discountRuleProductCache)){
			// Because it's an array of objects so unset doesn't really clear
			$discountRuleProductCache = array_map(function ($a){
				return NULL;
			}, $discountRuleProductCache);
		}

		unset($discountRuleProductCache);
	}

	/**
	 * @param $fk_product
	 * @param bool $forceFetch
	 * @return Product
	 */
	static function getProductCache($fk_product, $forceFetch = false){
		global $db, $discountRuleProductCache;

		if(empty($fk_product) || $fk_product < 0){
			return false;
		}

		if(!empty($discountRuleProductCache[$fk_product]) && !$forceFetch){
			return $discountRuleProductCache[$fk_product];
		}
		else{
			$product = new Product($db);
			$res = $product->fetch($fk_product);
			if($res>0){
				$discountRuleProductCache[$fk_product] = $product;
				return $discountRuleProductCache[$fk_product];
			}
		}

		return false;
	}


	/**
	 * @param $fk_project
	 * @param bool $forceFetch
	 * @return Product
	 */
	static function getProjectCache($fk_project, $forceFetch = false){
		global $db, $discountRuleProjectCache;

		if(empty($fk_project) || $fk_project < 0){
			return false;
		}

		if(!empty($discountRuleProjectCache[$fk_project]) && !$forceFetch){
			return $discountRuleProjectCache[$fk_project];
		}
		else{
			$project = new Project($db);
			$res = $project->fetch($fk_project);
			if($res>0){
				$discountRuleProjectCache[$fk_project] = $project;
				return $discountRuleProjectCache[$fk_project];
			}
		}

		return false;
	}

	/**
	 * @param $fk_soc
	 * @param bool $forceFetch
	 * @return Societe
	 */
	static function getSocieteCache($fk_soc, $forceFetch = false){
		global $db, $discountRuleSocieteCache;

		if(empty($fk_soc) || $fk_soc < 0){
			return false;
		}

		if(!empty($discountRuleSocieteCache[$fk_soc]) && !$forceFetch){
			return $discountRuleSocieteCache[$fk_soc];
		}
		else{
			$societe = new Societe($db);
			$res = $societe->fetch($fk_soc);
			if($res>0){
				$discountRuleSocieteCache[$fk_soc] = $societe;
				return $discountRuleSocieteCache[$fk_soc];
			}
		}

		return false;
	}

	/**
	 * Clear product cache
	 */
	public function clearSocieteCache(){
		global $discountRuleSocieteCache;
		$discountRuleSocieteCache = array();
	}

	/**
	 * 	Get children of line
	 *
	 * 	@param	int		$id		Id of parent line
	 * 	@return	array			Array with list of children lines id
	 */
	function fetch_categoryCompany()
	{
	    $this->TCategoryCompany=array();
	    
	    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.self::table_element_category_company;
	    $sql.= ' WHERE fk_discountrule = '.$this->id;
	    
	    $resql = $this->db->query($sql);
	    if ($resql)
	    {
	        while ($row = $this->db->fetch_object($resql) )
	        {
	            $this->TCategoryCompany[] = $row->fk_category_company;
	        }
			$this->db->free($resql);
	    }
	    
	    return $this->TCategoryCompany;
	}

	/**
	 * Calcule le prix net une fois toutes les reductions appliquées
	 * @param $subprice
	 * @param $reduction
	 * @param int $reductionAmount
	 * @return float|int
	 */
	static function calcNetPrice($subprice, $reduction, $reductionAmount = 0){
		$netPrice = $subprice - $reductionAmount;
		if(!empty($reduction) && $reduction > 0)
		{
			$netPrice-= $netPrice * $reduction / 100;
		}

		return $netPrice;
	}

	/**
	 * Retourne le prix net du produit fonction du client et une fois toutes les reductions appliquées
	 * @param $fk_product
	 * @param $fk_company
	 * @return float|int
	 */
	public function getNetPrice($fk_product, $fk_company){
		$baseSubprice = self::getProductSellPrice($fk_product, $fk_company);
		if(empty($baseSubprice)){
			return false;
		}
		return self::calcNetPrice($baseSubprice, $this->remise_percent, $this->product_reduction_amount);
	}
	
	/**
	 * @param boolean $replace  if false do not remove cat not in TCategoryCompany
	 * @return array
	 */
	function update_categoryCompany($replace = false)
	{
	    $TcatList = $this->TCategoryCompany; // store actual
	    $this->fetch_categoryCompany();

	    if(!is_array($this->TCategoryCompany) || !is_array($TcatList) || empty($this->id)){
	        return -1;
	    }

	    // Ok let's show what we got !
	    $TToAdd = array_diff ( $TcatList, $this->TCategoryCompany );
	    $TToDel = array_diff ( $this->TCategoryCompany, $TcatList );
	    
	    if(!empty($TToAdd)){
	        
	        // Prepare insert query
	        $TInsertSql = array();
	        foreach($TToAdd as $fk_category_company){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_company).')';
	        }
	        
	        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.self::table_element_category_company;
	        $sql.= ' (fk_discountrule,fk_category_company) VALUES '.implode(',', $TInsertSql );

	        $resql = $this->db->query($sql);
	        if (!$resql){
	            dol_print_error($this->db);
	            return -2;
	        }
	        else{
	            $this->TCategoryCompany = array_merge($TToDel,$TToAdd);
				$this->db->free($resql);
	        }
	    }
	    
	    if(!empty($TToDel) && $replace){
	        $TToDel = array_map('intval', $TToDel);
	        
	        foreach($TToDel as $fk_category_company){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_company).')';
	        }
	        
	        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.self::table_element_category_company.' WHERE fk_category_company IN ('.implode(',', $TToDel).')  AND fk_discountrule = '.intval($this->id).';';

	        $resql = $this->db->query($sql);
	        if (!$resql){
	            dol_print_error($this->db);
	            return -2;
	        }
	        else{
	            $this->TCategoryCompany = $TToAdd; // erase all to Del
				$this->db->free($resql);
	        }
	    }


        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET all_category_company = '.intval(empty($this->TCategoryCompany)).' WHERE rowid='.$this->id ;
        $resql = $this->db->query($sql);
        if (!$resql){
            dol_print_error($this->db);
            return -3;
        }
		$this->db->free($resql);
	    
	    return 1;
	}
	
	/**
	 * 	Get children of line
	 *
	 * 	@param	int		$id		Id of parent line
	 * 	@return	array			Array with list of children lines id
	 */
	function fetch_categoryProduct()
	{
	    $this->TCategoryProduct=array();
	    
	    $sql = 'SELECT * FROM '.MAIN_DB_PREFIX.self::table_element_category_product;
	    $sql.= ' WHERE fk_discountrule = '.$this->id;
	    
	    $resql = $this->db->query($sql);
	    if ($resql)
	    {
	        while ($row = $this->db->fetch_object($resql) )
	        {
	            $this->TCategoryProduct[] = $row->fk_category_product;
	        }
			$this->db->free($resql);
	    }
	    
	    return $this->TCategoryProduct;
	}
	
	

	/**
	 * @param boolean $replace  if false do not remove cat not in TCategoryProduct
	 * @return array
	 */
	function update_categoryProduct($replace = false)
	{
	    $TcatList = $this->TCategoryProduct; // store actual 
	    $this->fetch_categoryProduct();

	    if(!is_array($this->TCategoryProduct) || !is_array($TcatList) || empty($this->id)){
	        return -1;
	    }

	    // Ok let's show what we got !
	    $TToAdd = array_diff ( $TcatList, $this->TCategoryProduct );
	    $TToDel = array_diff ( $this->TCategoryProduct, $TcatList );
	    
	    if(!empty($TToAdd)){
	        
	        // Prepare insert query
	        $TInsertSql = array();
	        foreach($TToAdd as $fk_category_product){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_product).')';
	        }
	        
	        $sql = 'INSERT INTO '.MAIN_DB_PREFIX.self::table_element_category_product;
	        $sql.= ' (fk_discountrule,fk_category_product) VALUES '.implode(',', $TInsertSql );
	        
	        $resql = $this->db->query($sql);
	        if (!$resql){
	            return -2;
	        }
	        else{
	            $this->TCategoryProduct = array_merge($TToDel,$TToAdd); // erase all to Del
				$this->db->free($resql);
	        }
	    }
	    
	    if(!empty($TToDel) && $replace){
	        $TToDel = array_map('intval', $TToDel);

	        foreach($TToDel as $fk_category_product){
	            $TInsertSql[] = '('.intval($this->id).','.intval($fk_category_product).')';
	        }
	        
	        $sql = 'DELETE FROM '.MAIN_DB_PREFIX.self::table_element_category_product.' WHERE fk_category_product IN ('.implode(',', $TToDel).')  AND fk_discountrule = '.intval($this->id).';';
	        
	        $resql = $this->db->query($sql);
	        if (!$resql){
	            return -2;
	        }
	        else{
	            $this->TCategoryProduct = $TToAdd; // erase all to Del
				$this->db->free($resql);
	        }
	    }


        $sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET all_category_product = '.intval(empty($this->TCategoryProduct)).' WHERE rowid='.$this->id ;
        $resql = $this->db->query($sql);
        if (!$resql){
            dol_print_error($this->db);
            return -3;
        }
		$this->db->free($resql);
	    
	    return 1;
	}


	/**
	 * 	Get children of line
	 *
	 * 	@param	int		$id		Id of parent line
	 * 	@return	array			Array with list of children lines id
	 */
	function fetch_categoryProject()
	{
		$this->TCategoryProject=array();

		$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.self::table_element_category_project;
		$sql.= ' WHERE fk_discountrule = '.$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($row = $this->db->fetch_object($resql) )
			{
				$this->TCategoryProject[] = $row->fk_category_project;
			}
			$this->db->free($resql);
		}

		return $this->TCategoryProject;
	}



	/**
	 * @param boolean $replace  if false do not remove cat not in TCategoryProject
	 * @return array
	 */
	function update_categoryProject($replace = false)
	{
		$TcatList = $this->TCategoryProject; // store actual
		$this->fetch_categoryProject();

		if(!is_array($this->TCategoryProject) || !is_array($TcatList) || empty($this->id)){
			return -1;
		}

		// Ok let's show what we got !
		$TToAdd = array_diff ( $TcatList, $this->TCategoryProject );
		$TToDel = array_diff ( $this->TCategoryProject, $TcatList );

		if(!empty($TToAdd)){

			// Prepare insert query
			$TInsertSql = array();
			foreach($TToAdd as $fk_category_project){
				$TInsertSql[] = '('.intval($this->id).','.intval($fk_category_project).')';
			}

			$sql = 'INSERT INTO '.MAIN_DB_PREFIX.self::table_element_category_project;
			$sql.= ' (fk_discountrule,fk_category_project) VALUES '.implode(',', $TInsertSql );

			$resql = $this->db->query($sql);
			if (!$resql){
				return -2;
			}
			else{
				$this->TCategoryProject = array_merge($TToDel,$TToAdd); // erase all to Del
				$this->db->free($resql);
			}
		}

		if(!empty($TToDel) && $replace){
			$TToDel = array_map('intval', $TToDel);

			foreach($TToDel as $fk_category_project){
				$TInsertSql[] = '('.intval($this->id).','.intval($fk_category_project).')';
			}

			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.self::table_element_category_project.' WHERE fk_category_project IN ('.implode(',', $TToDel).')  AND fk_discountrule = '.intval($this->id).';';

			$resql = $this->db->query($sql);
			if (!$resql){
				return -2;
			}
			else{
				$this->TCategoryProject = $TToAdd; // erase all to Del
				$this->db->free($resql);
			}
		}


		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET all_category_project = '.intval(empty($this->TCategoryProject)).' WHERE rowid='.$this->id ;
		$resql = $this->db->query($sql);
		if (!$resql){
			dol_print_error($this->db);
			return -3;
		}
		$this->db->free($resql);

		return 1;
	}

	/**
	 * @param string $element
	 * @param int $fk_product
	 * @param int $fk_company
	 * @param int $from_quantity
	 * @param int $fk_project 0 search in all $fk_project values, -1 search in documents not linked to a project, > 0 search documents linked to project $fk_project
	 * @return false|Object
	 */
	public static function searchDiscountInDocuments($element, $fk_product, $fk_company, $from_quantity = 1, $fk_project = 0)
    {
        global $conf, $db;

        $table = $tableDet = $fkObjectCol = false;

        $refCol = 'ref';
		$fkProjectCol = 'fk_projet';
        $fk_product = intval($fk_product);
        $fk_company = intval($fk_company);
		$fk_project = intval($fk_project);
		$from_quantity = doubleval($from_quantity);

        $dateDocCol = '';

        if($element === 'facture'){
            $table          = 'facture';
            $tableDet       = 'facturedet';
            $fkObjectCol    = 'fk_facture';
			$dateDocCol		= 'datef';
        }
        elseif($element === 'commande'){
            $table          = 'commande';
            $tableDet       = 'commandedet';
            $fkObjectCol    = 'fk_commande';
			$dateDocCol		= 'date_commande';
        }
        elseif($element === 'propal'){
            $table          = 'propal';
            $tableDet       = 'propaldet';
            $fkObjectCol    = 'fk_propal';
			$dateDocCol		= 'datep';
        }

        if(empty($table) || empty($dateDocCol)){
            return false;
        }

        $sql = 'SELECT line.remise_percent, object.rowid, object.'.$refCol.' as ref, object.entity, line.qty, line.subprice ' ;

		$sql.= ', '.$dateDocCol.' as date_object ';

		$sql.= ', CASE ';
		$sql.= ' 	WHEN line.remise_percent > 0';
		$sql.= ' 		THEN line.subprice- line.subprice * line.remise_percent / 100 ';
		$sql.= ' 	ELSE line.subprice ';
		$sql.= ' END as net_subprice ';


        $sql.= ' FROM '.MAIN_DB_PREFIX.$tableDet.' line ';
        $sql.= ' JOIN '.MAIN_DB_PREFIX.$table.' object ON ( line.'.$fkObjectCol.' = object.rowid ) ';

        $sql.= ' WHERE object.fk_statut > 0 ';
        $sql.= ' AND object.fk_soc = '. $fk_company;
        $sql.= ' AND line.fk_product = '. $fk_product;
        $sql.= ' AND object.entity = '. $conf->entity;

        $sql.= ' AND line.subprice > 0 '; // parceque l'on offre peut-être pas à chaque fois les produits

		$sql.= ' AND line.qty > 0 '; // pour garder les options a part
        if (!empty($from_quantity)) {
            $sql.= ' AND line.qty <= '.$from_quantity;
        }

        if ($fk_project < 0) {
			// Search documents not linked to project
			$sql.= ' AND (ISNULL(object.'.$fkProjectCol.') OR  object.'.$fkProjectCol.' = 0 )';
		}
        elseif ($fk_project > 0) {
			// Search documents linked to project
			$sql.= ' AND object.'.$fkProjectCol.' = '.$fk_project;
		}
        else {
        	// Search documents in all projects
		}

        if(getDolGlobalInt('DISCOUNTRULES_SEARCH_DAYS')){
            $sql.= ' AND object.'.$dateDocCol.' >= CURDATE() - INTERVAL '.abs(getDolGlobalInt('DISCOUNTRULES_SEARCH_DAYS')).' DAY ';
        }

        $sql.= ' ORDER BY ';
		if(getDolGlobalString('DISCOUNTRULES_DOCUMENT_SEARCH_TYPE') == 'last_price'){
			$sql.= ' object.'.$dateDocCol.' DESC ';
		} else { // DISCOUNTRULES_DOCUMENT_SEARCH_TYPE == 'best_price'
			$sql.= ' net_subprice ASC ';
		}

        $sql.= ' LIMIT 1';

        $res = $db->query($sql);

        if($res)
        {
            if ($obj = $db->fetch_object($res))
            {
                $obj->date_object = $db->jdate($obj->date_object);
                $obj->element = $element;
                return $obj;
            }
        }
        else
        {
            $db->reserror = $db->error;
        }
        //print '<p>'.$sql.'</p>';
        return false;
    }

	/**
	 * Return HTML string to show a field into a page
	 *
	 * @param  string  $key            Key of attribute
	 * @param  string  $moreparam      To add more parameters on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showInputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
		return $this->showInputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
	}

	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param  array   		$val	       Array of properties for field to show
	 * @param  string  		$key           Key of attribute
	 * @param  string  		$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  		$moreparam     To add more parameters on html input tag
	 * @param  string  		$keysuffix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$morecss       Value for css to define style/length of field. May also be a numeric.
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form, $user;

		if (isModEnabled('categorie')) {
			include_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		}

		// Load langs
		if(!empty($this->fields[$key]['langfile'])){
			if (is_array($this->fields[$key]['langfile'])) $langs->loadLangs($this->fields[$key]['langfile']);
			else $langs->load($this->fields[$key]['langfile']);
		}

		if(empty($form)){ $form=new Form($this->db); }

		$required = '';
		if(!empty($this->fields[$key]['notnull']) && abs($this->fields[$key]['notnull']) > 0){
			$required = ' required ';
		}

		if ($key == 'fk_country'){
			$out = $form->select_country($value, $keyprefix.$key.$keysuffix);
		}
		elseif ($key == 'fk_product'){
			// pas de modification possible pour eviter les MEGA GROSSES BOULETTES utilisateur
			$out = $this->showOutputFieldQuick($key, $moreparam, $keysuffix, $keyprefix, $morecss);
		}
		elseif ($key == 'fk_c_typent'){
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
			$formcompany = new FormCompany($this->db);
			$sortparam = (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : getDolGlobalString('SOCIETE_SORT_ON_TYPEENT')); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
			$TTypent = $formcompany->typent_array(0);
			//$TTypent[0] = $langs->trans('AllTypeEnt');
			$out = Form::selectarray("fk_c_typent", $TTypent, $this->fk_c_typent, 1, 0, 0, '', 0, 0, 0, $sortparam);
			if ($user->admin) $out.=' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		elseif ($key == 'all_category_product'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->generateFormCategorie('product',$keyprefix.'TCategoryProduct'.$keysuffix, $this->TCategoryProduct, $morecss);
		}
		elseif ($key == 'all_category_company'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->generateFormCategorie('customer',$keyprefix.'TCategoryCompany'.$keysuffix, $this->TCategoryCompany, $morecss);
		}
		elseif ($key == 'all_category_project'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->generateFormCategorie('project',$keyprefix.'TCategoryProject'.$keysuffix, $this->TCategoryProject, $morecss);
		}
		elseif ($key == 'fk_status'){
			$options = array( self::STATUS_DISABLED => $langs->trans('Disable') ,self::STATUS_ACTIVE => $langs->trans('Enable') );
			$out = Form::selectarray($keyprefix.$key.$keysuffix, $options,$value);
		}
		elseif ($key == 'priority_rank'){
			$options = array();
			foreach ($this->fields['priority_rank']['arrayofkeyval'] as $arraykey => $arrayval) {
				$options[$arraykey] = $langs->trans($arrayval);
			}
			$out = Form::selectarray($keyprefix.$key.$keysuffix, $options,$value);
		}
		elseif (in_array($key, array('reduction', 'product_price', 'product_reduction_amount')))
		{
			$out = '<input '.$required.' class="flat" type="number" name="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" placeholder="xx.xx" min="0" step="any" >';
		}
		elseif ($key == 'from_quantity')
		{
			$out = '<input '.$required.' class="flat" type="number" name="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" placeholder="xx" min="0" step="any" >';
		}
		elseif ($this->fields[$key]['type'] == 'date')
		{
			if(is_int($value) && !empty($value)){$value = date('Y-m-d',$value);}
			$out = '<input '.$required.' class="flat" type="date" name="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" >';
		}
		else
		{
			$out = parent::showInputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss, $nonewbutton);
		}


		return $out;
	}

	/**
	 * Return HTML string to show a field into a page
	 *
	 * @param  string  $key            Key of attribute
	 * @param  string  $moreparam      To add more parameters on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputFieldQuick($key, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = ''){
		return $this->showOutputField($this->fields[$key], $key, $this->{$key}, $moreparam, $keysuffix, $keyprefix, $morecss);
	}

	/**
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param  array   $val		       Array of properties of field to show
	 * @param  string  $key            Key of attribute
	 * @param  string  $value          Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  $moreparam      To add more parametes on html input tag
	 * @param  string  $keysuffix      Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  $keyprefix      Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   $morecss        Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs, $form;

		$out = '';
		if ($key == 'fk_country'){
			if(!empty($value)){
				$tmparray=getCountry($value,'all');
				$out =  $tmparray['label'];
			}
			else{
				$out =  '<span class="discountrule-all-text" >'.$langs->trans('AllCountries').'</span>';
			}
		}
		elseif ($key == 'fk_company' && empty($value)){
			$out =  '<span class="discountrule-all-text" >'.$langs->trans('AllCustomers').'</span>';
		}
		elseif ($key == 'all_category_product'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->getCategorieBadgesList($this->TCategoryProduct, $langs->trans('AllProductCategories'));
		}
		elseif ($key == 'all_category_company'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->getCategorieBadgesList($this->TCategoryCompany, $langs->trans('AllCustomersCategories'));
		}
		elseif ($key == 'all_category_project'){
			// Petite astuce car je ne peux pas creer de input pour les categories donc je les ajoutent là
			$out = $this->getCategorieBadgesList($this->TCategoryProject, $langs->trans('AllProjectCategories'));
		}
		elseif ($key == 'fk_c_typent'){
			$out = getTypeEntLabel($this->fk_c_typent);
			if(!$out){ $out = ''; }
		}
		elseif ($key == 'fk_status'){
			$out =  $this->getLibStatut(5); // to fix dolibarr using 3 instead of 2
		}
		elseif ($key == 'priority_rank'){
			if(isset($this->fields['priority_rank']['arrayofkeyval'][$value])){
				$out = $langs->trans($this->fields['priority_rank']['arrayofkeyval'][$value]);
			}elseif (empty($value)){
				$out = $langs->trans($this->fields['priority_rank']['arrayofkeyval'][0]);
			}
		}
		else{
			$out = parent::showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);
		}

		return $out;
	}

	/**
	 * @param $Tcategorie array of category ID
	 * @return string
	 */
	public function getCategorieBadgesList($Tcategorie, $emptyMsg = ''){

		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

		$toprint = array();
		foreach($Tcategorie as $cid)
		{
			$c = new Categorie($this->db);
			if($c->fetch($cid)>0)
			{
				$ways = $c->print_all_ways();       // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formated text
				foreach($ways as $way)
				{
					// Check contrast with background and correct text color
					$forced_color = 'categtextwhite';
					if ($c->color)
					{
						if (colorIsLight($c->color)) $forced_color = 'categtextblack';
					}

					$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories '.$forced_color.'"'.($c->color?' style="background: #'.$c->color.';"':' style="background: #aaa"').'>'.img_object('','category').' '.$way.'</li>';
				}
			}
		}

		if(empty($toprint)){
			$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #ebebeb">'.$emptyMsg.'</li>';
		}

		return '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
	}

	/**
	 *    @param	string|int	            $type				Type of category ('customer', 'supplier', 'contact', 'product', 'member'). Old mode (0, 1, 2, ...) is deprecated.
	 *    @param    string		            $name			HTML field name
	 *    @param    array		            $selected    		Id of category preselected or 'auto' (autoselect category if there is only one element)
	 * 	  @return string
	 */
	public function generateFormCategorie($type,$name,$selected=array(), $morecss = "")
	{
		global $form;
		if(empty($morecss)) $morecss = 'minwidth200';
		$TOptions = $form->select_all_categories($type, $selected, $name, 0, 0, 1);
		return  $form->multiselectarray($name, $TOptions, $selected, 0, 0, $morecss, 0, 0, '', '', '', 1);
	}


	/**
	 * Function to update current object
	 * @param $key
	 */
	public function setValueFromPost($key)
	{
		$this->error = '';
		$request = $_POST;

		// prepare data
		if(!is_array($request)){
			$request[$key] = $request;
		}

		// set default value
		$value = '';
		if(isset($request[$key])){
			$value = $request[$key];
		}

		// TODO : implementer l'utilisation de la class Validate introduite en V15 de Dolibarr

		if(isset($this->fields[$key]))
		{
			if($this->fields[$key]['type'] == 'datetime'){
				$value .= ' '. $request[$key.'hour'] .':'.$request[$key.'min'].':'.$request[$key.'sec'];
				$this->setDate($key, $value);
			}
			else if($this->checkFieldType($key, 'date'))
			{
				$this->setDate($key, $value);
			}
			else if( $this->checkFieldType($key, 'array'))
			{
				$this->{$key} = $value;
			}
			else if( $this->checkFieldType($key, 'float') )
			{
				$this->{$key} = (double) price2num($value);
			}
			else if( $this->checkFieldType($key, 'int') ) {
				$this->{$key} = (int) price2num($value);
			}
			else
			{
				$this->{$key} = $value;
			}

			// for query search optimisation (or just working), only save 0 or a real id value and not the -1 empty value used by select form
			if(in_array($key, array('fk_country', 'fk_company', 'fk_project', 'fk_c_typent')) && ( $this->{$key} < 0 || $this->{$key} == '' ) ){
				$this->{$key} = 0;
			}
		}

	}


	/**
	 * Test type of field
	 *
	 * @param   string  $field  name of field
	 * @param   string  $type   type of field to test
	 * @return  bool
	 */
	private function checkFieldType($field, $type)
	{
		if (isset($this->fields[$field]) && method_exists($this, 'is' . ucfirst($type)))
		{
			return $this->{'is' . ucfirst($type)}($this->fields[$field]);
		}
		else
		{
			return false;
		}
	}


	/**
	 * Function to set date in field
	 *
	 * @param   string  $field  field to set
	 * @param   string  $date   formatted date to convert
	 * @return                  mixed
	 */
	public function setDate($field, $date)
	{
		if (empty($date))
		{
			$this->{$field} = '';
		}
		else
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			$this->{$field} = dol_stringtotime($date);
		}

		return $this->{$field};
	}


	/**
	 * Add quote to field value if necessary
	 *
	 * @param 	string|int	$value			Value to protect
	 * @param	array		$fieldsentry	Properties of field
	 * @return 	string
	 */
	protected function quote($value, $fieldsentry)
	{
		if (is_null($value) && isset($fieldsentry['nullvalue'])) return $this->db->escape($fieldsentry['nullvalue']);

		return parent::quote($value, $fieldsentry);
	}
}
