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
if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
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

$translateList = array('Saved', 'errorAjaxCall', 'CategoryNotSelected');

$translate = array();
foreach ($translateList as $key){
	$translate[$key] = $langs->transnoentities($key);
}

$translate['actionClickMeDiscountrule'] = $langs->transnoentities('actionClickMeDiscountrule', '<span class="suggest-discount"></span>');

if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal")  $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
if ($thousand == 'None') $thousand = '';
elseif ($thousand == 'Space') $thousand = ' ';

$confToJs = new stdClass();
$confToJs->MAIN_MAX_DECIMALS_TOT = getDolGlobalInt('MAIN_MAX_DECIMALS_TOT');
$confToJs->MAIN_MAX_DECIMALS_UNIT = getDolGlobalString('MAIN_MAX_DECIMALS_UNIT');

$confToJs->MAIN_LANG_DEFAULT = str_replace('_', '-', $langs->getDefaultLang());
$confToJs->dec = $dec;

/**
 * Petit delai à appliquer à discount rules lors de la mise à jour des inputs de formulaire d'ajout de produit
 * car il peut arriver que Dolibarr mette un peu de temps à répondre et donc le prix de discount rule est écrasé par le standard
 */
$confToJs->DISCOUNTRULE_APPLY_PRICE_DELAY = getDolGlobalInt('DISCOUNTRULE_APPLY_PRICE_DELAY', 300);
$confToJs->thousand = $thousand;

$confToJs->useForcedMod = intval(getDolGlobalInt('DISCOUNTRULES_FORCE_RULES_PRICES') && !$user->hasRight('discountrules','overrideForcedMod'));


// BE CAREFUL : Depending on Dolibarr version, there are 2 kinds of category inputs : single select or multiselect
if(intval(DOL_VERSION) > 10){
	// Use an multiselect field
	$confToJs->catImput = "search_category_product_list";
}

