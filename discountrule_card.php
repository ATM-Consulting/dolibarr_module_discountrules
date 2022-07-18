<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       discountrule_card.php
 *		\ingroup    discountrules
 *		\brief      Page to create/edit/view discountrule
 */

//if (! defined('NOREQUIREUSER'))          define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))            define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))           define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))          define('NOREQUIRETRAN','1');
//if (! defined('NOSCANGETFORINJECTION'))  define('NOSCANGETFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOSCANPOSTFORINJECTION')) define('NOSCANPOSTFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOCSRFCHECK'))            define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))           define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))         define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))          define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))          define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))          define('NOREQUIREAJAX','1');         // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

include_once __DIR__.'/class/discountrule.class.php';
include_once __DIR__.'/lib/discountrules.lib.php';


// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules","other", "companies"));

// Get parameters
$id			= GETPOST('id', 'int');
$action		= GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$TCategoryProduct = GETPOST('TCategoryProduct','array');
$TCategoryCompany = GETPOST('TCategoryCompany','array');
$TCategoryProject = GETPOST('TCategoryProject','array');

$fk_product = GETPOST('fk_product', 'int');

// Initialize technical objects
$object = new DiscountRule($db);

$object->picto = 'discountrules_card@discountrules';

if($id>0)
{
    $object->fetch($id);
	$fk_product = $object->fk_product;
}

$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->discountrules->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('discountrulecard'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('discountrule');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all",'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
}

if (empty($action)) $action='view';


// Security check
if (empty($conf->discountrules->enabled)) accessforbidden('Module not enabled');
if ($user->socid > 0 // Protection if external user
	|| empty($user->rights->discountrules->read) // Check user right
)
{
	accessforbidden();
}


// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals



/*
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error=0;

	if ($cancel)
	{
		if ($action != 'addlink')
		{
			$urltogo=$backtopage?$backtopage:dol_buildpath('/discountrules/discountrule_list.php',1);
			header("Location: ".$urltogo);
			exit;
		}
		if ($id > 0 || ! empty($ref)) $ret = $object->fetch($id,$ref);
		$action='';
	}

	// Action to add record
	if (($action == 'add' || $action == 'update') && ! empty($user->rights->discountrules->create))
	{
		$errors = 0;

		// for new rules
		if(empty($object->id)){
			$object->fk_product = $fk_product;

			$object->initFieldsParams();
		}


        foreach ($object->fields as $key => $val)
        {
			if (in_array($key, array('rowid', 'entity', 'date_creation', 'tms', 'import_key'))) continue;	// Ignore special fields
			if (isset($val['visible']) && in_array($val['visible'], array(0,2))) continue;

			$object->setValueFromPost($key); // Set standard attributes
            
            if (!empty($val['notnull']) && $object->$key == '')
            {
                $error++;
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val['label']) ), null, 'errors');
            }
        }

        if(empty($object->product_price) && empty($object->reduction) && empty($object->product_reduction_amount)){
			$error++;
			$fieldsList = $langs->transnoentitiesnoconv($object->fields['reduction']['label'])
				 . ', ' . $langs->transnoentitiesnoconv($object->fields['product_price']['label'])
				 . ', ' . $langs->transnoentitiesnoconv($object->fields['product_reduction_amount']['label']);
			setEventMessages($langs->trans("ErrorOneOffThisFieldsAreRequired", $fieldsList ), null, 'errors');
		}

		$object->TCategoryProduct =  array();
		if(empty($object->fk_product)){ $object->TCategoryProduct =  $TCategoryProduct; }

		$object->TCategoryProject =  array();
		if(empty($object->fk_project)){ $object->TCategoryProject =  $TCategoryProject; }

		$object->TCategoryCompany =  $TCategoryCompany;


		if ($object->id > 0)
		{
			if (!$error) {
				$result = $object->updateCommon($user);
				if ($result > 0) {
					$action = 'view';
					setEventMessage($langs->trans('Saved'));
				} else {
					// Creation KO
					if (!empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
					else setEventMessages($object->error, null, 'errors');
					$action = 'edit';
				}
			} else {
				$action = 'edit';
			}
		}
		else{
			if (! $error)
			{
				$result=$object->createCommon($user);
				if ($result > 0)
				{
					// Creation OK
					if(!empty($backtopage) && filter_var($backtopage, FILTER_VALIDATE_URL)){
						$urltogo = $backtopage;
					}
					else{
						$urltogo = dol_buildpath('/discountrules/discountrule_card.php',1);
						$urltogo.= '?id=' . intval($object->id) ;
						if(!empty($fk_product)){
							$urltogo.= '&fk_product=' . intval($fk_product) ;
						}
					}

					header("Location: ".$urltogo);
					exit;
				}
				else
				{
					// Creation KO
					if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
					else  setEventMessages($object->error, null, 'errors');
					$action='create';
				}
			}
			else
			{
				$action='create';
			}
		}
	}


    if ($action == 'activate' && !empty($user->rights->discountrules->create)){
        $object->setActive($user);
    }

    if ($action == 'disable' && !empty($user->rights->discountrules->create)){
        $object->setDisabled($user);
    }



	// Action to delete
	if ($action == 'confirm_delete' && ! empty($user->rights->discountrules->delete))
	{
		$result=$object->delete($user);
		if ($result > 0)
		{
			// Delete OK
			setEventMessages("RecordDeleted", null, 'mesgs');
            $url = dol_buildpath('/discountrules/discountrule_list.php',1);
            if(!empty($fk_product)){
                $url.= '?fk_product=' . intval($fk_product) ;
            }
			header("Location: ".$url);
			exit;
		}
		else
		{
			if (! empty($object->errors)) setEventMessages(null, $object->errors, 'errors');
			else setEventMessages($object->error, null, 'errors');
		}
	}
}




/*
 * VIEW
 *
 * Put here all code to build page
 */

