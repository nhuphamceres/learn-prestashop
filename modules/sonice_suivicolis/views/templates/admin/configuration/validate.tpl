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
 * @author    Alexandre D.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.sonice@common-services.com
 *}


{if !$ps16x}
    <input type="submit" value="{l s='Save' mod='sonice_suivicolis'}" name="submitsonice_suivicolis" class="button submitconf" style="clear: both;">
    <div class="cleaner">&nbsp;</div>
{else}
    <div class="panel-footer">
        <button type="submit" value="1" name="submitsonice_suivicolis" class="btn btn-default pull-right">
            <i class="process-icon-save"></i> {l s='Save' mod='sonice_suivicolis'}
        </button>
    </div>
{/if}