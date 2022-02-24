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
 * ...........................................................................
 *
 * @author    Alexandre D. & Olivier B.
 * @copyright Copyright (c) 2011-2015 Common Services Co Ltd - 90/25 Sukhumvit 81 - 10260 Bangkok - Thailand
 * @license   Commercial license
 * Support by mail  :  contact@common-services.com
 */

$(document).ready(function () {

    if ($.datepicker.initialized !== 'undefined') {
        $("#datepickerTo1").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerFrom1").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerTo2").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });

        $("#datepickerFrom2").datepicker({
            prevText: "",
            nextText: "",
            dateFormat: "yy-mm-dd"
        });
    }

    $(document).ajaxError(function (data) {
        $('#result').toggleClass('conf error');

        if (data.responseText)
            $('#result').append('<br />' + data.responseText);

        if (window.console)
            console.log(data);
    });

    $('li[id^="menu-"]').click(function () {
        result = $(this).attr('id').match('^(.*)-(.*)$');
        lang = result[2];

        $('input[name=selected_tab]').val(lang);

        if (!$(this).hasClass('selected')) {
            $('li[id^="menu-"]').removeClass('selected');
            $(this).addClass('selected');
            $('div[id^="menudiv-"]').hide();
            $('div[id^="menudiv-' + lang + '"]').show();
        }
    });

    $('#update-products, #lookup-products').click(function () {

        var result = '#result_update';

        if (!$("#update").find('input[name="categoryBox[]"]').serialize().length) {
            $(result).fadeOut();
            alert($('#msg_choose').val());
            return (false);
        }

        if (!$('#datepickerFrom2').val()) {
            $(result).fadeOut();
            alert($('#msg_date').val());
            return (false);
        }

        var params = '';
        if ($(this).attr('rel') === 'update') {
            params = '&action=update';
        }

        $('#result2').find('div').html('').fadeOut();
        $(result).fadeIn().find('div').html('<img src="' + $('#img_loader').val() + '" alt="" />');

        $.ajax({
            type: 'POST',
            url: $('#update_url').val() + '?fnac_token=' + $('#fnac_token').val(),
            data: $('#updateFnac').serialize() + '&context_key=' + $('input[name=context_key]').val() + params,
            success: function (data) {
                $('#result_update').show().find('div').html(data);
            },
            error: function (data) {
                $('#result_update').hide();
                $('#result_update_error').show().find('div').html(data.responseText);
            }
        });
        return false;
    });

    $('#masscsv-products').click(function () {
        $('#result_create, #result_create_error').hide();
        var result = '#result_create';

        if (!$("#masscsv").find('input[name="categoryBox[]"]').serialize().length) {
            $(result).fadeOut();
            alert($('#msg_choose').val());
            return false;
        }

        if (!$(this).attr("name") === "masscsv-products" && !$('#importDate').val()) {
            $(result).fadeOut();
            alert($('#msg_date').val());
            return false;
        }

        var params = '&action=create';

        $('#result2').html('').fadeOut();
        $(result).fadeIn().find('div').html('<img src="' + $('#img_loader').val() + '" alt="" />');

        $.ajax({
            type: 'POST',
            url: $('#create_url').val() + '?fnac_token=' + $('#fnac_token').val(),
            data: $('#createFnac').serialize() + '&context_key=' + $('input[name=context_key]').val() + params,
            success: function (data) {
                $('#result_create').show().find('div').html(data);
            },
            error: function (data) {
                $('#result_create').hide();
                $('#result_create_error').show().find('div').html(data.responseText);
            }
        });

        return false;
    });

    $('#csv-products').click(function () {

        var result = '#result_csv';

        if (!$("#menudiv-csv").find('input[name="categoryBox_csv[]"]').serialize().length) {
            $(result).fadeOut();
            alert($('#msg_choose_csv').val());
            return (false);
        }

//      if ( !$(this).attr("name") == "csv-products" && ! $('#importDate_csv').val() )
//      {
//        $(result).fadeOut() ;
//        alert( $('#msg_date').val() ) ;
//        return(false) ;      
//      }

        var params = '&action=export_products_as_csv';

        //$('#result2_csv').html('') ;
        //$('#result2_csv').fadeOut() ;
        $(result).fadeIn();
        $(result).html('<img src="' + $('#img_loader_csv').val() + '" alt="" />');

        $.ajax({
            type: 'POST',
            url: $('#export_url_csv').val(),
            data: $('#createCSVFnac').serialize() + '&context_key=' + $('input[name=context_key]').val() + params,
            success: function (data) {
                $('#result_csv').html(data);
            },
            error: function (data) {
                $('#result_csv').html(data);
            }
        });
        return (false);
    });

    $('input[name=checkme]').click(function () {

        $('input[id^=categoryBox]').each(function () {
            if ($(this).attr('checked'))
                $(this).attr('checked', false);
            else
                $(this).attr('checked', 'checked');
        });

    });


});
 
 