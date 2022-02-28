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
*
* @package    Mirakl
* @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @author     Tran Pham
* @license    Commercial license
* Support by mail  :  support.mirakl@common-services.com
*}
{if $ps177x}<div class="card mt-2">{/if}
    <form name="meUpdateOrder" id="meUpdateOrder" method="post">
        <script type="text/javascript" src="{$module_url|escape:'htmlall':'UTF-8'}views/js/orders_sheet.js"></script>
        <input type="hidden" name="me-order-id" value="{$mirakl_order_id|escape:'htmlall':'UTF-8'}"/>

        <fieldset class="panel" style="min-width: 400px;margin-top:10px;">
            {if $ps177x}<div class="card-header">{/if}
                {if $ps16x}<h3>{else}<legend>{/if}
                    <img src="{$image_path|escape:'htmlall':'UTF-8'}logo.gif" alt="{l s='Mirakl' mod='mirakl'}" style="vertical-align: middle"/>
                    {l s='Mirakl' mod="mirakl"}
                {if $ps16x}</h3>{else}</legend>{/if}
            {if $ps177x}</div>{/if}

            {if $ps177x}<div class="card-body">{/if}
                <span class="me_label">{l s='Order ID' mod='mirakl'}</span>
                <span class="me_text">
                    <a href="{$order_url|escape:'htmlall':'UTF-8'}" title="{l s='Order ID' mod='mirakl'} {$mirakl_order_id|escape:'htmlall':'UTF-8'}"
                       target="_blank">{$mirakl_order_id|escape:'htmlall':'UTF-8'}</a>
                </span>
                <br/>

                <span class="me_label">{l s='Documents' mod='mirakl'}</span>
                <span class="me_text">
                    <a href="{$module_url|escape:'htmlall':'UTF-8'}functions/dldocuments.php?mkp_order_id={$mirakl_order_id}&selected-mkp={$mirakl_channel}&token={$token}" title="{l s='Order ID' mod='mirakl'} {$mirakl_order_id|escape:'htmlall':'UTF-8'}" target="_blank">
                        {l s='Delivery slip' mod='mirakl'}
                    </a>
                </span>
                <br/>

                {if $pr_id}
                    <span class="me_label">{l s='Relay ID' mod='mirakl'}</span>
                    <span class="me_text">{$pr_id|escape:'htmlall':'UTF-8'}</span>
                    <br/>
                {/if}

                {if is_array($additional_fields) && count($additional_fields)}
                    <hr/>
                    <span class="me_label">{l s='Additional fields' mod='mirakl'}</span>
                    <br/>

                    {foreach $additional_fields as $field}
                        <span class="me_label"><code>{$field.code}: </code></span>
                        <span class="me_text">{$field.value}</span>
                        <br/>
                    {/foreach}
                {/if}
                {if $mirakl_order_latest_ship_date}
                    <span class="me_label">{l s='Latest Ship Date: ' mod='mirakl'}</span>
                    <span class="me_text">{$mirakl_order_latest_ship_date}</span>
                {/if}
            {if $ps177x}</div>{/if}
        </fieldset>
    </form>
{if $ps177x}</div>{/if}
