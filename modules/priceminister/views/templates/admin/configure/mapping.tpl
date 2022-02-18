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

<div id="conf-mappings" class="tabItem panel {$selected_tab_mappings|escape:'htmlall':'UTF-8'}" {if (empty($selected_tab_mappings))}style="display:none;"{/if}>
    <h3>
        {l s='Mappings' mod='priceminister'}
    </h3>

    <div class="form-group">
        <label class="control-label col-lg-3" style="color:grey">{l s='Attributes Mapping' mod='priceminister'}</label>
        <div class="margin-form col-lg-9">
                    <span id="attribute-mapping-collapse-group">
                        <a href="javascript:void(0)">[ + ]&nbsp;&nbsp; {l s='Show' mod='priceminister'}</a>
                        <a href="javascript:void(0)" style="display:none;">[ - ]&nbsp;&nbsp; {l s='Hide' mod='priceminister'}</a>
                    </span>
        </div>
    </div>

    <div id="attribute-mapping">
        {if isset($mapping.attributes) && isset($mapping.attributes.attribute_groups) && count($mapping.attributes.attribute_groups) > 0}
            {foreach from=$mapping.attributes.attribute_groups key=id_attr_saved item=attribute_group_saved}
                {assign var=mappings_pm_group value=$mapping.attributes.config[$id_attr_saved]}
                {foreach from=$mappings_pm_group key=pm_group item=mapping_group}
                    <div>
                        <div class="form-group">
                            <label for="attribute-group-{$id_attr_saved}-{$pm_group}" class="control-label col-lg-3" style="color:navy">
                                <strong>{$mapping_group.name}</strong>
                            </label>
                            <span class="attribute-mapping-collapse" id="attribute-mapping-collapse-{$id_attr_saved}-{$pm_group}">
                                        <a href="javascript:void(0)" rel="{$id_attr_saved}-{$pm_group}">[ + ]&nbsp;&nbsp;{l s='Show' mod='priceminister'}</a>
                                        <a href="javascript:void(0)" rel="{$id_attr_saved}-{$pm_group}" style="display:none;">[ - ]&nbsp;&nbsp;{l s='Hide' mod='priceminister'}</a>
                                    </span>
                        </div>
                        <div class="attribute-mapping form-group" id="attribute-mapping-{$id_attr_saved}-{$pm_group}">
                            <div class="form-group">
                                <div class="margin-form col-lg-9 mapping-type">
                                    <input type="hidden" name="attributes_mapping_types[{$id_attr_saved}][{$pm_group}]" value="{$pm_group}" class="attribute_mapping_type" style="width:250px" readonly="readonly"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="margin-form col-lg-offset-3 mapping-margin">
                                    {foreach from=$mapping_group.left key=index item=mapping_item}
                                        <div id="attribute-group-{$id_attr_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" class="attribute-group">
                                            <select name="mapping[prestashop][{$id_attr_saved}][{$pm_group}][]" rel="{$id_attr_saved}-{$pm_group}" onchange="javascript:return selectAttributeChange(this);"/>
                                            <option value="" class="blank"></option>
                                            {foreach from=$mapping_item key=id_attribute item=attribute_selector}
                                                <option value="{$id_attribute|escape:'htmlall':'UTF-8'}" {if $attribute_selector.selected}selected{/if} {if $attribute_selector.used}class="used-option"{else}class="unused-option"{/if}>{$attribute_selector.name|escape:'htmlall':'UTF-8'}</option>
                                            {/foreach}
                                            </select>
                                            <span style="position:relative;top:-2px">&nbsp;&nbsp;
                                                        <img src="{$mapping.images_url|escape:'htmlall':'UTF-8'}next.png" alt=""/>
                                                        &nbsp;&nbsp;
                                                    </span>
                                            <div id="attribute-group-right-{$id_attr_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" class="attribute-group inline-element">
                                                <select name="mapping[priceminister][{$id_attr_saved}][{$pm_group}][]" rel="mapping" class="inline-element"/>
                                                <option value="" class="blank"></option>
                                                {foreach from=$mapping.attributes.config[$id_attr_saved][$pm_group]['right'][$index] key=id_attribute_right item=attribute_selector_right}
                                                    <option value="{$id_attribute_right|escape:'htmlall':'UTF-8'}"
                                                            {if $attribute_selector_right.selected}selected{/if}>
                                                        {$attribute_selector_right.name|escape:'htmlall':'UTF-8'}
                                                    </option>
                                                {/foreach}
                                                </select>
                                            </div>
                                            <span class="add-attr-mapping" id="button-add-{$id_attr_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" {if $index}style="display:none;"{/if}><img src="{$mapping.images_url|escape:'htmlall':'UTF-8'}plus.png" alt="{l s='Add a new mapping' mod='priceminister'}"/></span>
                                            <span class="del-attr-mapping" id="button-del-{$id_attr_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" {if ! $index}style="display:none;"{/if}><img src="{$mapping.images_url|escape:'htmlall':'UTF-8'}minus.png" alt="{l s='Remove mapping' mod='priceminister'}"/></span>
                                        </div>
                                    {/foreach}
                                    <div id="new-mapping-{$id_attr_saved}-{$pm_group}" rel="{$mappings_pm_group[$pm_group]['items']|escape:'htmlall':'UTF-8'}"></div>
                                </div>
                            </div>
                            {if isset($mapping.attributes.config[$id_attr_saved][$pm_group]['matching'])}
                                <div class="form-group">
                                    <div class="margin-form col-lg-offset-3 mapping-margin">
                                        <span style="color:darkgrey">{l s='Those values have been automatically matched' mod='priceminister'}
                                            : </span><span style="color:darkgreen">{$mapping.attributes.config[$id_attr_saved][$pm_group]['matching']|escape:'htmlall':'UTF-8'}</span>
                                    </div>
                                </div>
                            {/if}
                        </div>
                        <br/>
                    </div>
                {/foreach}
                <!-- attribute-mapping -->
            {/foreach}
        {/if}
    </div><!-- attribute mapping -->

    <div class="form-group">
        <label class="control-label col-lg-3" style="color:grey">{l s='Features Mapping' mod='priceminister'}</label>
        <div class="margin-form col-lg-9">
                    <span id="feature-mapping-collapse-group">
                        <a href="javascript:void(0)">[ + ]&nbsp;&nbsp; {l s='Show' mod='priceminister'}</a>
                        <a href="javascript:void(0)" style="display:none;">[ - ]&nbsp;&nbsp; {l s='Hide' mod='priceminister'}</a>
                    </span>
        </div>
    </div>

    <div id="feature-mapping">
        {if isset($mapping.feature) && isset($mapping.feature.feature_groups) && count($mapping.feature.feature_groups) > 0}

            {foreach from=$mapping.feature.feature_groups key=id_feature_saved item=feature_group}
                {assign var=mappings value=$mapping.feature.config[$id_feature_saved]}
                {foreach from=$mappings key=pm_group item=mapping_group}
                    <div>
                        <div class="form-group">
                            <label for="feature-group-{$id_feature_saved}-{$pm_group}" class="control-label col-lg-3" style="color:navy">
                                <strong>{$mapping_group.name}</strong>
                            </label>
                            <span class="feature-mapping-collapse" id="feature-mapping-collapse-{$id_feature_saved}-{$pm_group}">
                                        <a href="javascript:void(0)" rel="{$id_feature_saved}-{$pm_group}">[ + ]&nbsp;&nbsp;{l s='Show' mod='priceminister'}</a>
                                        <a href="javascript:void(0)" rel="{$id_feature_saved}-{$pm_group}" style="display:none;">[ - ]&nbsp;&nbsp;{l s='Hide' mod='priceminister'}</a>
                                    </span>
                        </div>
                        <div class="feature-mapping form-group" id="feature-mapping-{$id_feature_saved}-{$pm_group}">
                            <div class="form-group">
                                <div class="margin-form col-lg-9 mapping-type">
                                    <input type="hidden" name="features_mapping_types[{$id_feature_saved}][{$pm_group}]" value="{$pm_group}" class="feature_mapping_type" readonly="readonly"/>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="margin-form col-lg-offset-3">
                                    {foreach from=$mapping_group.left key=index item=mapping_item}
                                        <div id="feature-group-{$id_feature_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" class="feature-group">
                                            <select type="text" name="features_mapping[prestashop][{$id_feature_saved}][{$pm_group}][]" rel="{$id_feature_saved}-{$pm_group}" onchange="javascript:return selectFeatureChange(this);"/>
                                            <option value="" class="blank"></option>
                                            {foreach from=$mapping_item key=id_feature_value item=feature_selector}
                                                <option value="{$id_feature_value|escape:'htmlall':'UTF-8'}" {if $feature_selector.selected}selected{/if} {if $feature_selector.used}class="used-option"{else}class="unused-option"{/if}>{$feature_selector.name|escape:'htmlall':'UTF-8'}</option>
                                            {/foreach}
                                            </select>
                                            <span>
                                                        <img src="{$mapping.images_url}next.png" alt=""/>
                                                    </span>

                                            <div id="feature-group-right-{$id_feature_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" class="feature-group inline-element">
                                                {if is_array($mapping.feature.config[$id_feature_saved][$pm_group]['right'][$index]) && count($mapping.feature.config[$id_feature_saved][$pm_group]['right'][$index])}
                                                    <select name="features_mapping[priceminister][{$id_feature_saved}][{$pm_group}][]" rel="mapping" style="width:250px"/>
                                                    <option value="" class="blank"></option>
                                                    {foreach from=$mapping.feature.config[$id_feature_saved][$pm_group]['right'][$index] key=id_feature_right item=feature_selector_right}
                                                        <option value="{$id_feature_right}"
                                                                {if $feature_selector_right.selected}selected{/if}>
                                                            {$feature_selector_right.name}
                                                        </option>
                                                    {/foreach}
                                                    </select>
                                                {else}
                                                    <input type="text" name="features_mapping[priceminister][{$id_feature_saved}][{$pm_group}][]"
                                                           {if !is_array($mapping.feature.config[$id_feature_saved][$pm_group]['right'][$index])}value="{$mapping.feature.config[$id_feature_saved][$pm_group]['right'][$index]|escape:'htmlall':'UTF-8'}"{/if}
                                                           rel="mapping" style="width:250px"/>
                                                {/if}
                                            </div>
                                            <span class="add-feature-mapping" id="button-add-feature-{$id_feature_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" {if $index}style="display:none;"{/if}><img src="{$mapping.images_url|escape:'htmlall':'UTF-8'}plus.png" alt="{l s='Add a new mapping' mod='priceminister'}"/></span>
                                            <span class="del-feature-mapping" id="button-del-feature-{$id_feature_saved}-{$pm_group}-{$index|escape:'htmlall':'UTF-8'}" {if ! $index}style="display:none;"{/if}><img src="{$mapping.images_url|escape:'htmlall':'UTF-8'}minus.png" alt="{l s='Remove mapping' mod='priceminister'}"/></span>
                                        </div>
                                    {/foreach}
                                    <div id="new-feature-mapping-{$id_feature_saved}-{$pm_group}" rel="{$mappings[$pm_group]['items']|escape:'htmlall':'UTF-8'}"></div>
                                </div>
                            </div>
                        </div>
                        <br/>
                    </div>
                {/foreach}
                <!-- feature-mapping -->
            {/foreach}

        {/if}

    </div><!-- feature mapping -->


    <hr style="width:30%; margin-bottom: 25px;"/>

    <div class="cleaner"></div>
    {include file="$module_path/views/templates/admin/configure/validate.tpl"}
</div>
