# CHANGELOG FOR DISCOUNTRULES MODULE

## Not Released

- NEW : Grosse Factorisation du code js - 2.7.0
- NEW : Display/Export customer prices  - 2.6.0
  This new feature add a new page and entry menu in discounts rules menu call "price list".
  
  This page can simulate prices for different kinds of filters and allow you to export results in csv
  
    **Possible improvement :**
  
    - Add conf on setup page to configure default CSV export options
    - Add conf on setup page to configure default behavior on export null prices (currently not exported)
    - Add dialog box on click export button to choose export options

## 2.5.0 - 21/01/2021
- NEW : Add priority rank to rules *12/01/2021* - 2.4.0
- NEW : Discount rules search class *13/01/2021* - 2.5.0
- FIX : Table creation *05/03/2021* - 2.5.1
- FIX : use relave url for interface.php instead of conf.php url *23/03/2021* - 2.5.2

## 2.3.0 - 11/01/2021

- NEW : Add Button to update rules from quotations lines (MAIN_FEATURE_LEVEL 2)
  To finish this feature, on button click, need to show all modifications applied before validation
- FIX : Quantity limitation
- FIX : Security access of card and list

## 2.2.0 - 13/10/2020

- NEW : Règle de prix étendue à la modification de lignes.
   Lors de la modification d'une ligne de devis | propal | facture si une réduction existe celle-ci peut être ajoutée avec un clic sur l'icône de réduction.

## 2.1.0 - 06/08/2020
on  Project reduce rule:

- NEW : Ajout d'un champs de selection de projet dans la création d'une règle de prix.
- NEW : AutoLoading de la réduction liée à un projet si existant. 
- NEW : Affichage de la ref Projet et de son titre dans la tooltips du projet associé à la règle si selectionnée.(mouse over sur  le champs réduction ).  

## 2.0
- NEW : Product price rules feature


## 1.0
Initial version
