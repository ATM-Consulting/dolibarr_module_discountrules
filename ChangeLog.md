# CHANGELOG FOR DISCOUNTRULES MODULE




## Not Released
- NEW : COMPAT V22 - *08/07/2025* - 2.26.0

## RELEASE 2.25
- FIX : Mark/Margin minimum rate feature did not work properly need to add condition to check there is an empty value - *08/07/2025* - 2.25.2
- FIX : Mark/Margin minimum rate feature did not work with the module quickcustomerprice - *03/06/2025* - 2.25.1
- NEW : Add Conf DISCOUNTRULES_MARKUP_MARGIN_RATE & DISCOUNTRULES_MINIMUM_RATE - *19/05/2025* - 2.25.0

## RELEASE 2.24
- FIX : fatal on round function  with null parameters - *20/02/2025* - 2.24.2  
- FIX : COMPAT V21 - **06/01/2025** - 2.24.1
- NEW : compatibility V20, changed Dolibarr compatibility range 16.0 to 20.0 - *22/07/2024* - 2.24.0

## RELEASE 2.23
- FIX : Compat. v19 - selectForForms doesn't get module files properly - *23/04/2024* - 2.23.5
- FIX : PHP Error : Attempt to assign property "tpMsg" on int in  - *20/03/2024* - 2.23.4
- FIX : Fatal error : Call to undefined function getCountry() - *20/03/2024* - 2.23.3
- FIX DA024604 : Dolibarr ajax call delay override DiscountRule Ajax call - *18/03/2024* - 2.23.2
  You can use conf DISCOUNTRULE_APPLY_PRICE_DELAY to override default Delay in milliseconds (default 300)
- FIX : set to zero le reduction default id no default reduction setted  - *04/03/2024* - 2.23.1  
- NEW : Changed Dolibarr compatibility range to 15 min - 19 max - *30/11/2023* - 2.23
	Changed PHP compatibility range to 7.0 min - 8.2 max

##  RELEASE 2.22
- NEW : Apply default price - *09/10/2023* - 2.22.0
- NEW : TakePos module compatibility - *23/08/2023* - 2.21.0

##  RELEASE 2.20
- FIX : Missing inclusion of catégorie class - *21/08/2023* - 2.20.2
- FIX : Compatibility V18, public $ismultientitymanaged - *09/06/2023* - 2.20.1
- NEW : Extra fields for discounts rules - *06/02/2023* - 2.20.0

##  RELEASE 2.19
- FIX : Changement de l'accessibilité de l'attribut ismultientitymanaged dans la class discountrule - *24/10/2023* - 2.19.4
- FIX : lorsque la langue par défaut de Dolibarr était en "auto", on récupérait la valeur "auto" au lieu de récupérer la valeur par défaut du navigateur - erreur lors l'ajout d'un produit avec une réduction fixe  - *30/05/2023* - 2.19.4
- FIX : Compatibilité v17 Extrafields attribute - *02/02/2023* - 2.19.2
- FIX : Set des heures de dates de début de remise à 0 pour que ces remises soient prisent en compte dès le premier jour - *15/12/2022* - 2.19.1
- NEW : Add field "product_price" in discount rules list - *07/09/2022* - 2.19.0

##  RELEASE 2.18
- FIX : PHP 8  - *04/08/2022* - 2.18.5  
- FIX : UPDATE TRIGGER TO MODIFY  - *08/06/2022* - 2.18.4  
- FIX : V16 TOKEN - *08/06/2022* - 2.18.3
- FIX : US price format for discounts - *05/05/2022* - 2.18.2
- FIX : Import CSV date format with d/m/Y (Exemple : 25/01/2022) *14/04/2022* - 2.18.1
- FIX : SQL migration file 2.17 => 2.18 to set all_category_project field in llx_discountrule default value to 1 - *13/04/2022* - 2.18.0


##  RELEASE 2.17
- FIX : Multi Module Hook compatibility - *06/05/2022)* - 2.17.4
- FIX : set minwidth on cat_company selectArray to min 300  - *07/04/2022)* - 2.17.3  
- FIX : Remove std import menu, keep discount rule import menu - *30/03/2022* - 2.17.2
- FIX : CSRF token protection - *17/03/2022* - 2.17.1
- FIX : css and js files doesn't need to be PHP file (need module reload) - *05/02/2022* - 2.17.0
- NEW : Add project category filter for rules *28/12/2021* - 2.16.0

