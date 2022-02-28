/**
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
 *
 * @package   Mirakl
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  support.mirakl@common-services.com
 */

$(document).ready(function () {
    // For PS 1.5017
    if ($('select[name="id_address"]'))
        $('select[name="id_address"]').css('width', '400px');

    // get parameters - credits :
    // http://wowmotty.blogspot.com/2010/04/get-parameters-from-your-script-tag.html
    // extract out the parameters
    function gup(n, s) {
        n = n.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
        var p = (new RegExp("[\\?&]" + n + "=([^&#]*)")).exec(s);
        return (p === null) ? "" : p[1];
    }

    var scriptSrc = $('script[src*="orders_sheet.js"]').attr('src');
    var path = gup('path', scriptSrc);

    // load CSS
    //
    $('head').append("<link>");
    var cssi = $("head").children(":last");
    cssi.attr({
        rel: "stylesheet",
        type: "text/css",
        href: path + '/views/css/orders_sheet.css'
    });

    var tab_selector = $('#meUpdateOrder');

    if (tab_selector.length) {
        $(tab_selector).insertBefore('#formAddPaymentPanel')
    }
});