?>

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
    		var catinput = $('#' + DiscountRule.config.catImput);
    		if(catinput != undefined)
    		{
    			if(catinput.val() == ""){
					// set error
					catinput.get(0).setCustomValidity(DiscountRule.langs.CategoryNotSelected); // Apparement le message n'est pas toujours affiché
					DiscountRule.setEventMessage(DiscountRule.langs.CategoryNotSelected, false); // Du coup ajout d'un set event message
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

	$('#' + DiscountRule.config.catImput).change(function() {
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
	o.langs = <?php print json_encode($translate) ?>;
	o.config = <?php print json_encode($confToJs) ?>;
	o.urlInterface = "<?php print dol_buildpath('discountrules/scripts/interface.php',1); ?>";

	o.fetchDiscountOnEditLine = function (element, idLine, idProd,fkCompany,fkProject,fkCountry,date,qtySelector = '#qty', subpriceSelector = '#price_ht', remiseSelector = '#remise_percent', subpriceTTCSelector = '#price_ttc') {


		if (idProd == undefined || $(qtySelector) == undefined) return 0;

		if(o.config.useForcedMod == 1){
			$(subpriceSelector + ', ' + remiseSelector + ', ' + subpriceTTCSelector).prop('readonly', true).addClass('--discount-for-readonly');
		}

		var lastidprod = 0;
		var lastqty = 0;
		var qty = $(qtySelector).val();

		if (idProd != lastidprod || qty != lastqty) {

			lastidprod = idProd;
			lastqty = qty;

			var sendData = {
				'action': "product-discount",
				'qty': qty,
				'id_line': idLine,
				'fk_product': idProd,
				'fk_company': fkCompany,
				'fk_project' : fkProject,
				'fk_country' : fkCountry,
				'date':date
			};


			$.ajax({
				method: "POST",
				url: o.urlInterface,
				dataType: 'json',
				data: sendData,
				success: function (data) {

					var $inputPriceHt = $(subpriceSelector);
					var $inputRemisePercent = $(remiseSelector);

					var discountTooltip = data.tpMsg;

					if(data.result && data.element === "discountrule") {
						$("#suggest-discount").attr('data-discount', data.reduction);
						$("#suggest-discount").attr('data-subprice', data.subprice);

						if(o.config.useForcedMod == 1){
							$(subpriceSelector).val(data.subprice);
							$(remiseSelector).val(data.reduction);
						}

						$("#suggest-discount").removeClass("--disable");
						$("#suggest-discount").addClassReload("--dr-rotate-icon");
					}
					else if(data.result && (data.element === "facture" || data.element === "commande" || data.element === "propal"  )) {
						$("#suggest-discount").attr('data-discount', data.reduction);
						$("#suggest-discount").attr('data-subprice', data.subprice);

						if(o.config.useForcedMod == 1){
							$(subpriceSelector).val(data.subprice);
							$(remiseSelector).val(data.reduction);
						}

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
					DiscountRule.setToolTip($('#suggest-discount'), DiscountRule.langs.actionClickMeDiscountrule + '<br/><br/>' + discountTooltip);

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

	o.priceFormat = function (price){
		var numberFormat = Intl.NumberFormat(DiscountRule.config.MAIN_LANG_DEFAULT);
		var priceFormatter = (price)=>numberFormat.format(price);
		return priceFormatter(price);
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
	o.discountUpdate = function (idprod, fk_company, fk_project, qtySelector = '#qty', subpriceSelector = '#price_ht', remiseSelector = '#remise_percent', defaultCustomerReduction = 0, date='', subpriceTTCSelector = '#price_ttc'){

		if(o.config.useForcedMod == 1){
			if(idprod > 0){
				$(subpriceSelector + ', ' + remiseSelector + ', ' + subpriceTTCSelector).prop('readonly', true).addClass('--discount-for-readonly');
			}else{
				$(subpriceSelector + ', ' + remiseSelector + ', ' + subpriceTTCSelector).prop('readonly', false).removeClass('--discount-for-readonly');
			}
		}


		if(idprod == null || idprod == 0 || $(qtySelector) == undefined ){  return 0; }

		var qty = $(qtySelector).val();
		if(idprod != o.lastidprod || qty != o.lastqty)
		{

			o.lastidprod = idprod;
			o.lastqty = qty;

			$.ajax({
				method: "POST",
				url: o.urlInterface,
				dataType: 'json',
				data: {
					'fk_product': idprod,
					'action': "product-discount",
					'qty': qty,
					'fk_company': fk_company,
					'fk_project' : fk_project,
					'date' : date
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
							$inputPriceHt.val(o.priceFormat(data.subprice));

							// FIX Dolibarr response time : in some case first call of standard price comme after DiscountRules call
							// TODO : ajouter un système de hook js dans Dolibarr pour pouvoir lancer ce type de maj sur un évenement du standard car là
							//  pas moyen de savoir lorsque Dolibarr change l'input
							setTimeout(function() {
								$inputPriceHt.val(o.priceFormat(data.subprice));
							}, o.config.DISCOUNTRULE_APPLY_PRICE_DELAY);

							$inputPriceHt.addClassReload("discount-rule-change --info");
						}
					}
					else if(data.result
						&& (data.element === "facture" || data.element === "commande" || data.element === "propal"  )
					)
					{
						$inputRemisePercent.val(data.reduction);
						$inputRemisePercent.addClassReload("discount-rule-change --info");

						$inputPriceHt.val(o.priceFormat(data.subprice));
						// FIX Dolibarr response time : in some case first call of standard price comme after DiscountRules call
						// TODO : ajouter un système de hook js dans Dolibarr pour pouvoir lancer ce type de maj sur un évenement du standard car là
						//  pas moyen de savoir lorsque Dolibarr change l'input
						setTimeout(function() {
							$inputPriceHt.val(o.priceFormat(data.subprice));
						}, o.config.DISCOUNTRULE_APPLY_PRICE_DELAY);

						$inputPriceHt.addClassReload("discount-rule-change --info");
					}
					else
					{
						if(defaultCustomerReduction>0)
						{
							$inputPriceHt.removeClass("discount-rule-change --info");
							$inputRemisePercent.val(defaultCustomerReduction); // apply default customer reduction from customer
							// FIX Dolibarr response time : in some case first call of standard price comme after DiscountRules call
							// TODO : ajouter un système de hook js dans Dolibarr pour pouvoir lancer ce type de maj sur un évenement du standard car là
							//  pas moyen de savoir lorsque Dolibarr change l'input
							setTimeout(function() {
								$inputRemisePercent.val(defaultCustomerReduction); // apply default customer reduction from customer card
							}, o.config.DISCOUNTRULE_APPLY_PRICE_DELAY);

							$inputRemisePercent.addClass("discount-rule-change --info");
						}
						else
						{
							$inputRemisePercent.val('0');
							// FIX Dolibarr response time : in some case first call of standard price comme after DiscountRules call
							// TODO : ajouter un système de hook js dans Dolibarr pour pouvoir lancer ce type de maj sur un évenement du standard car là
							//  pas moyen de savoir lorsque Dolibarr change l'input
							setTimeout(function() {
								$inputRemisePercent.val('0');
							}, o.config.DISCOUNTRULE_APPLY_PRICE_DELAY);


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
