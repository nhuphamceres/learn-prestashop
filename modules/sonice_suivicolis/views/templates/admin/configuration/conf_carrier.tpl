{* NOTICE OF LICENSE
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
 * ...........................................................................
 * @package    SO COLISSIMO
 * @copyright  Copyright(c) 2010-2013 S.A.R.L S.M.C - http://www.common-services.com
 * @author     Olivier B. / Debusschere A.
 * @license    Commercial license
 * Contact by Email :  olivier@common-services.com
 * Contact on Prestashop forum : delete (Olivier B)
 * Skype : delete13_fr (please prefer email)
 *}

<div id="conf-carrier" class="no_max_width" style="display: none;">
    <h2>{l s='Carriers' mod='sonice_suivicolis'}</h2>

    <div class="form-group">
        <label class="control-label col-lg-3" rel="carriers_filter">{l s='Carrier Filters' mod='sonice_suivicolis'}</label>
        <div class="margin-form snsc_typo_conftab col-lg-9">
            <div class="snsc_multi_select_heading">
                <span><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}cross.png" alt="Excluded" /></span>  
                <span><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}tick.png" alt="Included" /></span>
            </div>
            <br>

            <select class="snsc_multi_select float-left" id="available-carriers" style="margin-left: 10px;" multiple>
                <option value="0" disabled style="color:red;">{l s='Excluded Carriers' mod='sonice_suivicolis'}</option>
                {foreach $snsc_carriers.available as $carrier}
                    <option value="{$carrier['id_carrier']|escape:'htmlall':'UTF-8'}">{$carrier['name']|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
            <div class="snsc_sep float-left">
                <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}next.png" class="snsc_move" id="carrier-snsc_move-right" alt="Right" /><br /><br />
                <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}previous.png" class="snsc_move" id="carrier-snsc_move-left" alt="Left" />
            </div>
            <select name="filtered_carriers[]" class="snsc_multi_select float-left" id="filtered-carriers" multiple>
                <option value="0" disabled style="color: #4F8A10;">{l s='Included Carriers' mod='sonice_suivicolis'}</option>
                {foreach $snsc_carriers.filtered as $carrier}
                    <option value="{$carrier['id_carrier']|escape:'htmlall':'UTF-8'}">{$carrier['name']|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
        </div>
    </div>

    {include file="$snsc_module_path/views/templates/admin/configuration/validate.tpl"}
</div>