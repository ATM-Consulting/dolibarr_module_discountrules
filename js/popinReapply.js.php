<?php
/* Copyright (C) 2025 ATM Consulting
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

//if (!defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (!defined('NOCSRFCHECK'))    define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN')) define('NOLOGIN', 1);
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', 1);
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');


/**
 * \file    js/advancedproductsearch.js.php
 * \ingroup advancedproductsearch
 * \brief   JavaScript file for module advancedproductsearch.
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];$tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/../main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/../main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Define js type
header('Content-Type: application/javascript');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

global $langs, $conf;

// Load traductions files requiredby by page
$langs->loadLangs(array("discountrules@discountrules", "other"));


// LANGS : seront utilisable en js avec
$translateList = array('Saved', 'errorAjaxCall','priceReapply', 'productDescriptionReapply', 'Apply', 'Cancel', 'UpdateProduct');

$translate = array();
foreach ($translateList as $key) {
	$translate[$key] = $langs->transnoentities($key);
}

if ($langs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal") $dec = $langs->transnoentitiesnoconv("SeparatorDecimal");
if ($langs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") $thousand = $langs->transnoentitiesnoconv("SeparatorThousand");
if ($thousand == 'None') $thousand = '';
elseif ($thousand == 'Space') $thousand = ' ';

$confToJs = array(
	'MAIN_MAX_DECIMALS_TOT' => getDolGlobalInt('MAIN_MAX_DECIMALS_TOT'),
	'MAIN_MAX_DECIMALS_UNIT' => getDolGlobalString('MAIN_MAX_DECIMALS_UNIT'),
	'dec' => $dec,
	'thousand' => $thousand,
	'urlToInterface' => dol_buildpath('/discountrules/scripts/interface.php', 1)
);

?>
/* <script > */

// Checkbox
$(document).ready(function() {
	$(document).on("click", '#document-lines-load-dialog-box .linecheckboxtoggle', function (event) {
		var checkBoxes = $("#document-lines-load-dialog-box .linecheckbox");
		checkBoxes.prop("checked", this.checked);
	})
});

//Accordion toggle for description
$(document).ready(function() {
	$(document).on("click", '[data-accordion-target]', function (event) {  // data-accordion-target -> class <div>
		let target = $(this).attr('data-accordion-target');

		var container = $(this).closest( ".dr-accordion-container" );      // container = <div> contenant la class dr-accordion-container. closet séléctionne le parent le plus proche
		if(container.hasClass('--open')){                                  // Si container ( qui est égale à la div contenant la class dr-accordion-container ) à une class --open
			$('#'+target).slideUp();                                       // ajout de la fonction slideUp pour l'attribut data-accordion-target
			container.addClass('--closed');                                // ajout de la class --closed
			container.removeClass('--open');                               // suppression de la class --open
		}
		else{
			$('#'+target).slideDown();                                     // ajout de la fonction slideDown pour la target
			container.addClass('--open');                                  // ajout de la class --open
			container.removeClass('--closed');                             // suppression de la class --closed
		}
	})
});

$(document).ready(function() {
	$(document).on("click", 'span[data-accordion-target-current]', function (event) {
		let targetCurrent = $(this).attr('data-accordion-target-current');
		$('#'+targetCurrent).slideToggle();
	})
});

// DIALOG BOX

	$(document).on("click", '#discount-rules-reapply-all', function (event) {
		event.preventDefault();
		if(!$(this).hasClass('butActionRefused')) {

			var element = $(this).attr('data-target-element');
			var fk_element = $(this).attr('data-target-id');
			var documentUrl = $(this).attr('data-document-url');

			var productLoadDialogBox = "document-lines-load-dialog-box";
			// Create layer to convert popup
			$('body').append('<div id="' + productLoadDialogBox + '" title="' + reapplyDiscount.langs.UpdateProduct + '"></div>');

			// Layer to popup
			var popup = $('#' + productLoadDialogBox).dialog({
				autoOpen: true,
				modal: true,
				resizable: false,
				width: Math.min($(window).width() - 50, 1700),
				height: Math.min($(window).height() - 50, 800),
				dialogClass: 'discountrule-product-search-box',
				buttons: [
					{
						text: reapplyDiscount.langs.Apply,
						"class": 'ui-state-information',
						"type": 'submit',
						"id": 'apply-button',
						click: function () {
							$("#reapply-form").submit();
						}
					},
					{
						text: reapplyDiscount.langs.Cancel,
						"class": 'ui-state-information',
						click: function () {
							$(this).dialog("close");
							$('#' + productLoadDialogBox).remove();
						}
					}
				],
				close: function (event, ui) {
					$('#' + productLoadDialogBox).remove();
					if (reapplyDiscount.dialogCountAddedProduct > 0) {
						// si une ligne a été ajoutée, recharge la page actuelle
						document.location.reload();
					}
				},
				open: function (event, ui) {
					reapplyDiscount.discountLoadProductDialogForm(documentUrl, element, fk_element);
					$('#' + productLoadDialogBox).parent().css('z-index', 1002);
					$('.ui-widget-overlay').css('z-index', 1001);
					//Enabled/disabled Apply button
					$("#apply-button").addClass(reapplyDiscount.classForDisabledBtn);
				}
			});
		}
	});



