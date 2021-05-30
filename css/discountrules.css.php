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
