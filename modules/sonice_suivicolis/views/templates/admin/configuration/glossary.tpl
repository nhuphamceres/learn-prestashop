{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Common-Services Co., Ltd.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 * ...........................................................................
 *
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 *}

<div id="glossary" style="display: none;">
    <div class="login">
        {l s='This is your 6 caracters user number, provided by La Poste Colissimo at the opening of your seller account.' mod='sonice_suivicolis'}<br>
        <br>
        {l s='You have to subscribe a So Colissimo Flexibilite contract with Coliposte to receive your login and password in order to access this service.' mod='sonice_suivicolis'}<br>
        <br>
        <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}/glossary/login.png">
    </div>
    <div class="pwd">
        {l s='Password linked to your credentials upper.' mod='sonice_suivicolis'}<br>
        {l s='It must be the same as the one in your customer area.' mod='sonice_suivicolis'}<br>
        <br>
        {l s='You have to subscribe a So Colissimo Flexibilite contract with Coliposte to receive your login and password in order to access this service.' mod='sonice_suivicolis'}<br>
    </div>
    <div class="debug_mode">
        {l s='Enable traces for debugging and developpment purpose.' mod='sonice_suivicolis'}<br>
        <b {if isset($snsc_config.debug) && $snsc_config.debug}style="color: red;"{/if}>{l s='In exploitation this option must not be active !' mod='sonice_suivicolis'}</b>
    </div>
    <div class="test_mode">
        {l s='This is a demonstration or developpment mode, API calls are fakes.' mod='sonice_suivicolis'}<br>
        {l s='Use for developpment purpose only or for tests and validate the module under this environment.' mod='sonice_suivicolis'}<br>
        <b {if isset($snsc_config.demo) && $snsc_config.demo}style="color: red;"{/if}>{l s='In exploitation this option must not be active !' mod='sonice_suivicolis'}</b>
    </div>
    <div class="carriers_filter">
        {l s='This tool allows you to select your Colissimo carriers with which retrieve orders to generate labels.' mod='sonice_suivicolis'}<br>
        {l s='Select a carrier on the right side and push it on the left side to select it.' mod='sonice_suivicolis'}
    </div>
    <div class="auto_update">
        {l s='Once activated the parcel tracking will automatically be updated after fiew secondes in your order sheet.' mod='sonice_suivicolis'}
    </div>
    <div class="order_until">
        {l s='Determine until how far the module as to follow your orders.' mod='sonice_suivicolis'}
        <u>{l s='Note' mod='sonice_suivicolis'} :</u> {l s='It is recommended to use a short period for small PHP configuration.' mod='sonice_suivicolis'}
    </div>
    <div class="expeditor">
        {l s='In case of import from Expeditor, apply this status to orders.' mod='sonice_suivicolis'}
    </div>
    <div class="employee_cron">
        {l s='This employee will be used in CRON task as the employee who modified data and sent email.' mod='sonice_suivicolis'}
    </div>
    <div class="filter_payment">
        {l s='Orders with this payment method will not be tracked.' mod='sonice_suivicolis'}
    </div>
    <div class="filter_status">
        {l s='Orders with this order status method will not be tracked.' mod='sonice_suivicolis'}
    </div>
</div>