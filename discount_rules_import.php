<?php
/* Copyright (C) 2020 ATM Consulting <support@atm-consulting.fr>
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

//require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
dol_include_once('discountrules/lib/discountrules.lib.php');
dol_include_once('discountrules/class/importrule.class.php');

if(empty($user->rights->discountrules->create)) accessforbidden();
$langs->load('importdiscountrules@discountrules');

$action = GETPOST('action', 'alphanohtml');

$startLine 		= GETPOSTISSET('startLine','int') ? GETPOST('startLine','int') : 2;
$endline		= GETPOSTISSET('endline','int') ? GETPOST('endline','int') : '';
$importRule = new ImportRule($db);

/*
 * ACTION
 */

if($action == 'importCSV') {
	$tokenSend = GETPOST('token');
	if($_SESSION['token'] != $tokenSend){
		setEventMessage($langs->trans("TokenError"), "errors");
		$action == '';
	}
	else{

		$step = 'importCSV';
		$filename = GETPOST('CSVFile', 'alpha');

		if (isset($_FILES['CSVFile'])) {

			$filePath = $_FILES['CSVFile']['tmp_name'];

			$importLogs = $importRule->idrGetDiscountFromCSV(
				$filePath,
				GETPOST('srcEncoding', 'alpha'),
				'idr' . date('Ymd'),
				$startLine,
				$endline
			);

			$lineNumber = 1;

			$linecount = count(file($filePath));


			if (($startLine > $endline && $endline > 0)) {
				$importLogs[] = $importRule->newImportLogLine('error', $langs->trans("startmustbeInferior"));
			}

			if ($startLine > $linecount) {
				$importLogs[] = $importRule->newImportLogLine('error', $langs->trans("startafterendOffileLine"));
			}

			$action = 'showlogs';
		}
	}
}



/*
 * VIEW
 */

$form = new Form($db);

llxHeader('<link rel="stylesheet" href="' . dol_buildpath('/discountrules/css/idr.css', 1) . '" />');

print load_fiche_titre($langs->trans("idrImportDiscountRules"), '', "super_atm.gif@discountrules");


$activeTab = 'SelectFile';
if($action=='showlogs'){
	$activeTab = 'showlogs';
}

$head = discountrulesImportPrepareHead($activeTab);
print dol_get_fiche_head(
	$head,
	$activeTab,
	$langs->trans("ModulediscountrulesName"),
	-1,
	"discountrules@discountrules"
);
print dol_get_fiche_end(-1);


if($action=='showlogs' && !empty($importRule)){

	print '<fieldset>';
	print '<legend>'.$langs->trans('ImportResults').'</legend>';


	print '<h2>';

	if ($importRule->nbImported) {
		print $langs->trans('importDone');
		print ' <span class="badge badge-success">';
		print $langs->trans('XDiscountsRulesImported', $importRule->nbImported);
		print '</span>';
	}

	if ($importRule->nbErrors) {

		if ($importRule->nbImported == 0) {
			print $langs->trans('importAborted');
		}

		print ' <span class="badge badge-danger">';
		print $langs->trans('XErrors', $importRule->nbErrors);
		print '</span>';
	}
	print '</h2>';

//		print_barre_liste($langs->trans("idrImportDiscountRules"), 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, -1, '', 0, '', '', 0, 1, 1);
	if (!empty($importLogs)){
		$lineNumber = 1;
		echo '<table class="idr import-log centpercent">';
		foreach ($importLogs as $logLine) {
			echo '<tr class="log-' . $logLine['type'] . '"><td>' . $logLine['msg'] . '</td></tr>';
		}
		echo '</table>';
	}
	print '</fieldset>';
}
else{

	print '<div class="clearboth"></div>';
	print '<div class="fichecenter">';

	print '<div class="fichehalfleft">';
	_showImportForm($form,$startLine,$endline);
	print '</div>';

	print '<div class="fichehalfright">';
	_showHelp();
	print '</div>';

	print '</div>'; // close fichecenter
}


llxFooter();

