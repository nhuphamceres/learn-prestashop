{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 *}

<script type="text/javascript" src="{$module_url}views/js/priceminister.js?version={$version|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript" src="{$module_url}views/js/priceminister_models.js?version={$version|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript" src="{$module_url}views/js/priceminister_profiles.js?version={$version|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript" src="{$module_url}views/js/priceminister_repricing.js?version={$version|escape:'htmlall':'UTF-8'}"></script>
<script type="text/javascript" src="{$module_url}views/js/jquery.validate.js"></script>


<link rel="stylesheet" type="text/css" href="{$module_url}views/css/priceminister.css"/>
<link rel="stylesheet" type="text/css" href="{$module_url}views/css/profiles.css"/>
<link rel="stylesheet" type="text/css" href="{$module_url}views/css/models.css"/>
<link rel="stylesheet" type="text/css" href="{$module_url}views/css/repricing.css"/>

{if $ps16x}
    <link rel="stylesheet" type="text/css" href="{$module_url}views/css/priceminister_ps16.css">
{else}
    <link rel="stylesheet" type="text/css" href="{$module_url}views/css/chosen.min.css"/>
    <script type="text/javascript" src="{$module_url}views/js/chosen.jquery.min.js"></script>
{/if}

<input type="hidden" id="cronjobs_url" value="{$cronjobs_url}"/>

<div class="alert alert-danger" id="validation_error" style="display:none;">
    <strong>{l s='Validation Error' mod='priceminister'}</strong><br></div>

<a href="http://www.common-services.com" target="_blank" class="col-lg-6">
    <img src="{$images_url|escape:'none'}common-services_48px.png" alt="{l s='Common-Services' mod='priceminister'}" style="float: left; padding-top: 40px;"/>
</a>
<a href="https://fr.shopping.rakuten.com/" target="_blank" class="lg-col-6">
    <img src="{$images_url|escape:'none'}logo.svg" alt="{l s='RakutenFrance' mod='priceminister'}" class="logo-wide"/>
</a>
<div style="clear:both"></div>
<br/>
<br/>
