/**
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
 * @author    Tran Pham
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * @package   Amazon Market Place
 * Support by mail:  support.amazon@common-services.com
 */

var CommonServices = {};

(function($) {
    function tippingGlossary(index, domElement) {
        var $glossaryCollection = $('#glossary'),
            $target = $(domElement),
            key = $target.attr('rel'),
            glossaryDiv = $glossaryCollection.find('div.glossary[rel=' + key + ']'); // performance trouble

        if (glossaryDiv && glossaryDiv.length) {
            var $span = $target.find('span').first(),
                title = ($span && $span.length) ? $span.text() : null;
            $target.qtip({
                content: {
                    text: glossaryDiv.html(),
                    title: title
                },
                hide: {
                    fixed: true,
                    delay: 300
                },
                plugins: {}
            });
            $target.addClass('tip');
        }
    }
    
    CommonServices.tippingGlossary = tippingGlossary;
})(jQuery);
