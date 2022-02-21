{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Common-Services Co., Ltd.
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Common-Services Co., Ltd. is strictly forbidden.
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
 *
 * @package   CDiscount
 * @author    Olivier B., Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<div id="conf-models" class="tabItem">
    <input type="hidden" class="text_must_fill_name" value="{l s='You must fill a model name !' mod='cdiscount'}" />
    <img src="{$images_url|escape:'htmlall':'UTF-8'}loading.gif" alt="{l s='Loading Models' mod='cdiscount'}"
         id="loader_models" style="display:none;margin:20px 48%;" />

    <h2>{l s='Models' mod='cdiscount'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3">&nbsp;</label>

        <div class="margin-form col-lg-9">
            <div class="{$alert_class.info|escape:'htmlall':'UTF-8'}">
                {l s='Please follow our online tutorial' mod='cdiscount'} :<br>
                <a href="http://documentation.common-services.com/cdiscount/configurer-un-modele/?lang={$support_language|escape:'htmlall':'UTF-8'}"
                   target="_blank">http://documentation.common-services.com/cdiscount/configurer-un-modele/</a><br>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Models Configuration' mod='cdiscount'}</label>
        <div class="margin-form col-lg-9">
            <div id="model-add">
                <span class="model-add">{l s='Add a model to the list' mod='cdiscount'}</span>&nbsp;&nbsp;
                <span class="model-add-img"><img src="{$images_url|escape:'htmlall':'UTF-8'}add.png" alt="add" /></span>
            </div>
            <div class="cleaner"></div>
        </div>
    </div>

    {* Master model *}
    {include file="$module_path/views/templates/admin/configure/model/model_master.tpl" universe_options=$cd_models.universe_options}

    <div id="model-items">
        {foreach from=$cd_models.models_data item=moduleModel}
            <div class="model-content">
                {* Model header only *}
                <div class="model-header" id="model-header-{$moduleModel->id|escape:'htmlall':'UTF-8'}">
                    <input type="hidden" class="model_state" name="{'models['|cat:$moduleModel->id:'][state]'}"
                           value="unchanged" />
                    <br />
                    <label class="control-label col-lg-3" style="color:navy">
                        {$moduleModel->name|escape:'htmlall':'UTF-8'}
                    </label>

                    <div class="margin-form col-lg-9">
                        <table class="profile-table">
                            <tr>
                                <td style="width: 90%;">
                                    <span class="type">{$moduleModel->getBreadcrumb()|escape:'htmlall':'UTF-8'}</span>
                                </td>
                                <td>
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png" class="model-del-img"
                                         alt="{l s='Delete' mod='cdiscount'}"
                                         rel="{$moduleModel->id|escape:'htmlall':'UTF-8'}" />
                                </td>
                                <td>
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}edit.png" class="model-edit-img"
                                         alt="{l s='Edit' mod='cdiscount'}"
                                         rel="{$moduleModel->id|escape:'htmlall':'UTF-8'}" />
                                </td>
                                <td>
                                    <img src="{$images_url|escape:'htmlall':'UTF-8'}green-loader.png"
                                         class="model-refresh-img" alt="{l s='Refresh' mod='cdiscount'}"
                                         rel="{$moduleModel->id|escape:'htmlall':'UTF-8'}" />
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
    <br>
    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
