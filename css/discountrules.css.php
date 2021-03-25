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
 */

/**
 * \file    css/discountrules.css.php
 * \ingroup discountrules
 * \brief   CSS file for module discountrules.
 */

session_cache_limiter(FALSE);

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

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

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


// Load user to have $user->conf loaded (not done by default here because of NOLOGIN constant defined) and load permission if we need to use them in CSS
/*if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
    $user->fetch('',$_SESSION['dol_login']);
	$user->getrights();
}*/


// Define css type
header('Content-type: text/css');
// Important: Following code is to cache this file to avoid page request by browser at each Dolibarr page access.
// You can use CTRL+F5 to refresh your browser cache.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>
/* <style type="text/css" > */
.discount-rule-change, input.flat.discount-rule-change, input.discount-rule-change{
	outline: 1px solid rgba(0,0,0,.1);
}

.discount-rule-change.--info, input.flat.discount-rule-change.--info, input.discount-rule-change.--info{
	outline: 1px solid rgba(0, 142, 255, 0.2);

	-webkit-animation:animate-discount-rule-change-info 1s ease-in-out;
	-moz-animation:animate-discount-rule-change-info 1s ease-in-out;
	animation:animate-discount-rule-change-info 1s ease-in-out;
}

@keyframes animate-discount-rule-change-info {
	20% { outline-color: rgba(0, 142, 255, 0); }
	40% { outline-color: rgba(0, 142, 255, 1); }
	60% { outline-color: rgba(0, 142, 255, 0); }
	80% { outline-color: rgba(0, 142, 255, 1); }
	100% { outline-color: rgba(0, 142, 255, 0); }
}

.discount-rule-change.--warning, input.flat.discount-rule-change.--warning, input.discount-rule-change.--warning{
	outline: 1px solid rgba(255, 0, 205, 0.2);

	-webkit-animation:animate-discount-rule-change-warning 1s ease-in-out;
	-moz-animation:animate-discount-rule-change-warning 1s ease-in-out;
	animation:animate-discount-rule-change-warning 1s ease-in-out;
}


@keyframes animate-discount-rule-change-warning {
	20% { outline-color: rgba(255, 0, 205, 0); }
	40% { outline-color: rgba(255, 0, 205, 1); }
	60% { outline-color: rgba(255, 0, 205, 0); }
	80% { outline-color: rgba(255, 0, 205, 1); }
	100% { outline-color: rgba(255, 0, 205, 0); }
}

.suggest-discount{
	position: relative;
	display: inline-block;
	width: 16px;
	height: 16px;
	cursor: pointer;
	background: url("../img/discount.svg") no-repeat center;
}

.suggest-discount.--disable{
	display: none;
}

.suggest-discount.--dr-rotate-icon{
	-webkit-animation:dr-shake .6s linear;
	-moz-animation:dr-shake .6s linear;
	animation:dr-shake .6s linear;
}

@keyframes dr-shake {
	0% { transform: translate(1px, 1px) rotate(0deg); }
	10% { transform: translate(-1px, -2px) rotate(-1deg); }
	20% { transform: translate(-3px, 0px) rotate(1deg); }
	30% { transform: translate(3px, 2px) rotate(0deg); }
	40% { transform: translate(1px, -1px) rotate(1deg); }
	50% { transform: translate(-1px, 2px) rotate(-1deg); }
	60% { transform: translate(-3px, 1px) rotate(0deg); }
	70% { transform: translate(3px, 1px) rotate(-1deg); }
	80% { transform: translate(-1px, -1px) rotate(1deg); }
	90% { transform: translate(1px, 2px) rotate(0deg); }
	100% { transform: translate(1px, -2px) rotate(-1deg); }
}

#product-search-dialog-button{
	margin-bottom: 3px;
	margin-top: 3px;
	margin-left: 5px;
	margin-right: 5px;
	font-family: roboto,arial,tahoma,verdana,helvetica;
	display: inline-block;
	padding: 5px 7px;
	text-align: center;
	cursor: pointer;
	text-decoration: none !important;
	background-color: #f5f5f5;
	background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
	background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
	background-repeat: repeat-x;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
	border: 1px solid #aaa;
	-webkit-border-radius: 2px;
	border-radius: 1px;
	font-weight: bold;
	text-transform: uppercase;
	color: #444;
}

/*
* SEARCH
*/

.discountrules-global-search-container{
	min-width: 70%;
	margin: 1em 0 0.4em 0;
	text-align: center;
}

.discountrules-global-search-input {
	width: 100%;
	padding: 10px 35px 10px 20px;

	background-color: transparent;
	font-size: 14px;
	line-height: 16px;
	box-sizing: border-box;


	color: #575756;
	background-color: transparent;
	background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'%3E%3Cpath d='M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z' fill='%23b3b3b3'/%3E%3Cpath d='M0 0h24v24H0z' fill='none'/%3E%3C/svg%3E%0A");
	background-repeat: no-repeat;
	background-size: 16px 16px;
	background-position: 95% center;
	background-position: calc(100% - 16px) center;
	border-radius: 50px;
	border: 1px solid #c4c4c2 !important;
	transition: all 250ms ease-in-out;
	backface-visibility: hidden;
	transform-style: preserve-3d;
	font-style: oblique;
	max-width: 600px;

}

