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

<div class="margin-form col-lg-offset-3 pm-model" rel="{(isset($model.idx) && $model.idx >= 0 && $model.idx !== '') ? $model.idx : ''}">
    <fieldset>
        <div class="pm-model-header">
            <div>
                <label class="pm-model-header-model-name">{$model.name}</label><span style="color:grey">&nbsp;&nbsp;&gt;&nbsp;&nbsp;</span><span class="pm-model-header-product-type">{$model.model_option}</span>
                <span class="pm-model-action">
                        <img src="{$images_url}cross.png" class="pm-model-action-delete" alt=""/>
                        <img src="{$images_url}edit.png" class="pm-model-action-edit" alt=""/>
                    </span>
            </div>
        </div>
        <div class="pm-model-body" style="display:none">
            <div class="pm-model-item">
                <label class="control-label col-lg-3">{l s='Model Name' mod='priceminister'}</label>&nbsp;&nbsp;
                <div class="margin-form col-lg-9">
                    <input type="text" name="models[{(isset($model.idx) && $model.idx >= 0 && $model.idx !== '') ? $model.idx : '_key_'}][name]" class="model-model-name" value="{if isset($model.name)}{$model.name}{/if}"/><span class="pm-required">*</span>
                </div>
            </div>
            <div class="pm-model-item">
                <label class="control-label col-lg-3">{l s='Product Type' mod='priceminister'}</label>
                <div class="margin-form col-lg-9">
                    <select class="product_type" name="models[{(isset($model.idx) && $model.idx >= 0 && $model.idx !== '') ? $model.idx : '_key_'}][product_type]">
                        <option value=""></option>
                        {foreach from=$models.model_options item=option}
                            <option value="{$option.value}" {if $option.value == $model.model_option}selected{/if}>{$option.desc}</option>
                        {/foreach}
                    </select><span class="pm-required">*</span>
                </div>
            </div>

            <div class="product_type_template_container pm-model-template">
                {$model.product_type_template}
            </div>

            <div class="pm-model-delete">
                <button class="btn btn-default">
                    <img src="{$images_url}cross.png" class="del-model-img" alt=""/>&nbsp;&nbsp; {l s='Delete' mod='priceminister'}
                </button>
            </div>

            <div class="pm-model-minimize">
                <button class="btn btn-default">
                    <img src="{$images_url}minimize.png" class="min-model-img" alt=""/>&nbsp;&nbsp; {l s='Minimize' mod='priceminister'}
                </button>
            </div>
        </div>
    </fieldset>

    <hr style="width: 30%;"/>
</div>