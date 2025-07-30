<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 John BOTELLA
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
 * \file    admin/setup.php
 * \ingroup discountrules
 * \brief   discountrules setup page.
 */

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
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

global $langs, $user,$conf,$db;
$inputCount = 1;
// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once __DIR__ . '/../class/discountrule.class.php';
require_once __DIR__ . '/../lib/discountrules.lib.php';
//require_once "../class/myclass.class.php";
// Translations
$langs->load("discountrules@discountrules");

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');


/*
 * Actions
 */
if ($action == 'setModuleOptions'){

	$minRateKey = GETPOST('DISCOUNTRULES_MINIMUM_RATE');
	$markupRateKey = '';

	foreach ($_POST as $key => $value) {
		if (strpos($key, 'param') === 0) {
			if ($value == 'DISCOUNTRULES_MINIMUM_RATE') {
				$suffix = substr($key, 5); // récupère N
				$minRateKey = 'value' . $suffix;
			}
			if ($value == 'DISCOUNTRULES_MARKUP_MARGIN_RATE') {
				$suffix = substr($key, 5);
				$markupRateKey = 'value' . $suffix;
			}
		}
	}

	$minValue = isset($_POST[$minRateKey]) ? (int)$_POST[$minRateKey] : '';
	$markupValue = isset($_POST[$markupRateKey]) ? (int)$_POST[$markupRateKey] : '';


	 if ($markupValue >= 0){
		 if ($minValue <= 0){
			 dol_syslog(__METHOD__ .$langs->trans('EmptyDISCOUNTRULES_MARKUP_MARGIN_RATEError') .': Taux minimum vide ou nul', LOG_ERR);
			 setEventMessages($langs->trans('EmptyDISCOUNTRULES_MARKUP_MARGIN_RATEError'), null, 'errors');
			 $action = '';
		 }
	 }else{
		 if ($minValue > 0) {
			 dolibarr_del_const($db, 'DISCOUNTRULES_MINIMUM_RATE');
			 dol_syslog(__METHOD__ . $langs->trans('ErrorNoMinRate').': Suppression du taux min car type désélectionné', LOG_ERR);
			 setEventMessages($langs->trans('ErrorNoMinRate'), null, 'errors');
			 $action = '';
		 }
	 }

}

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

/*
 * View
 */

$form=new Form($db);
$page_name = "discountrulesSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, "super_atm.gif@discountrules");

// Configuration header
$head = discountrulesAdminPrepareHead();
dol_fiche_head(
	$head,
	'settings',
	$langs->trans("ModulediscountrulesName"),
	-1,
	"discountrules@discountrules"
);
dol_fiche_end(-1);


$var=0;

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';


print load_fiche_titre($langs->trans("GlobalConf"), '', '');
print '<table class="noborder" width="100%">';

// TODO : Use new dolibarr setup system

// conf qui permet de garder le comportement originel du module qui recherchait les règles de remises en prenant comme référence la date courante
_printOnOff('DISCOUNTRULES_SEARCH_WITHOUT_DOCUMENTS_DATE');

_printOnOff('DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_TAKE_POS');
_printOnOff('DISCOUNTRULES_FORCE_RULES_PRICES', $langs->trans('DISCOUNTRULES_FORCE_RULES_PRICES'), $langs->trans('DISCOUNTRULES_FORCE_RULES_PRICES_DESC'));

_printOnOff('DISCOUNTRULES_USE_MARKUP_MARGIN_RATE', '' , $langs->trans('UsedMarkupMarginRateHelp'));

// Taux sélectionné
$options = array($langs->trans('DISCOUNTRULES_MARKRATE'),
				 $langs->trans('DISCOUNTRULES_MARGINRATE')
		   );
$confKey = 'DISCOUNTRULES_MARKUP_MARGIN_RATE';
$type = Form::selectarray('value'.($inputCount+1), $options, getDolGlobalInt($confKey, -1),1,0,0,'',1 );
_printInputFormPart($confKey, $langs->trans('SelectMarkupMarginRate'), '', array(), $type, 'SelectMarkupMarginRateHelp');

// Taux minimum souhaité
$metas = array( 'type' => 'number', 'step' => '0.1', 'min' => 0 );
_printInputFormPart('DISCOUNTRULES_MINIMUM_RATE', $langs->trans('SelectMinimumRate'), '', $metas);

print '</table>';



print load_fiche_titre($langs->trans("DialogUpdatePrice"), '', '');
print '<table class="noborder" width="100%">';
_printOnOff('DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_ALL_LINES', '', $langs->trans('DISCOUNTRULES_ALLOW_APPLY_DISCOUNT_TO_ALL_LINES_DESC'));
_printOnOff('DISCOUNTRULES_MASS_LINE_ALLOW_UPDATE_ON_ZERO');
_printOnOff('DISCOUNTRULES_PRECHECKED_LINES_ON_DIALOG');
print '</table>';


print load_fiche_titre($langs->trans("SearchDiscountAllreadyApplied"), '', '');
print '<table class="noborder" width="100%">';
_setupPrintTitle('TypeOfDocuments');
_printOnOff('DISCOUNTRULES_SEARCH_IN_ORDERS');
_printOnOff('DISCOUNTRULES_SEARCH_IN_PROPALS');
_printOnOff('DISCOUNTRULES_SEARCH_IN_INVOICES');
_setupPrintTitle('OptionsConditions');
_printOnOff('DISCOUNTRULES_SEARCH_IN_DOCUMENTS_QTY_EQUIV');
_printOnOff('DISCOUNTRULES_SEARCH_IN_DOCUMENTS_PROJECT_EQUIV');

