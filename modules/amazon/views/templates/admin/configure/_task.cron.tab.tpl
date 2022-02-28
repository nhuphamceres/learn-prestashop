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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
*}

<p>
    <b>{$task.title|escape:'htmlall':'UTF-8'}</b> ({$task.lang|escape:'htmlall':'UTF-8'}):
    {l s='each' mod='amazon'} {$task.frequency|intval|abs} {if $task.frequency > 12}{l s='minutes' mod='amazon'}{elseif $task.frequency > 1}{l s='hours' mod='amazon'}{else}{l s='hour' mod='amazon'}{/if}, {l s='url' mod='amazon'}:
    <a href="{$task.url|escape:'quotes':'UTF-8'}" target="_blank">{$task.short_url|escape:'quotes':'UTF-8'}</a>
</p>