function _showImportForm($form,$startline,$endline) {
	global $langs;
	$newToken = function_exists('newToken') ? newToken() : $_SESSION['newtoken'];
	$acceptedEncodings = array(
		'UTF-8',
		'latin1',
		'ISO-8859-1',
		'ISO-8859-15',
		'macintosh'
	);
	?>
	<form method="POST" enctype="multipart/form-data">
		<fieldset>
			<legend><?php echo $langs->trans('DiscountRuleImpConfYourImport'); ?></legend>
			<label for="CSVFile">
				<?php echo $langs->trans('PickCSVFile'); ?> :
			</label>
			<input type="hidden" name="action" value="importCSV" />
			<input type="hidden" name="token" value="<?php echo $newToken ?>" />

			<input id="CSVFile" name="CSVFile" type="file" required />
			<br/>
			<br/>
			<label for="srcEncoding">
				<?php print $langs->trans('SelectFileEncoding'); ?>
			</label>
			<select id="srcEncoding" name="srcEncoding">
				<?php
				foreach ($acceptedEncodings as $encoding) {
					echo '<option value="' . $encoding . '">' . $encoding . '</option>';
				}
				?>
			</select>
			<br/>
			<br />
			<label for="excludefirstline">

			<?php

				print $langs->trans('startLine');
				print '<input type="number" class="maxwidth50" name="startLine" value="'.$startline.'">' ;
				print $form->textwithpicto("", $langs->trans("SetThisValueTo2ToExcludeFirstLine"));
				print ' - <input type="number" class="maxwidth50" name="endline" value="'.$endline.'">';
				print $form->textwithpicto("", $langs->trans("KeepEmptyToGoToEndOfFile"));
			?>
			</label>

			<br/>
			<hr style="margin-top: 30px;"/>
			<div class="right" >
			<button type="submit" class="button" name="save" value="1" ><span class="fa fa-upload"></span> <?php echo $langs->trans("SubmitCSVForImport") ?></button>
			</div>

		</fieldset>
	</form>
	<?php
}

function _showHelp() {

	global $langs, $importRule;

	$key="csv";
	$param="&datatoimport=discountrules_1";
	?>
	<fieldset>
	<legend><?php print $langs->trans("help"); ?></legend>

		<h4 class="center">
			<?php print img_picto('', 'download', 'class="paddingright opacitymedium"').'<a href="'.dol_buildpath('/discountrules/import_demo/Exemple_de_fichier_import_discountrules_sep_point_vigule.csv',1).'" target="_blank">'.$langs->trans("DownloadEmptyExample").'</a>'; ?>
		</h4>
		<hr/>

		<h4><?php print $langs->trans("TechDescCsvTitle"); ?></h4>
		<ul>
			<li><strong><?php print $langs->trans("NumbersubTitle"); ?></strong>
				<ul>
					<li><?php print $langs->trans("Numbersub-1"); ?></li>
					<li><?php print $langs->trans("Numbersub-2"); ?></li>
					<li><?php print $langs->trans("Numbersub-3"); ?></li>
				</ul>
			</li>
			<li><strong><?php print $langs->trans("EncodeCharsSubTitle"); ?> </strong>
				<br><?php print $langs->trans("EncodeCharsSubTitle-2"); ?>
			</li>
			<li><?php print $langs->trans("FieldSeparatorsubTitle", $importRule->csvSeparator); ?> </li>
			<li><?php print $langs->trans("catSeparatorsubTitle", $importRule->csvCatSeparator); ?> </li>
			<li><?php print $langs->trans("StringSeparatorsubTitle"); ?></li>
		</ul>

		<hr/>

		<h4><?php print $langs->trans("Columns"); ?></h4>
		<table class="idr help-table">
			<tr><th><?php print $langs->trans("label"); ?></th>
				<td><?php print $langs->trans("labelDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("refProject"); ?></th>

				<td><?php print $langs->trans("refProjectDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("refProduct"); ?></th>

				<td><?php print $langs->trans("refProductDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("refCompany"); ?></th>

				<td><?php print $langs->trans("refCompanyDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("priorityRank"); ?></th>

				<td><?php print $langs->trans("priorityRankDesc"); ?> </td>
			</tr>
			<tr><th><?php print $langs->trans("cTypeEnt"); ?></th>

				<td><?php print $langs->trans("cTypeEntDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("allCategoryProduct"); ?></th>

				<td><?php print $langs->trans("allCategoryProductDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("allCategoryCompany"); ?></th>

				<td><?php print $langs->trans("allCategoryCompanyDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("reduction"); ?></th>

				<td><?php print $langs->trans("reductionDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("fromQuantity"); ?></th>

				<td><?php print $langs->trans("fromQuantityDesc"); ?> </td>
			</tr>

			<tr><th><?php print $langs->trans("productPrice"); ?></th>
				<td><?php print $langs->trans("productPriceDesc"); ?> </td>
			</tr>

			<tr>
				<th><?php print $langs->trans("productReductionAmount"); ?></th>
				<td><?php print $langs->trans("productReductionAmountDesc"); ?> </td>
			</tr>
			<tr>
				<th><?php print $langs->trans("dateFrom"); ?></th>
				<td><?php print $langs->trans("dateFromDesc"); ?> </td>
			</tr>
			<tr>
				<th><?php print $langs->trans("dateTo"); ?></th>
				<td><?php print $langs->trans("dateToDesc"); ?> </td>
			</tr>

			<tr>
				<th><?php print $langs->trans("activate"); ?></th>
				<td><?php print $langs->trans("activateDesc"); ?> </td>
			</tr>
		</table>
	</fieldset>
	<?php
}