$staticDiscountRule = new DiscountRule($db);
$options = array();
foreach ($staticDiscountRule->fields['priority_rank']['arrayofkeyval'] as $arraykey => $arrayval) {
	$options[$arraykey] = $langs->trans($arrayval);
}

$confKey = 'DISCOUNTRULES_SEARCH_DOCUMENTS_PRIORITY_RANK';
$type = Form::selectarray('value'.($inputCount+1), $options, getDolGlobalInt($confKey) ?? '');
_printInputFormPart($confKey, '', '', array(), $type, 'PriorityRuleRankHelp');


$arrayOption = [
	'last_price' => $langs->trans('LastCustomerPrice'),
	'best_price' => $langs->trans('BestCustomerPrice'),
];

$value = 'best_price'; // value par défaut
if(getDolGlobalString('DISCOUNTRULES_DOCUMENT_SEARCH_TYPE') && isset($arrayOption[getDolGlobalString('DISCOUNTRULES_DOCUMENT_SEARCH_TYPE')])){
	$value = getDolGlobalString('DISCOUNTRULES_DOCUMENT_SEARCH_TYPE');
}

$input = $form->selectArray('value'.($inputCount+1), $arrayOption, $value);
_printInputFormPart('DISCOUNTRULES_DOCUMENT_SEARCH_TYPE', '', '', array(), $input);

$metas = array( 'type' => 'number', 'step' => '1', 'min' => 0 );
_printInputFormPart('DISCOUNTRULES_SEARCH_DAYS', '', '', $metas);


print '</table>';

/**
 * IN DEVELOPMENT
 */
if (getDolGlobalInt('MAIN_FEATURE_LEVEL') >= 2) {
	print load_fiche_titre($langs->trans("ParameterForDevelopmentOrDeprecated"), '', '');
	print '<div class="warning">' . $langs->trans("ParameterForDevelopmentOrDeprecatedHelp") . '</div>';
	print '<table class="noborder" width="100%">';

	print '</table>';
}

_updateBtn();

print '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();

/**
 * Print an update button
 *
 * @return void
 */
function _updateBtn()
{
    global $langs;
    print '<div style="text-align: right;" >';
    print '<input type="submit" class="butAction" value="'.$langs->trans("Save").'">';
    print '</div>';
}

/**
 * Print a On/Off button
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description
 *
 * @return void
 */
function _printOnOff($confkey, $title = false, $desc = '')
{
    global $var, $bc, $langs;
	print '<tr class="oddeven">';
    print '<td>'.($title?$title:$langs->trans($confkey));
    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }
    print '</td>';
    print '<td class="center" width="20">&nbsp;</td>';
    print '<td class="right" width="300">';
    print ajax_constantonoff($confkey);
    print '</td></tr>';
}


/**
 * Print a form part
 *
 * @param string $confkey the conf key
 * @param bool   $title   Title of conf
 * @param string $desc    Description of
 * @param array  $metas   html meta
 * @param string $type    type of input textarea or input
 * @param bool   $help    help description
 *
 * @return void
 */
function _printInputFormPart($confkey, $title = false, $desc = '', $metas = array(), $type = 'input', $help = false)
{
    global $var, $bc, $langs, $conf, $db, $inputCount;
    $var=!$var;
    $inputCount = empty($inputCount)?1:($inputCount+1);
    $form=new Form($db);

    $defaultMetas = array(
        'name' => 'value'.$inputCount
    );

    if ($type!='textarea') {
        $defaultMetas['type']   = 'text';
		if(empty($conf->global->{$confkey})) $conf->global->{$confkey} = '';
        $defaultMetas['value']  = $conf->global->{$confkey};
    }


    $metas = array_merge($defaultMetas, $metas);
    $metascompil = '';
    foreach ($metas as $key => $values) {
        $metascompil .= ' '.$key.'="'.$values.'" ';
    }

    print '<tr '.$bc[$var].'>';
    print '<td>';

    if (!empty($help)) {
        print $form->textwithtooltip(($title?$title:$langs->trans($confkey)), $langs->trans($help), 2, 1, img_help(1, ''));
    } else {
        print $title?$title:$langs->trans($confkey);
    }

    if (!empty($desc)) {
        print '<br><small>'.$langs->trans($desc).'</small>';
    }

    print '</td>';
    print '<td class="center" width="20">&nbsp;</td>';
    print '<td class="right" width="300">';
    print '<input type="hidden" name="param'.$inputCount.'" value="'.$confkey.'">';

    print '<input type="hidden" name="action" value="setModuleOptions">';
    if ($type=='textarea') {
        print '<textarea '.$metascompil.'  >'.dol_htmlentities($conf->global->{$confkey}).'</textarea>';
    } elseif ($type == 'input') {
        print '<input '.$metascompil.'  />';
    } else {
    	print $type;
	}
    print '</td></tr>';
}
/**
 * Display title
 * @param string $title
 */
function _setupPrintTitle($title = "", $width = 300)
{
	global $langs;
	print '<tr class="liste_titre">';
	print '<th colspan="3">' . $langs->trans($title) . '</th>' . "\n";
	print '</tr>';
}
