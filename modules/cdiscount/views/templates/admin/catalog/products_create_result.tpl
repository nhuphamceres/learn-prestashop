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
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<h3 style="text-align:center;margin:10px 0 0 0; border: none;">{l s='Send File' mod='cdiscount'}</h3>
<!-- <h4 style="text-align:center;margin:0;font-size:0.8em">%s: %s (%s)</h4>', $this->l('Last File'), $this->zipfile, $date); -->
<hr style="width:60%;margin-top:5px;">

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Generated On' mod='cdiscount'}</label>

    <div class="margin-form col-lg-9" style="font-size:1.0em;font-weight: bold; color:green">
        {$date|escape:'htmlall':'UTF-8'}
    </div>
</div>

<div class="form-group">
    <label class="control-label col-lg-3">{l s='Last Package' mod='cdiscount'}</label>

    <div class="margin-form col-lg-9">
        <div style="text-align:center;width:200px;">
            <a href="{$zip_url|escape:'htmlall':'UTF-8'}{$zipfile|escape:'htmlall':'UTF-8'}" target="_blank"
               title="{$zipfile|escape:'htmlall':'UTF-8'}">
                <img src="{$images|escape:'htmlall':'UTF-8'}file_extension_zip.png"
                     alt="{l s='Download' mod='cdiscount'}"/><br/>
                {$zipfile|escape:'htmlall':'UTF-8'}
            </a>
        </div>
    </div>
</div>


<input type="button" id="send-products" value="{l s='Send to CDiscount' mod='cdiscount'}" class="button btn"/>

<div style="clear:both">&nbsp;<br></div>
<div id="send-products-result" style="display:none;"></div>
<div id="send-products-debug" style="display: none;">
    <label class="dropup" style="cursor: pointer;" onclick="$(this).toggleClass('dropup').toggleClass('dropdown'); $('#send-products-debug').find('div').slideToggle();">
        Sent debug
        <span class="caret"></span>
    </label>
    <div style="display: none;"></div>
</div>
