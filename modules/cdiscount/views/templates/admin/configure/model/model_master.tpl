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
 * @author    Olivier B., Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail:  support.cdiscount@common-services.com
*}

<div id="master_model" class="model-create model form-group" style="display: none;">
    <input type="hidden" data-name="state" value="as-is" />

    <span class="model-del-2">{l s='Remove this model from the list' mod='cdiscount'}
        <img src="{$images_url|escape:'htmlall':'UTF-8'}cross.png" class="model-del-img2"
             alt="{l s='Remove this model from the list' mod='cdiscount'}" />
    </span>
    <h2>{l s='New Model' mod='cdiscount'}</h2>

    {include file="$module_path/views/templates/admin/configure/model/model_name_universe.tpl"
    model_id=null model_name=null universe=null}
</div>
