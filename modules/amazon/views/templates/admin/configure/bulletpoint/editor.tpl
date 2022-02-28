{**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from Feed.biz
 * Use, copy, modification or distribution of this source file without written
 * license agreement from Feed.biz is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe Feed.biz.
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la Common-Services Co. Ltd. est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter Common-Services Co., Ltd. a l'adresse: contact@common-services.com
 *
 * @package   Amazon Market Place
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

{if $bulletpoint.existing_bullet_point}
    <script>
        existing_bullet_point = '{$bulletpoint.existing_bullet_point|escape:'quotes':'UTF-8'}';
    </script>
{/if}
<script type="text/javascript"
        src="{$bulletpoint.url|escape:'quotes':'UTF-8'}views/js/amazon-bulletpoint.js?version={$bulletpoint.version|escape:'htmlall':'UTF-8'}"></script>
<link href="{$bulletpoint.url|escape:'quotes':'UTF-8'}views/css/bulletpoint_box.css" rel="stylesheet" type="text/css"/>

<div id="bulletpoint-overlay"></div>
<div id="bulletpoint-box" class="bulletpoint-box">
    <div class="main-box">
        <input type="hidden" name="bullet_point_id" value="{$bulletpoint.id|escape:'quotes':'UTF-8'}"/>

        <div class="main-box-content">
            <span class="close-box">[ x ]</span>

            <div>
                <label class="title">{l s='Bullet Point Generator' mod='amazon'}</label>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Informations' mod='amazon'}</label>

                <div class="margin-form col-lg-9">
                    <div id="bpe-fields">
                        {foreach from=$bulletpoint.fields key=field_key item=field_name}
                            <span class="bpe-item bpe-entity bpe-item-field"
                                  rel="bpe-x-{$field_key|escape:'htmlall':'UTF-8'}-0">{$field_name|escape:'htmlall':'UTF-8'}
                                <a href="#">x</a></span>
                        {/foreach}
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Attributes' mod='amazon'}</label>

                <div class="margin-form col-lg-9">
                    <div id="bpe-attributes">
                        {foreach from=$bulletpoint.attributes key=id_attribute_group item=attribute}
                            <span class="bpe-item bpe-entity bpe-item-attribute"
                                  rel="bpe-a-n-{$id_attribute_group|escape:'htmlall':'UTF-8'}">{$attribute.name|escape:'htmlall':'UTF-8'}
                                <a href="#">x</a></span>
                            <span class="bpe-item bpe-entity bpe-item-attribute"
                                  rel="bpe-a-nv-{$id_attribute_group|escape:'htmlall':'UTF-8'}">{$attribute.name|escape:'htmlall':'UTF-8'}
                                : {$attribute.any_value|escape:'htmlall':'UTF-8'}<a href="#">x</a></span>
                            <span class="bpe-item bpe-entity bpe-item-attribute"
                                  rel="bpe-a-v-{$id_attribute_group|escape:'htmlall':'UTF-8'}">{$attribute.any_value|escape:'htmlall':'UTF-8'}
                                <a href="#">x</a></span>
                        {/foreach}
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Features' mod='amazon'}</label>

                <div class="margin-form col-lg-9">
                    <div id="bpe-features">
                        {foreach from=$bulletpoint.features key=id_feature item=feature}
                            <span class="bpe-item bpe-entity bpe-item-feature"
                                  rel="bpe-f-n-{$id_feature|escape:'htmlall':'UTF-8'}">{$feature.name|escape:'htmlall':'UTF-8'}
                                <a href="#">x</a></span>
                            <span class="bpe-item bpe-entity bpe-item-feature"
                                  rel="bpe-f-nv-{$id_feature|escape:'htmlall':'UTF-8'}">{$feature.name|escape:'htmlall':'UTF-8'}
                                : {$feature.any_value|escape:'htmlall':'UTF-8'}<a href="#">x</a></span>
                            <span class="bpe-item bpe-entity bpe-item-feature"
                                  rel="bpe-f-v-{$id_feature|escape:'htmlall':'UTF-8'}">{$feature.any_value|escape:'htmlall':'UTF-8'}
                                <a href="#">x</a></span>
                        {/foreach}
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Text' mod='amazon'}</label>

                <div class="margin-form col-lg-9">
                    <div id="bpe-text">
                        <span class="bpe-input bpe-entity" rel="bpe-i-i-0"><input type="text"
                                                                                  placeholder="{l s='Personalize your bullet point' mod='amazon'}"/><a
                                    href="#">x</a></span>
                    </div>
                </div>
            </div>

            <div style="display:none;">
                <p class="drop-message" id="drop-message"><span>{l s='Drop your items here' mod='amazon'}</span></p>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3" style="color:grey">{l s='Recipient' mod='amazon'}</label>

                <div class="margin-form col-lg-9">

                    <div id="bpe-recipient-wrap">
                        <div id="bpe-recipient">

                        </div>
                    </div>

                </div>
            </div>

            <div class="form-group bpe-sample-section">
                <label class="control-label col-lg-3" style="color:grey">{l s='Sample Result' mod='amazon'}</label>

                <div class="margin-form col-lg-9">
                    <pre id="bpe-sample"></pre>
                </div>
            </div>

            <!--
            <button class="button btn btn-defaul bulletpoint-serialize" style="float:right;" class="mapping-values-insert">{l s='Serialize' mod='amazon'}</button>
            <button class="button btn btn-defaul bulletpoint-clear" style="float:right;" class="mapping-values-insert">{l s='Clear' mod='amazon'}</button>
            <button class="button btn btn-defaul bulletpoint-unserialize" style="float:right;" class="mapping-values-insert">{l s='Unserialize' mod='amazon'}</button>
            -->

            <button class="button btn bulletpoint-use" class="mapping-values-use">{l s='Save' mod='amazon'}</button>
        </div>
    </div>
</div><!--mapping box-->