##  RELEASE 2.15
- FIX : Image && design fail : refonte page d'import - *11/02/2022* - 2.15.2
- FIX : Wrong parameter for multiprice - *02/02/2022* - 2.15.1
- NEW : Import des règles de prix  *12/01/2022* - 2.15.0

##  RELEASE 2.14
- FIX : Missing product name when add a rule for a product *08/01/2022* - 2.14.3
- FIX : Display field for product price *08/01/2022* - 2.14.2
- FIX : Popin compare country field in description if activated *05/01/2022* - 2.14.1
- NEW : Last price search in document configuration *12/12/2021* - 2.14.0  
  The search in document feature finds the last price applied to a customer  
  A conf enables the module to search the best price instead of the last price

##  RELEASE 2.13
- NEW : Popin display changes before apply *13/10/2021* - 2.13.0
- NEW : Add mass action for lines on document *30/07/2021* - 2.12.0

##  RELEASE 2.11
- FIX : Prise en compte de la date du document comme date référence pour la recherche des règles de remise + ajout de conf pour rétro cohérence comportementale *09/11/2021* - 2.11.2
- FIX : afficher les colonnes "Prix Ht à appliquer" et "Remise fixe" dans la liste des règles de remise sur la fiche Tiers et sur la fiche Produit *23/09/2021* - 2.11.1
- NEW : un onglet “Règles de prix catalogue” sera ajouté sur les fiches tiers. Cet onglet proposera un tableau des règles de remises applicables à ce client selon qu’elles s’appliquent directement à lui ou à un attribut qu’il possède (catégorie, type de tiers, pays ou projet). *23/08/2021* - 2.11.0
- NEW : Search result return now also current product price and default customer reduction *29/07/2021* - 2.10.0

##  RELEASE 2.9
- FIX : type ent save error *28/07/2021* - 2.9.2
- FIX : Minor v14 compatibility fixes *12/07/2021* - 2.9.1
- NEW : New Dolibarr V14 check module update compatibility and add dynamic about page loader  *02/07/2021* - 2.9.0
- NEW : Add product filter to rules list *15/06/2021* - 2.8.0

##  RELEASE 2.7 - 01/06/2021
- FIX : Priority rank between document and discount rules  *18/07/2021* - 2.7.3
- FIX : Price list default selection for fields *15/06/2021* - 2.7.2
- NEW : Grosse Factorisation du code js - 2.7.0
- FIX : Compatibility V13 - Add token renewal - *18/05/2021* - 2.6.3
- NEW : Display/Export customer prices  - 2.6.0

  This new feature add a new page and entry menu in discounts rules menu call "price list".
  
  This page can simulate prices for different kinds of filters and allow you to export results in csv
  
    **Possible improvement :**
  
    - Add conf on setup page to configure default CSV export options
    - Add conf on setup page to configure default behavior on export null prices (currently not exported)
    - Add dialog box on click export button to choose export options

##  RELEASE 2.5.0 - 21/01/2021
- NEW : Add priority rank to rules *12/01/2021* - 2.4.0
- NEW : Discount rules search class *13/01/2021* - 2.5.0
- FIX : Table creation *05/03/2021* - 2.5.1
- FIX : use relave url for interface.php instead of conf.php url *23/03/2021* - 2.5.2
- FIX : UpdatelinebySelf method  change object->product_type to line->product_type *17/09/2021* - 2.5.3
- 
##  RELEASE 2.3.0 - 11/01/2021
- NEW : Add Button to update rules from quotations lines (MAIN_FEATURE_LEVEL 2)
  To finish this feature, on button click, need to show all modifications applied before validation
- FIX : Quantity limitation
- FIX : Security access of card and list

##  RELEASE 2.2.0 - 13/10/2020
- NEW : Règle de prix étendue à la modification de lignes.
   Lors de la modification d'une ligne de devis | propal | facture si une réduction existe celle-ci peut être ajoutée avec un clic sur l'icône de réduction.

##  RELEASE 2.1.0 - 06/08/2020
on  Project reduce rule:
- NEW : Ajout d'un champs de selection de projet dans la création d'une règle de prix.
- NEW : AutoLoading de la réduction liée à un projet si existant. 
- NEW : Affichage de la ref Projet et de son titre dans la tooltips du projet associé à la règle si selectionnée.(mouse over sur  le champs réduction ).  

##  RELEASE 2.0
- NEW : Product price rules feature

##  RELEASE 1.0
Initial version
