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
<table>
    <tr>
        <th>&nbsp;</th>
        <th>{l s='Name' mod='amazon'}</th>
        <th>{l s='URL' mod='amazon'}</th>
        <th>{l s='Messages' mod='amazon'}</th>
    </tr>
    {foreach from=$data item=queue}
        <tr>
            <td>
                <input type="checkbox" name="purge_queue[{$queue.name|escape:'htmlall':'UTF-8'}]"
                       value="{$queue.url|escape:'htmlall':'UTF-8'}"/>
            </td>
            <td>
                {$queue.name|escape:'htmlall':'UTF-8'}
            </td>
            <td>
                {$queue.url|escape:'htmlall':'UTF-8'}
            </td>
            <td>
                {$queue.count|escape:'htmlall':'UTF-8'}
            </td>
        </tr>
    {/foreach}
</table>