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
    setTimeout(function () {
        if ($('.warn:eq(0)').length)
            $('.warn:eq(0)').slideUp();
    }, 2000);

    if ($.datepicker.initialized !== 'undefined') {
        $("#datepickerFrom").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerTo").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerFromA").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerToA").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });
    }

    // Tabs
    //
    $('li[id^="menu-"]').click(function () {
        var result = $(this).attr('id').match('^(.*)-(.*)$');
        var lang = result[2];

        $('input[name=selected_tab]').val(lang);

        if (!$(this).hasClass('selected')) {
            $('li[id^="menu-"]').removeClass('selected');
            $(this).addClass('selected');
            $('div[id^="menudiv-"]').hide();
            $('div[id^="menudiv-' + lang + '"]').show();
        }
    });

    // Order Checkbox/Table Trigger for PS > 1.3
    //
    if ($.isFunction($(this).live)) {
        $('.order-item').live('click', function (event) {
            var checkbox = $(this).find('input[type=checkbox]');

            if (event.target.type !== 'checkbox')
                $(checkbox, this).trigger('click');
        });

        $('#import-table .select-all').live('click', function (event) {
            var checkboxes = $('#import-table input[name="selected_orders[]"]').not(':disabled');
            checkboxes.attr('checked', !checkboxes.attr('checked'));
        });

        $('#accept-table .select-all').live('click', function (event) {
            var checkboxes = $('#accept-table input[name="selected_orders[]"]').not(':disabled');
            checkboxes.attr('checked', !checkboxes.attr('checked'));
        });

    }

    /*
     * DEBUG Tab
     */

    $('#list-orders').click(function () {

        $('#debug-loader').show();
        // Display last export files
        //
        $.ajax({
            type: 'POST',
            url: $('#debug-orders-url').val() + '&action=' + $(this).attr('rel') + '&callback=?',
            dataType: 'json',
            success: function (data) {
                if (window.console)
                    console.log("Debug products...");
                $('#debug-loader').hide();
                $('#console').show();
                $('#console').html(data.msg);

            }
        });
    });


});
