<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 John BOTELLA
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
 * 	\defgroup   discountrules     Module discountrules
 *  \brief      discountrules module descriptor.
 *
 *  \file       htdocs/discountrules/core/modules/moddiscountrules.class.php
 *  \ingroup    discountrules
 *  \brief      Description and activation file for module discountrules
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Classes.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module discountrules
 */
class moddiscountrules extends DolibarrModules
{
	// @codingStandardsIgnoreEnd
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 104091;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'discountrules';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "products";
		// Module position in the family
		$this->module_position = 500;
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '001', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModulediscountrulesName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModulediscountrulesDesc' not found (MyModue is name of module).
		$this->description = "ModulediscountrulesDesc";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "discountrulesDescription (Long)";

		$this->editor_name = 'ATM Consulting';
		$this->editor_url = 'https://www.atm-consulting.fr';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'

		$this->version = '2.25.4';

		// Key used in llx_const table to save module status enabled/disabled (where discountrules is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='discountrules_card@discountrules';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /discountrules/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /discountrules/core/modules/barcode)
		// for specific css file (eg: /discountrules/css/discountrules.css.php)
		$this->module_parts = array(
		                        	'triggers' => 1,                                 	// Set this to 1 if module has its own trigger directory (core/triggers)
									'login' => 0,                                    	// Set this to 1 if module has its own login method directory (core/login)
									'substitutions' => 0,                            	// Set this to 1 if module has its own substitution function file (core/substitutions)
									'menus' => 0,                                    	// Set this to 1 if module has its own menus handler directory (core/menus)
									'theme' => 0,                                    	// Set this to 1 if module has its own theme directory (theme)
		                        	'tpl' => 0,                                      	// Set this to 1 if module overwrite template dir (core/tpl)
									'barcode' => 0,                                  	// Set this to 1 if module has its own barcode directory (core/modules/barcode)
									'models' => 0,                                   	// Set this to 1 if module has its own models directory (core/modules/xxx)
									'css' => array('/discountrules/css/discountrules.css'),	// Set this to relative path of css file if module has its own css file
	 								'js' => array('/discountrules/js/discountrules.js.php'),          // Set this to relative path of js file if module must load a js on all pages
		                            'hooks' => array(
		                                'propalcard', 
		                                'ordercard', 
		                                'invoicecard',
		                                'globalcard',
		                                'productservicelist',
		                                'servicelist',
		                                'productlist',
										'productcard',
										'discountrulelist', // pour completeTabsHead
										'societecard',
										'takeposinvoice'
										//'globalcard'
		                            ) 
		                        );

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/discountrules/temp","/discountrules/subdir");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into discountrules/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@discountrules");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array('modCategorie');		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->phpmin = array(7,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(16,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("discountrules@discountrules","importdiscountrules@discountrules");
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)

		// Url to the file with your last numberversion of this module
		require_once __DIR__ . '/../../class/techatm.class.php';
		$this->url_last_version = ATM\DiscountRules\TechATM::getLastModuleVersionUrl($this);

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('discountrules_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('discountrules_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			1=>array('DISCOUNTRULES_MOD_LAST_RELOAD_VERSION', 'chaine', $this->version, 'Last version reload', 0, 'allentities', 0)
		);

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:mylangfile@discountrules:$user->rights->discountrules->read:/discountrules/mynewtab1.php?id=__ID__',  					// To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@discountrules:$user->rights->othermodule->read:/discountrules/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
        //                              'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
        // Can also be:	$this->tabs = array('data'=>'...', 'entity'=>0);
        //
		// where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view
        $this->tabs = array(
				'product:+discountrules,:TabTitleDiscountRule,DiscountRule,/discountrules/class/discountrule.class.php,countProductOccurrences:discountrules@discountrules:$user->rights->discountrules->read:/discountrules/discountrule_list.php?contextpage=discountrulelistforproduct&fk_product=__ID__',
            'thirdparty:+discountrules:TabTitleDiscountRule:discountrules@discountrules:$user->rights->discountrules->read:/discountrules/discountrule_list.php?contextpage=discountrulelistforcompany&fk_company=__ID__',
            // 'thirdparty:+discountrules:TabTitleDiscountRule:discountrules@discountrules:$user->rights->discountrules->read:/discountrules/discountrule_list.php?fk_company=__ID__', // Todo : rectifier le bug de bouble affichage
        );

		if (! isset($conf->discountrules) || ! isModEnabled('discountrules'))
        {
        	$conf->discountrules=new stdClass();
        	$conf->discountrules->enabled=0;
        }

        // Dictionaries
		$this->dictionaries=array();
        /* Example:
        $this->dictionaries=array(
            'langs'=>'mylangfile@discountrules',
            'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
            'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
            'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
            'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
            'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
            'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
            'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
            'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
            'tabcond'=>array($conf->discountrules->enabled,$conf->discountrules->enabled,$conf->discountrules->enabled)												// Condition to show each dictionary
        );
        */


        // Boxes/Widgets
		// Add here list of php file(s) stored in discountrules/core/boxes that contains class to show a widget.
        $this->boxes = array(
        	//0=>array('file'=>'discountruleswidget1.php@discountrules','note'=>'Widget provided by discountrules','enabledbydefaulton'=>'Home'),
        	//1=>array('file'=>'discountruleswidget2.php@discountrules','note'=>'Widget provided by discountrules'),
        	//2=>array('file'=>'discountruleswidget3.php@discountrules','note'=>'Widget provided by discountrules')
        );


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		$this->cronjobs = array(
			//0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/discountrules/class/discountrulesmyjob.class.php', 'objectname'=>'discountrulesMyJob', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true)
		);
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );


		// Permissions
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'ReadDiscountsRules';// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'CreateUpdateDiscountsRules';// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'create';			// In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'DeleteDiscountsRules';// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';			// In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)


		$r++;
		$this->rights[$r][0] = $this->numero . $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'RightUserCanOverrideForcedMod';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'overrideForcedMod';	// In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)
		$this->rights[$r][5] = '';				    // In php code, permission will be checked by test if ($user->rights->discountrules->level1->level2)




		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus

		// Example to declare a new Top Menu entry and its Left menu entry:
		/* BEGIN  TOPMENU */
		/*$this->menu[$r++]=array('fk_menu'=>'',			                // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'top',			                // This is a Top menu entry
								'titre'=>'discountrules',
								'mainmenu'=>'discountrules',
								'leftmenu'=>'',
								'url'=>'/discountrules/discountrulesindex.php',
								'langs'=>'discountrules@discountrules',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->discountrules->enabled',	// Define condition to show or hide menu entry. Use '$conf->discountrules->enabled' if entry must be visible if module is enabled.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->discountrules->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
*/
		/* END TOPMENU */

		// Example to declare a Left Menu entry into an existing Top menu entry:
		//BEGIN  LEFTMENU MYOBJECT
		$r++;
		$this->menu[$r]=array(	
                    		    'fk_menu'=>'fk_mainmenu=products',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
                    		    'type'=>'left',			                // This is a Left menu entry
                    		    'titre'=>'ListDiscountRule',
                    		    'mainmenu'=>'products',
                    		    'leftmenu'=>'discountrules',
                    		    'url'=>'/discountrules/discountrule_list.php',
                    		    'langs'=>'discountrules@discountrules',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
                    		    'position'=>1000+$r,
                    		    'enabled'=>'isModEnabled("discountrules")',  // Define condition to show or hide menu entry. Use '$conf->discountrules->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'$user->hasRight("discountrules", "read")',			                // Use 'perms'=>'$user->rights->discountrules->level1->level2' if you want your menu with a permission rules
                    		    'target'=>'',
                    		    'prefix' => '<span class="fas fa-tag em092 pictofixedwidth discount-rules-left-menu-picto" style="color: #e72400;"></span>',
                    		    'user'=>0

		);				                // 0=Menu for internal users, 1=external users, 2=both

		$r++;


        $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=discountrules',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'NewDiscountRule',
								'mainmenu'=>'products',
								'leftmenu'=>'discountrulesCreate',
								'url'=>'/discountrules/discountrule_card.php?action=create',
								'langs'=>'discountrules@discountrules',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'isModEnabled("discountrules")',  // Define condition to show or hide menu entry. Use '$conf->discountrules->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'$user->hasRight("discountrules", "create")',			                // Use 'perms'=>'$user->rights->discountrules->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>0);				                // 0=Menu for internal users, 1=external users, 2=both
		$r++;

        $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=discountrules',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'MenuDiscountRuleListe',
            'mainmenu'=>'products',
            'leftmenu'=>'discountrulesList',
            'url'=>'/discountrules/discountrule_list.php',
            'langs'=>'discountrules@discountrules',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=>'isModEnabled("discountrules")',  // Define condition to show or hide menu entry. Use '$conf->discountrules->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->hasRight("discountrules", "read")',		                // Use 'perms'=>'$user->rights->discountrules->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
		);

        $r++;

        $this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=discountrules',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
            'type'=>'left',			                // This is a Left menu entry
            'titre'=>'MenuDiscountRulePricesList',
            'mainmenu'=>'products',
            'leftmenu'=>'discountrulesPricesList',
            'url'=>'/discountrules/prices_list.php',
            'langs'=>'discountrules@discountrules',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
            'position'=>1000+$r,
            'enabled'=>'isModEnabled("discountrules")',  // Define condition to show or hide menu entry. Use '$conf->discountrules->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->hasRight("discountrules", "read")',			                // Use 'perms'=>'$user->rights->discountrules->level1->level2' if you want your menu with a permission rules
            'target'=>'',
            'user'=>0
		);

		//Menu  import discount Rules
		$r++;
		$this->menu[$r] = array(
			'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=import',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',
			'titre'=>'idrImportDiscountRules',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth valignmiddle"'),
			'mainmenu'=>'importdiscountrules',
			'leftmenu'=>'importdiscountrules_left',

			'url'=>'/discountrules/discount_rules_import.php?datatoimport=importdiscountrules&mainmenu=tools',
			'langs'=>'importdiscountrules@discountrules', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'isModEnabled("discountrules")', // Define condition to show or hide menu entry. Use '$conf->importdiscountrules->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->hasRight("discountrules", "create")',			                // Use 'perms'=>'$user->rights->cliaufildesmatieres->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>0, // 0=Menu for internal users, 1=external users, 2=both
		);


        $r++;


		// Exports
		$r=1;

		// Example:
		/* BEGIN MODULEBUILDER EXPORT MYOBJECT
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='discountrules';	                         // Translation key (used only if key ExportDataset_xxx_z not found)
        $this->export_enabled[$r]='1';                               // Condition to show export in list (ie: '$user->id==3'). Set to 1 to always show when module is enabled.
        $this->export_icon[$r]='generic:discountrules';					 // Put here code of icon then string for translation key of module name
		//$this->export_permission[$r]=array(array("discountrules","level1","level2"));
        $this->export_fields_array[$r]=array('t.rowid'=>"Id",'t.ref'=>'Ref','t.label'=>'Label','t.datec'=>"DateCreation",'t.tms'=>"DateUpdate");
		$this->export_TypeFields_array[$r]=array('t.rowid'=>'Numeric', 't.ref'=>'Text', 't.label'=>'Label', 't.datec'=>"Date", 't.tms'=>"Date");
		// $this->export_entities_array[$r]=array('t.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_dependencies_array[$r]=array('invoice_line'=>'fd.rowid','product'=>'fd.rowid');   // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'discountrule as t';
		// $this->export_sql_order[$r] .=' ORDER BY t.ref';
		// $r++;
		END MODULEBUILDER EXPORT MYOBJECT */



		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT MYOBJECT */