$form=new Form($db);

llxHeader('','discountrule','');





if(!empty($fk_product)){
	$product = $object->product = new Product($db);
	if($object->product->fetch($fk_product) > 0)
	{
		$object->fields['product_price']['visible'] = 1;
		$object->fields['fk_product']['visible'] = 1;
	}
	else{
		$object->product = false;
	}
}



// Part to create
if ($action == 'create')
{
	$title = $langs->trans("NewDiscountRule");
	if($product){
		$title = $langs->trans("NewDiscountRuleForProduct", $product->label);
	}
	print load_fiche_titre($title, '', 'discountrules@discountrules');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    if(!empty($fk_product)){
		$object->fk_product = $fk_product;
		$object->initFieldsParams();
        print '<input type="hidden" name="fk_product" value="'.intval($fk_product).'">';
    }

	dol_fiche_head(array(), '');

	print '<div class="info" >'.$langs->trans("ExplainPriorityOfRuleApplied").'</div>';

	print '<table class="border centpercent">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';


	dol_fiche_end();

    $linkbackUrl = dol_buildpath('discountrules/discountrule_list.php',1);
    if(!empty($fk_product)){
        $linkbackUrl.= '?fk_product=' . intval($fk_product);
    }

	print '<div class="center"><input type="submit" class="butAction" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
	print ' &nbsp; <a class="butAction" href="'.$linkbackUrl.'" >'.$langs->trans("Cancel").'</a>';
	print '</div>';
	print '</form>';
}



// Part to edit record
if ($id && $action == 'edit')
{
	print load_fiche_titre($langs->trans("discountrules"), '', 'discountrules@discountrules');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
    print '<input type="hidden" name="fk_product" value="'.$object->fk_product.'">';

	dol_fiche_head();

	print '<div class="info" >'.$langs->trans("ExplainPriorityOfRuleApplied").'</div>';

	print '<table class="border tableforfield" width="100%">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

    $linkbackUrl = dol_buildpath('discountrules/discountrule_card.php',1).'?id='.$object->id;
    if(!empty($fk_product)){
        $linkbackUrl.= '&fk_product=' . intval($fk_product);
    }

	print '<div class="center"><input type="submit" class="butAction" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <a class="butAction" href="'. $linkbackUrl .'" >'.$langs->trans("Cancel").'</a>';
	print '</div>';

	print '</form>';
}


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create')))
{
    //$res = $object->fetch_optionals($object->id, $extralabels);

    $head = discountrulesPrepareHead($object);

	print '<div class="discount-rule-head-container --status-'.$object->fk_status.'">';
	dol_fiche_head($head, 'card', $langs->trans("Discountrule"), -1);
	print '<div>';

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('Delete'), $langs->trans('ConfirmDelete'), 'confirm_delete', '', 0, 1);
	}

	if (! $formconfirm) {
	    $parameters = array('lineid' => $lineid);
	    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	    if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
	    elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;



	// Object card
	// ------------------------------------------------------------
	discountRulesBannerTab($object, 1);

	
	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswithonsecondcolumn';
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

    print '<div class="clearboth"></div><br />';

    print '<div class="tabsAction">'."\n";
    $parameters=array();
    $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

    if (empty($reshook))
    {
        $actionUrl = $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=';

        if ($object->fk_status !== $object::STATUS_ACTIVE) {
            print dolGetButtonAction($langs->trans("Activate"), '', 'default', $actionUrl . 'activate', '', $user->rights->discountrules->create);
        }
        elseif ($object->fk_status === $object::STATUS_ACTIVE) {
            print dolGetButtonAction($langs->trans("Disable"), '', 'default', $actionUrl . 'disable', '', $user->rights->discountrules->create);
        }

        //print dolGetButtonAction($langs->trans("Clone"), '', 'default', $actionUrl . 'clone', '', $user->rights->discountrules->create);
        print dolGetButtonAction($langs->trans("Modify"), '', 'default', $actionUrl . 'edit', '', $user->rights->discountrules->create);
        print dolGetButtonAction($langs->trans("Delete"), '', 'danger', $actionUrl . 'delete', '', $user->rights->discountrules->delete);
    }
    print '</div>'."\n";

	dol_fiche_end();



}


// End of page
llxFooter();
$db->close();
