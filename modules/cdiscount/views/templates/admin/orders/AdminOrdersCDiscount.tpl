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

<div id="tabList" class="panel">
    {if (isset($debug) && debug)}
        <input type="hidden" id="cdiscount-debug" value="1" />
    {else}
        <input type="hidden" id="cdiscount-debug" value="0" />
    {/if}

    {if (isset($tab_accept_data))}
        {include file="orders_accept.tpl" tab_accept_data=$tab_accept_data}
    {/if}

    {if (isset($tab_import_data))}
        {include file="orders_import.tpl" tab_import_data=$tab_import_data}
    {/if}

    <div id="console" style="{$console_display|escape:'quotes':'UTF-8'}"></div>
</div>
<br/>

</fieldset>