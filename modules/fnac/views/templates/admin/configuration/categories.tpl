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
* @author    Alexandre D. & Olivier B.
* @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
* @license   Commercial license
* Support by mail  :  contact@common-services.com
*}

<ps-form-group label="{l s='Categories' mod='fnac'}">
    <table cellspacing="0" cellpadding="0" class="table">
        <tr>
            <th><input type="checkbox" name="checkme" class="noborder"/></th>
            <th>{l s='ID' mod='fnac'}</th>
            <th style="width: 400px">{l s='Name' mod='fnac'}</th>
            <th>{l s='Shipping Category' mod='fnac'}</th>
        </tr>
        {if isset($fnac_categories) && isset($fnac_categories.categories_html)}
            {$fnac_categories.categories_html|escape:'quotes':'UTF-8'}
        {/if}
    </table>
</ps-form-group>

<select class="logistic_type_id_template" style="display: none;">
    <option value="">{l s='Default' mod='fnac'} / {l s='None' mod='fnac'}</option>
    {* Base categories *}
    <option style="color: blue;" disabled>{l s='Base Categories' mod='fnac'}</option>
    <option value="101">{l s='Category A' mod='fnac'}</option>
    <option value="102">{l s='Category B' mod='fnac'}</option>
    <option value="103">{l s='Category C' mod='fnac'}</option>
    <option value="104">{l s='Category D' mod='fnac'}</option>
    <option value="105">{l s='Category E' mod='fnac'}</option>
    <option value="106">{l s='Category F' mod='fnac'}</option>
    <option value="107">{l s='Category G' mod='fnac'}</option>
    <option value="108">{l s='Category H' mod='fnac'}</option>
    <option value="109">{l s='Category I' mod='fnac'}</option>
    <option value="110">{l s='Category J' mod='fnac'}</option>
    <option value="111">{l s='Category K' mod='fnac'}</option>
    {* Custom categories *}
    <option style="color: blue;" disabled>{l s='Custom Categories' mod='fnac'}</option>
    <option value="201">{l s='Custom Category #1 ' mod='fnac'}</option>
    <option value="202">{l s='Custom Category #2' mod='fnac'}</option>
    <option value="203">{l s='Custom Category #3' mod='fnac'}</option>
    <option value="204">{l s='Custom Category #4' mod='fnac'}</option>
    <option value="205">{l s='Custom Category #5' mod='fnac'}</option>
    {* Qualified sellers only *}
    <option style="color: blue;" disabled>{l s='Large Products Categories' mod='fnac'}</option>
    <option value="206">{l s='Large Products Category #1 (qualified sellers only)' mod='fnac'}</option>
    <option value="207">{l s='Large Products Category #2 (qualified sellers only)' mod='fnac'}</option>
    <option value="208">{l s='Large Products Category #3 (qualified sellers only)' mod='fnac'}</option>
    <option value="209">{l s='Large Products Category #4 (qualified sellers only)' mod='fnac'}</option>
    <option value="210">{l s='Large Products Category #5 (qualified sellers only)' mod='fnac'}</option>
</select>

