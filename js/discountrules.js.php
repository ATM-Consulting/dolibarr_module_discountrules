<?php
/* Copyright (C) 2018 John BOTELLA
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
 *
 * Library javascript to enable Browser notifications
 */

//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))        define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');


/**
 * \file    js/discountrules.js.php
 * \ingroup discountrules
 * \brief   JavaScript file for module discountrules.
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/../main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/../main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules@discountrules","other"));

$translateList = array('Saved', 'errorAjaxCall');

$translate = array();
foreach ($translateList as $key){
	$translate[$key] = $langs->transnoentities($key);
}

if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
if ($thousand == 'None') $thousand = '';
elseif ($thousand == 'Space') $thousand = ' ';

$confToJs = array(
	'MAIN_MAX_DECIMALS_TOT' => $conf->global->MAIN_MAX_DECIMALS_TOT,
	'MAIN_MAX_DECIMALS_UNIT' => $conf->global->MAIN_MAX_DECIMALS_UNIT,
	'dec' => $dec,
	'thousand' => $thousand,
);


// BE CAREFUL : Depending on Dolibarr version, there are 2 kinds of category inputs : single select or multiselect
if(intval(DOL_VERSION) > 10){
	// Use an multiselect field
	$catImput = "search_category_product_list";
}
else{
	// Use an single select field
	$catImput = "select_categ_search_categ";
}

?>
/* <script > */
// LANGS
var discountlang = <?php print json_encode($translate) ?>;
var discountConfig = <?php print json_encode($confToJs) ?>;
var discountDialogCountAddedProduct = 0;

/* Javascript library of module discountrules */
$( document ).ready(function() {



	/***************************************************************/
	/* Lors de la modification de ligne ajout du bouton de remise  */
	/***************************************************************/
	$('#remise_percent').parent().append('<span class="suggest-discount --disable" id="suggest-discount"></span>');


	/*****************************************************/
	/* Pour les actions en masse sur liste des produits  */
	/*****************************************************/
	var discountRulesCheckSelectCat = true;

    $("[name='massaction']").change(function() {

    	if($(this).val() == 'addtocategory' || $(this).val() == 'removefromcategory' )
    	{
    		var catinput = $('#<?php echo $catImput; ?>');
    		if(catinput != undefined)
    		{
    			if(catinput.val() == ""){
					// set error
					catinput.get(0).setCustomValidity('<?php print $langs->transnoentitiesnoconv('CategoryNotSelected'); ?>');
					discountRulesCheckSelectCat = false;
				}
    		}
    	}
    	else
    	{
			// reset error
			$(this).get(0).setCustomValidity('');
			discountRulesCheckSelectCat = true;
    	}
    });

	$('#<?php echo $catImput; ?>').change(function() {
		if(!discountRulesCheckSelectCat && $(this).val() != "")
		{
			// reset error
			$(this).get(0).setCustomValidity('');
			discountRulesCheckSelectCat = true;
		}
	});
});


/*******************/
/* LES LIBRAIRIES  */
/*******************/


/**
 * permet de faire un addClass qui reload les animations si la class était déjà là
 */
(function ( $ ) {
	$.fn.addClassReload = function(className) {
		return this.each(function() {
			var $element = $(this);
			// Do something to each element here.
			$element.removeClass(className).width;
			setTimeout(function(){ $element.addClass(className); }, 0);
		});
	};
}( jQuery ));