.discountrules-global-search-input::placeholder {
	color: #575756cc;
	letter-spacing: 1.5px;
}

.discountrules-productsearch__results-count{
	font-size: 0.9em;
	color: #686868;
}

.discount-prod-list-input-reduction{
	max-width: 40px;
}

.discount-prod-list-input-qty{
	max-width: 64px;
}

.discount-prod-list-input-subprice{
	max-width: 80px;
	padding-right: 2px;
}

.discount-search-product-row.--data:hover .discount-search-product-col{
	background-color: var(--colorbacklinepairhover); /*rgba(251,255,0,0.15);*/
}

.default-hidden, .discount-search-product-row:hover .default-visible{
	display:none;
}

.discount-search-product-row:hover .default-hidden, .default-visible{
	display:block;
}

.discount-prod-list-action-btn{
	cursor: pointer;
	margin-left: 10px;
}
.discount-prod-list-action-btn:hover{
	color: #0b419b;
}
.discount-prod-list-action-btn:focus{
	color: #0b419b;
}

.discount-search-product-col.--action,
.discount-search-product-col.--buy-price{
	padding: 2px 8px 2px 8px;
	min-height: 22px;
	min-width: 100px;
}

.search-list-select{
	max-width: 175px;
}

.advance-search-product-results{
	position: relative;
	border-collapse: collapse;
}

.discount-search-product-row.--title th, .discount-search-product-row.--title td{
	position: -webkit-sticky;
	position: sticky;
	background: var(--colorbacktitle1);
	z-index: 1;
	top: 0; /* Don't forget this, required for the stickiness */
}

.--ajax-loading .--blur-on-loading {
	-webkit-filter: blur(1px);
	filter: blur(1px);
	cursor: wait; /* Add a pointer on hover */
}

.inner-dialog-overlay{
	position: absolute;
	width: 100%;
	height: 100%;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: rgba(255,255,255,0.1); /* background with some opacity to be sure under elements are not clikables  */
	z-index: 2; /* Specify a stack order in case you're using a different order for other elements */
}


/* LOADING EFFECT */
.dialog-loading__loading {
	-webkit-animation:dialog-loading-fadein 2s;
	-moz-animation:dialog-loading-fadein 2s;
	-o-animation:dialog-loading-fadein 2s;
	animation:dialog-loading-fadein 2s;
}
@-moz-keyframes dialog-loading-fadein {
	from {opacity:0}
	to {opacity:1}
}
@-webkit-keyframes dialog-loading-fadein {
	from {opacity:0}
	to {opacity:1}
}
@-o-keyframes dialog-loading-fadein {
	from {opacity:0}
	to {opacity:1}
}
@keyframes dialog-loading-fadein {
	from {opacity:0}
	to {opacity:1}
}

.dialog-loading__spinner-wrapper {
	min-width:100%;
	min-height:100%;
	height:100%;
	top:0;
	left:0;
	position:absolute;
	z-index:300;
}

.dialog-loading__spinner-text {position:absolute;top:41.5%;left:47%;margin:16px 0 0 35px;font-size:9px;font-family:Arial;color: #808080;letter-spacing:1px;font-weight:700}
.dialog-loading__spinner {
	margin:0;
	display:block;
	position:absolute;
	left:45%;
	top:40%;
	border:25px solid rgba(100,100,100,0.5);
	width:1px;
	height:1px;
	border-left-color:transparent;
	border-right-color:transparent;
	-webkit-border-radius:50px;
	-moz-border-radius:50px;
	border-radius:50px;
	-webkit-animation:dialog-loading-spin 1.5s infinite;
	-moz-animation:dialog-loading-spin 1.5s infinite;
	animation:dialog-loading-spin 1.5s infinite;
}

@-webkit-keyframes dialog-loading-spin {
	0%,100% {-webkit-transform:rotate(0deg) scale(1)}
	50%     {-webkit-transform:rotate(720deg) scale(0.6)}
}

@-moz-keyframes dialog-loading-spin  {
	0%,100% {-moz-transform:rotate(0deg) scale(1)}
	50%     {-moz-transform:rotate(720deg) scale(0.6)}
}
@-o-keyframes dialog-loading-spin  {
	0%,100% {-o-transform:rotate(0deg) scale(1)}
	50%     {-o-transform:rotate(720deg) scale(0.6)}
}
@keyframes dialog-loading-spin  {
	0%,100% {transform:rotate(0deg) scale(1)}
	50%     {transform:rotate(720deg) scale(0.6)}
}
