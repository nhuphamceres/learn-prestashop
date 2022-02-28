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
 * @package   Amazon Market Place
 * @author    Olivier B.
 * @copyright Copyright (c) Since 2011 Common Services Co Ltd / Feed.biz
 * @license   Commercial license
 * Support by mail:  support.amazon@common-services.com
 */

(function ($) {
    $(document).ready(function () {
        var $loader = $('.support-information-loader'),
            $content_result = $('#support-information-content');

        /**
         * Show PS info / Php info
         */
        $('#support-information-prestashop, #support-information-php').click(function () {
            $.ajax({
                type: 'POST',
                url: $(this).attr('rel') + '&callback=?',
                data: { fields: $('input, select, textarea, button').length },
                beforeSend: function () {
                    $loader.show();
                },
                success: function (data) {
                    $content_result.html(data).slideDown();
                },
                error: function (jqXHR) {
                    $content_result.html(jqXHR).slideDown();
                },
                complete: function () {
                    $loader.hide();
                }
            });
        });

        /**
         * Toggle dev mode
         */
        $('#support-mode_dev').click(function () {
            $('.support-information-loader').show();

            var current_status = $('#mode_dev-status').val();

            $('#devmode-response-success').html('').hide();
            $('#devmode-response-danger').html('').hide();

            $.ajax({
                type: 'POST',
                dataType: 'jsonp',
                url: $(this).attr('rel') + '&status=' + current_status + '&callback=?',
                beforeSend: function () {
                    $loader.show();
                },
                complete: function () {
                    $loader.hide();
                },
                success: function (data) {
                    $('#devmode-response-success').html(data.message).slideDown();
                    if (data.status) {
                        $('#mode_dev-status').val('0');
                        $('#support-mode_dev').val($('#mode_dev-status-off').val());
                        $('#prestashop-info-dev').show();
                    } else {
                        $('#mode_dev-status').val('1');
                        $('#support-mode_dev').val($('#mode_dev-status-on').val());
                        $('#prestashop-info-dev').hide();
                    }
                },
                error: function (data) {
                    $('#devmode-response-danger').html(data.responseText).slideDown();
                }
            });
        });

        var $supportLoader = $('#support-information-file-loader'),
            $downloadBlock = $('#support-information-download'),
            $downloadLink = $downloadBlock.find('a.support-url'),
            supportFileName = $downloadLink.data('fileName');

        /**
         * Download support files every time request
         */
        $downloadLink.click(function (e) {
            e.preventDefault();

            if (typeof html2canvas !== 'undefined') {
                $supportLoader.show();
                $downloadBlock.hide();

                setTimeout(function () {
                    html2canvas(document.body).then(function (canvas) {
                        var formData = new FormData();
                        formData.append('screenShot', canvas.toDataURL('image/png'));

                        fetch($downloadLink.attr('href'), {
                            method: 'POST',
                            body: formData
                        })
                            .then(response => response.blob())
                            .then(blob => {
                                var a = document.createElement('a');
                                var url = window.URL.createObjectURL(blob);
                                a.href = url;
                                a.download = supportFileName ? supportFileName + '-support.zip' : 'cs-amazon-support.zip';
                                document.body.append(a);
                                a.click();
                                a.remove();
                                window.URL.revokeObjectURL(url);

                                $supportLoader.hide();
                                $downloadBlock.show();
                            });
                    });
                }, 300);
            }
        });
    });
})(jQuery);