var reapplyDiscount = {};
(function (o) {

	o.lastidprod = 0;
	o.lastqty = 0;

	o.langs = <?php print json_encode($translate) ?>;
	o.config = <?php print json_encode($confToJs) ?>;
	o.dialogCountAddedProduct = 0;

	o.classForDisabledBtn = "ui-button-disabled ui-state-disabled";

	/**
	 * Load reapply discount dialog form
	 */
	o.discountLoadProductDialogForm = function (documentUrl, element = '', fk_element = '') {
		var discountrulesDocumentLinesMassActionsUpdateDialogBox = "document-lines-load-dialog-box";
		var formReapply = $('<form action="" id="reapply-form" method="post"></form>');
		var divReapply = $('<div id="divReapply"></div>');

		$('#' + discountrulesDocumentLinesMassActionsUpdateDialogBox).addClass('--ajax-loading');

		$('#' + discountrulesDocumentLinesMassActionsUpdateDialogBox).append(formReapply);

		formReapply.append(divReapply);

		// Display all invoice products lines
		$.ajax({
			method: "POST",
			url: o.config.urlToInterface,
			dataType: 'json',
			data: {
				'action': "display-documents-lines",
				'element': element,
				'fk_element' : fk_element
			},
			success: function (data) {
				if(data.result) {
					// do stuff on success

					divReapply.html(data.html);

					o.initToolTip($('#divReapply .classfortooltip')); // restore tooltip after ajax call


					// Check all checkboxes at once
					$(".linecheckboxtoggle").first().change(function () {
						if ($(".linecheckboxtoggle").is(':checked')) {
							$(".linecheckbox").prop('checked', true).trigger( "change" );
						} else {
							$(".linecheckbox").prop('checked', false).trigger( "change" );
						}
					});

					//Enabled/disabled Apply button on click
					$("input.checkbox-reapply, .linecheckbox").change(function () {
						if (($("input.checkbox-reapply").is(':checked')) && ($(".linecheckbox").is(':checked'))) {
							$("#apply-button").removeClass(o.classForDisabledBtn);
						} else {
							$("#apply-button").addClass(o.classForDisabledBtn);
						}
					});

					// Enable/disable Apply Button when popin is open
					var formReady = false;
					if($(".checkbox-reapply:checked") != undefined && $(".linecheckbox") != undefined && $(".linecheckbox:checked").prop("checked")){
						formReady = true;
					}

					if(formReady){
						$("#apply-button").removeClass(o.classForDisabledBtn);
					}else {
						$("#apply-button").addClass(o.classForDisabledBtn);
					}

				}
				else {
					// do stuff on error
					o.setEventMessage(data.msg, false);
				}
			},
			error: function (err) {
				o.setEventMessage(o.langs.errorAjaxCall, false);
			}
		});
	}


	/**
	 * affectation du contenu dans l'attribut title
	 *
	 * @param $element
	 * @param text
	 */
	o.setToolTip = function ($element, text) {
		$element.attr("title", text);
		o.initToolTip($element);
	}


	/**
	 * initialisation de la tootip
	 * @param element
	 */
	o.initToolTip = function (element) {

		if (!element.data("tooltipset")) {
			element.data("tooltipset", true);
			element.tooltip({
				show: {collision: "flipfit", effect: "toggle", delay: 50},
				hide: {delay: 50},
				tooltipClass: "mytooltip",
				content: function () {
					return $(this).prop("title");		/* To force to get title as is */
				}
			});
		}
	}


	o.setEventMessage = function (msg, status = true) {

		if (msg.length > 0) {
			if (status) {
				$.jnotify(msg, 'notice', {timeout: 5}, {
					remove: function () {
					}
				});
			} else {
				$.jnotify(msg, 'error', {timeout: 0, type: 'error'}, {
					remove: function () {
					}
				});
			}
		} else {
			$.jnotify('NoProductService', 'error', {timeout: 0, type: 'error'}, {
				remove: function () {
				}
			});
		}
	}

})(reapplyDiscount);