//
//		$this->import_code[$r] = $this->rights_class.'_'.$r;
//		$this->import_label[$r] = "discountrules"; // Translation key
//		$this->import_icon[$r] = 'discountrules@discountrules';
//		// for example csv file
//		$this->import_fields_array[$r] = array(
//			"label" 					=> "label",
//			"fk_project" 				=> "refProject",
//			"fk_product" 				=> "refProduct",
//			"fk_company" 				=> "refCompany",
//			"fk_country" 				=> "refCountry",
//			"priority_rank" 			=>"priorityRank",
//			"fk_c_typent" 				=> "cTypeEnt",
//
//
//
//			"all_category_product" 		=>"allCategoryProduct",
//			"all_category_company" 		=>"allCategoryCompany",
//
//			"reduction" 				=> "reduction",
//			"from_quantity" 			=> "fromQuantity",
//			"product_price" 			=> "productPrice",
//			"product_reduction_amount" => "productReductionAmount",
//			"date_from" 				=>"dateFrom",
//			"date_to" 					=>"dateTo",
//			"activation" 				=>"activation",
//
//
//		);
//
//		//@todo exemple à remplir
//		$this->import_examplevalues_array[$r] = array(
//			"label" 					=> "ligne Exemple",
//
//			"fk_project" 				=> "PJ2201-0001",
//			"fk_product" 				=> "PRODUIT_IMPORT_01",
//			"fk_company" 				=> "KEVIN",
//			"fk_country" 				=> "code pays. ex :  US",
//			"priority_rank" 			=>"vide ou 0  si pas de priorité sinon numérique entre 1 et 5",
//			"fk_c_typent" 				=> "cTypeEnt",
//
//
//			"all_category_product" 		=>"vide pour toutes les catégories sinon liste des ref séparées par des virgules. ex : TCP01,TCP02",
//			"all_category_company" 		=>"vide pour toutes les catégories sinon liste des ref séparées par des virgules. ex : TCP01,TCP02",
//
//			"from_quantity" 			=> "numérique",
//			"product_price" 			=> "numérique",
//			"product_reduction_amount" => "5",
//			"reduction" 				=> "10",
//			"date_from" 				=>"date au format jj/mm/yyyy",
//			"date_to" 					=>"date au format jj/mm/yyyy",
//			"activation" 				=>"vide/0 pour désactiver 1 pour activer",
//
//			);
		/* END MODULEBUILDER IMPORT MYOBJECT */


	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options='')
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";

		// TODO à retirer après la version 3.0
		// ----------------------------------------------------------
		$sql = "SELECT * FROM ".MAIN_DB_PREFIX."discountrule WHERE 1 LIMIT 1";
		$resql = $this->db->query($sql);

		$first_install = false;
		if ($this->db->lasterrno() == 'DB_ERROR_NOSUCHTABLE') $first_install = true; // première install => la table n'existe pas

		if (
			!$first_install && !getDolGlobalInt('DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE')
			&& !getDolGlobalString('DISCOUNTRULES_MOD_LAST_RELOAD_VERSION')
		) {
			// on set la conf pour maintenir le comportement historique (rétro cohérence du comportement)
			$result = dolibarr_set_const($this->db, 'DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE', '1', 'chaine', 0, '', $conf->entity);
		}
		// ----------------------------------------------------------

		$sql = array();

		$this->_load_tables('/discountrules/sql/');

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('myattr1', "New Attr 1 label", 'boolean', 1, 3, 'thirdparty');
		//$result2=$extrafields->addExtraField('myattr2', "New Attr 2 label", 'string', 1, 10, 'project');

		return $this->_init($sql, $options);
	}

	/**
	 * Function called when module is disabled.
	 * Remove from database constants, boxes and permissions from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes')
	 * @return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}

}
