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

{*<pre>{$snc_info|escape:'htmlall':'UTF-8'}</pre>*}

<div class="box">
    <h3 class="page-subheading">
        <img src="{$snsc_img_dir|escape:'htmlall':'UTF-8'}colissimo-h.png" style="float: right; margin-top: -10px;">
        {l s='Tracking number' mod='sonice_suivicolis'}
        : {$snsc_tracking_information.shipping_number|escape:'htmlall':'UTF-8'}
    </h3>
    <p>
        <strong>{l s='Statut' mod='sonice_suivicolis'}
            :</strong> {$snsc_tracking_information.coliposte_state|escape:'htmlall':'UTF-8'}<br>
        <strong>{l s='Localization' mod='sonice_suivicolis'}
            :</strong> {$snsc_tracking_information.coliposte_location|escape:'htmlall':'UTF-8'}<br>
        <strong>{l s='Last update' mod='sonice_suivicolis'}
            :</strong> {$snsc_tracking_information.date_upd|escape:'htmlall':'UTF-8'}<br>
    </p>

    {*<hr>*}

    {*<div class="container">*}
        {*<div class="row">*}
            {*<div class="col-md-12 col-sm-12 col-xs-12">*}
                {*<section class="main-timeline-section">*}
                    {*<div class="timeline-start"></div>*}
                    {*<div class="conference-center-line"></div>*}
                    {*<div class="conference-timeline-content">*}
                        {*<div class="timeline-article timeline-article-top">*}
                            {*<div class="content-date">*}
                                {*<span>03-03-2018</span>*}
                            {*</div>*}
                            {*<div class="meta-date"></div>*}
                            {*<div class="content-box">*}
                                {*<strong>Livré</strong><br>*}
                                {*51 avenue Paul Doumer, 75116 Paris<br>*}
                                {*Le colis a été laissé à l'intérieur de la boite aux lettres de la maison*}
                            {*</div>*}
                        {*</div>*}

                        {*<div class="timeline-article timeline-article-bottom">*}
                            {*<div class="content-date">*}
                                {*<span>02-03-2018</span>*}
                            {*</div>*}
                            {*<div class="meta-date"></div>*}
                            {*<div class="content-box">*}
                                {*<p>*}
                                    {*<strong>Expédié</strong>*}
                                {*</p>*}
                            {*</div>*}
                        {*</div>*}
                        {*<div class="timeline-article timeline-article-top">*}
                            {*<div class="content-date">*}
                                {*<span>01-03-2018</span>*}
                            {*</div>*}
                            {*<div class="meta-date"></div>*}
                            {*<div class="content-box">*}
                                {*<p>*}
                                    {*<strong>Préparation en cours</strong>*}
                                {*</p>*}
                            {*</div>*}
                        {*</div>*}

                        {*<div class="timeline-article timeline-article-bottom">*}
                            {*<div class="content-date">*}
                                {*<span>01-03-2018</span>*}
                            {*</div>*}
                            {*<div class="meta-date"></div>*}
                            {*<div class="content-box">*}
                                {*<p>*}
                                    {*<strong>Commandé</strong>*}
                                {*</p>*}
                            {*</div>*}
                        {*</div>*}
                    {*</div>*}
                    {*<div class="timeline-end"></div>*}
                {*</section>*}
            {*</div>*}
        {*</div>*}
    {*</div>*}
</div>