// Utilisation d'une sorte de namespace en JS à appeler comme ça : DiscountRule.nomDeLaFonction()
var DiscountRule = {};
(function(o) {

	o.discountlang = <?php print json_encode($translate) ?>;
	o.advanceProductSearchConfig = <?php print json_encode($confToJs) ?>;

	o.fetchDiscountOnEditLine = function (element, idLine, idProd,fkCompany,fkProject,fkCountry) {

		if (idProd == undefined || $('#qty') == undefined) return 0;


		var lastidprod = 0;
		var lastqty = 0;
		var qty = $('#qty').val();

		if (idProd != lastidprod || qty != lastqty) {

			lastidprod = idProd;
			lastqty = qty;

			var urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php', 1); ?>";
			var sendData = {
				'action': "product-discount",
				'qty': qty,
				'id_line': idLine,
				'fk_product': idProd,
				'fk_company': fkCompany,
				'fk_project' : fkProject,
				'fk_country' : fkCountry
			};


			$.ajax({
				method: "POST",
				url: urlInterface,
				dataType: 'json',
				data: sendData,
				success: function (data) {


					var $inputPriceHt = $('#price_ht');
					var $inputRemisePercent = $('#remise_percent');

					var discountTooltip = data.tpMsg;

					if(data.result && data.element === "discountrule") {
						$("#suggest-discount").attr('data-discount', data.reduction);
						$("#suggest-discount").attr('data-subprice', data.subprice);

						$("#suggest-discount").removeClass("--disable");
						$("#suggest-discount").addClassReload("--dr-rotate-icon");
					}
					else if(data.result && (data.element === "facture" || data.element === "commande" || data.element === "propal"  )) {
						$("#suggest-discount").attr('data-discount', data.reduction);
						$("#suggest-discount").attr('data-subprice', data.subprice);

						$("#suggest-discount").removeClass("--disable");
						$("#suggest-discount").addClassReload("--dr-rotate-icon");
					}
					else
					{ // pas de discounRule

						$("#suggest-discount").attr('data-discount', $inputRemisePercent.val());
						$("#suggest-discount").attr('data-subprice', $inputPriceHt.val());

						//if(defaultCustomerReduction > 0) {
						//	$("#suggest-discount").attr('data-discount', defaultCustomerReduction).removeClass("--disable --dr-rotate-icon");
						//} else {
							$("#suggest-discount").addClass("--disable").removeClass("--dr-rotate-icon");
						//}
					}

					// add tooltip message
					DiscountRule.setToolTip($('#suggest-discount'),"<?php print dol_escape_js($langs->transnoentities('actionClickMeDiscountrule', '<span class="suggest-discount"></span>')); ?><br/><br/>"+ discountTooltip);

					// Show tootip
					if(data.result){
						$("#suggest-discount").tooltip("open");//  to explicitly show it here
						setTimeout(function() {
							$("#suggest-discount").tooltip("close" );
						}, 2000);
					}
				},
				error: function (err) {

				}
			});
		}

	} // FormmUpdateLine

	/**
	 * affectation du contenu dans l'attribut title
	 *
	 * @param $element
	 * @param text
	 */
	o.setToolTip = function ($element, text){
		$element.attr("title",text);
		o.initToolTip($element);
	}


	/**
	 * initialisation de la tootip
	 * @param element
	 */
	o.initToolTip = function (element){

		if(!element.data("tooltipset")){
			element.data("tooltipset", true);
			element.tooltip({
				show: { collision: "flipfit", effect:"toggle", delay:50 },
				hide: { delay: 50 },
				tooltipClass: "mytooltip",
				content: function () {
					return $(this).prop("title");		/* To force to get title as is */
				}
			});
		}
	}


	o.setEventMessage = function (msg, status = true){

		if(msg.length > 0){
			if(status){
				$.jnotify(msg, 'notice', {timeout: 5},{ remove: function (){} } );
			}
			else{
				$.jnotify(msg, 'error', {timeout: 0, type: 'error'},{ remove: function (){} } );
			}
		}
		else{
			$.jnotify('ErrorMessageEmpty', 'error', {timeout: 0, type: 'error'},{ remove: function (){} } );
		}
	}

	/**
	 * this function is used for addline form and discount quick search form
	 * cette fonction était à l'origine dans le fichier action_discountrules.class.php
	 * placée ici pour factorisation du code
	 *
	 * MISE EN GARDE : Les modules suivants utlisent cette methode donc faites attention si vous modifiez les paramètres
	 * 				->	module_advancedproductsearch
	 *
	 * @param int idprod
	 * @param int fk_company
	 * @param int fk_project
	 * @param string qtySelector
	 * @param string subpriceSelector
	 * @param string remiseSelector
	 * @param float defaultCustomerReduction
	 * @returns {number}
	 */

	o.lastidprod = 0;
	o.lastqty = 0;
	o.discountUpdate = function (idprod, fk_company, fk_project, qtySelector = '#qty', subpriceSelector = '#price_ht', remiseSelector = '#remise_percent', defaultCustomerReduction = 0){

		if(idprod == null || idprod == 0 || $(qtySelector) == undefined ){  return 0; }

		var qty = $(qtySelector).val();
		if(idprod != o.lastidprod || qty != o.lastqty)
		{

			o.lastidprod = idprod;
			o.lastqty = qty;

			var urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php',1); ?>";

			$.ajax({
				method: "POST",
				url: urlInterface,
				dataType: 'json',
				data: {
					'fk_product': idprod,
					'action': "product-discount",
					'qty': qty,
					'fk_company': fk_company,
					'fk_project' : fk_project,
				}
			})
				.done(function( data ) {
					var $inputPriceHt = $(subpriceSelector);
					var $inputRemisePercent = $(remiseSelector);
					var discountTooltip = data.tpMsg;


					if(data.result && data.element === "discountrule")
					{
						$inputRemisePercent.val(data.reduction);
						$inputRemisePercent.addClassReload("discount-rule-change --info");

						if(data.subprice > 0){
							// application du prix de base
							$inputPriceHt.val(data.subprice);
							$inputPriceHt.addClassReload("discount-rule-change --info");
						}
					}
					else if(data.result
						&& (data.element === "facture" || data.element === "commande" || data.element === "propal"  )
					)
					{
						$inputRemisePercent.val(data.reduction);
						$inputRemisePercent.addClassReload("discount-rule-change --info");
						$inputPriceHt.val(data.subprice);
						$inputPriceHt.addClassReload("discount-rule-change --info");
					}
					else
					{
						if(defaultCustomerReduction>0)
						{
							$inputPriceHt.removeClass("discount-rule-change --info");
							$inputRemisePercent.val(defaultCustomerReduction); // apply default customer reduction from customer card
							$inputRemisePercent.addClass("discount-rule-change --info");
						}
						else
						{
							$inputRemisePercent.val('');
							$inputPriceHt.removeClass("discount-rule-change --info");
							$inputRemisePercent.removeClass("discount-rule-change --info");
						}
					}

					// add tooltip message
					$inputRemisePercent.attr("title", discountTooltip);
					$inputPriceHt.attr("title", discountTooltip);

					// add tooltip
					if(!$inputRemisePercent.data("tooltipset")){
						$inputRemisePercent.data("tooltipset", true);
						$inputRemisePercent.tooltip({
							show: { collision: "flipfit", effect:"toggle", delay:50 },
							hide: { delay: 50 },
							tooltipClass: "mytooltip",
							content: function () {
								return $(this).prop("title");		/* To force to get title as is */
							}
						});
					}

					if(!$inputPriceHt.data("tooltipset")){
						$inputPriceHt.data("tooltipset", true);
						$inputPriceHt.tooltip({
							show: { collision: "flipfit", effect:"toggle", delay:50 },
							hide: { delay: 50 },
							tooltipClass: "mytooltip",
							content: function () {
								return $(this).prop("title");		/* To force to get title as is */
							}
						});
					}

					// Show tootip
					if(data.result){

						// TODO : ajouter une vérification des inputs avant et apres application des remises car si rien n'a changé alors ne pas forcement faire pop la tooltip

						$inputRemisePercent.tooltip().tooltip( "open" ); //  to explicitly show it here
						setTimeout(function() {
							$inputRemisePercent.tooltip().tooltip("close" );
						}, 2000);
					}
				});
		}
	}

})(DiscountRule);
