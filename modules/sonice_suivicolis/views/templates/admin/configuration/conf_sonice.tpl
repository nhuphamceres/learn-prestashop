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

<div id="conf-sonice">
    <div class="form-group">
        <label class="col-lg-3"><h2>SoNice Suivi v{$snsc_module_version|escape:'htmlall':'UTF-8'}</h2></label>
        <div class="margin-form col-lg-9">
            <p class="descriptionBold">
                <span style="color: navy;">{$snsc_module_description|escape:'htmlall':'UTF-8'}</span>
            </p>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Informations' mod='sonice_suivicolis'}</label>
        <div class="margin-form col-lg-9" style="margin-top: 6px;">
            <span style="color:navy">{l s='Provided by' mod='sonice_suivicolis'} :</span> Common-Services<br>
            <br>
            <span style="color:navy">{l s='Informations, follow up on our blog' mod='sonice_suivicolis'} :</span><br>
            <a href="http://www.common-services.com" target="_blank">http://www.common-services.com</a><br>
            <br>
            <span style="color:navy">{l s='More informations about us on Prestashop website' mod='sonice_suivicolis'} :</span><br>
            <a href="http://www.prestashop.com/fr/agences-web-partenaires/or/common-services" target="_blank">http://www.prestashop.com/fr/agences-web-partenaires/or/common-services</a><br>
            <br>
            <span style="color:navy">{l s='You will appreciate our others modules' mod='sonice_suivicolis'} :</span><br>
            <a href="http://addons.prestashop.com/fr/58_common-services" target="_blank">http://addons.prestashop.com/fr/58_common-services</a><br>
        </div>
    </div>
    <br>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Documentation' mod='sonice_suivicolis'}</label>
        <div class="margin-form col-lg-9">
            <div class="col-lg-1"><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}books.png" alt="docs" /></div>
            <div class="col-lg-11">
                <span style="color:red; font-weight:bold;">{l s='Please first read the provided documentation' mod='sonice_suivicolis'} :</span><br>
                <a href="{$snsc_module_directory|escape:'htmlall':'UTF-8'}documentation/readme_fr.pdf" target="_blank">{$snsc_module_directory|escape:'htmlall':'UTF-8'}documentation/readme_fr.pdf</a><br>
            </div>
        </div>
    </div>
    <br>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Support' mod='sonice_suivicolis'}</label>
        <div class="margin-form col-lg-9">
            <div class="col-lg-1"><img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}submit_support_request.png" alt="support"></div>
            <div class="col-lg-11">
                <span style="color:red; font-weight:bold;">
                    {l s='The technical support is available by e-mail only.' mod='sonice_suivicolis'}
                </span><br>
                <span style="color: navy;">
                    {l s='For any support, please provide us' mod='sonice_suivicolis'} :<br>
                </span>
                <ul>
                    <li>{l s='A detailled description of the issue or encountered problem' mod='sonice_suivicolis'}</li>
                    <li>{l s='Your Pretashop Addons Order ID available in your Prestashop Addons order history' mod='sonice_suivicolis'}</li>
                    <li>{l s='Your Prestashop version' mod='sonice_suivicolis'} : <span style="color: red;">Prestashop {$ps_version|escape:'htmlall':'UTF-8'}</span></li>
                    <li>{l s='Your module version' mod='sonice_suivicolis'} : <span style="color: red;">SoNice Suivi de Colis v{$snsc_module_version|escape:'htmlall':'UTF-8'}</span></li>
                </ul>
                <br>
                <span style="color:navy">Support Common-Services :</span>
                <a href="mailto:contact@common-services.com?subject={l s='Support SoNice Suivi de Colis' mod='sonice_suivicolis'}&body={l s='Dear Support, I am currently having some trouble with your module v%s on my Prestashop v%s.' sprintf=[$snsc_module_version, $ps_version] mod='sonice_suivicolis'}" title="Email" >
                    contact@common-services.com
                </a>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="control-label col-lg-3">{l s='Licence' mod='sonice_suivicolis'}</label>
        <div class="margin-form col-lg-9">
            <p>
                {l s='This add-on is under a commercial licence from S.A.R.L. SMC' mod='sonice_suivicolis'}.<br>
                {l s='In case of purchase on Prestashop Addons, the invoice is the final proof of license.' mod='sonice_suivicolis'}<br>
                {l s='Contact us to obtain a license only in other cases' mod='sonice_suivicolis'} : <a href="mailto:contact@common-services.com">contact@common-services.com</a>
            </p>
        </div>
    </div>
</div>