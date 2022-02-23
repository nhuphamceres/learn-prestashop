{**
* NOTICE OF LICENSE
*
* This source file is subject to a commercial license from Common-Services Co., Ltd.
* Use, copy, modification or distribution of this source file without written
* license agreement from the SARL SMC is strictly forbidden.
* In order to obtain a license, please contact us: support.mondialrelay@common-services.com
* ...........................................................................
* INFORMATION SUR LA LICENCE D'UTILISATION
*
* L'utilisation de ce fichier source est soumise a une licence commerciale
* concedee par la societe Common-Services Co., Ltd.
* Toute utilisation, reproduction, modification ou distribution du present
* fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
* expressement interdite.
* Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: support.mondialrelay@common-services.com
* ...........................................................................
*
* @package   sonice_suivicolis
* @author    debuss-a
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  support.sonice_suivicolis@common-services.com
*}

<a href="http://blog.common-services.com/" target="_blank"><img src="{$snsc_img_dir}cs_logo.png" alt="cs_logo" class="snsc_logo"></a>
<div class="clearfix">
    <br><br>
</div>

<input type="hidden" id="snsc_checklogin_url" value="{$snsc_checklogin_url|escape:'htmlall':'UTF-8'}">
<input type="hidden" id="snsc_cron_task_url" value="{$snsc_cron_task_url|escape:'htmlall':'UTF-8'}">

<form id="configuration" class="defaultForm snsc form-horizontal" action="{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}" autocomplete="off" method="post" enctype="multipart/form-data">
    <ps-tabs position="left">

        <ps-tab label="{$module_name}" id="sonice" panel="false" icon="icon-AdminParentModules">
            {include file="./conf_sonice.tpl"}
        </ps-tab>

        <ps-tab label="{l s='Informations' mod='sonice_suivicolis'}" id="informations" icon="icon-question">
            {include file="./conf_informations.tpl"}
        </ps-tab>

        <ps-tab label="{l s='Credentials' mod='sonice_suivicolis'}" active="true" id="credentials" icon="icon-key">
            {include file="./conf_login.tpl"}
            <ps-panel-footer>
                <ps-panel-footer-submit title="{l s='Save' mod='sonice_suivicolis'}" icon="process-icon-save" direction="right" name="submitPanel"></ps-panel-footer-submit>
            </ps-panel-footer>
        </ps-tab>

        <ps-tab label="{l s='Carriers' mod='sonice_suivicolis'}" id="carriers" icon="icon-truck">
            // content 3
        </ps-tab>
        <ps-tab label="{l s='Mapping' mod='sonice_suivicolis'}" id="mapping" icon="icon-exchange">
            // content 3
        </ps-tab>
        <ps-tab label="{l s='Emails' mod='sonice_suivicolis'}" id="emails" icon="icon-envelope">
            // content 3
        </ps-tab>
        <ps-tab label="{l s='Parameters' mod='sonice_suivicolis'}" id="parameters" icon="icon-gear">
            // content 3
        </ps-tab>
        <ps-tab label="{l s='Filters' mod='sonice_suivicolis'}" id="filters" icon="icon-filter">
            // content 3
        </ps-tab>
        <ps-tab label="{l s='Cronjobs' mod='sonice_suivicolis'}" id="cronjobs" icon="icon-certificate">
            // content 3
        </ps-tab>

    </ps-tabs>
</form>