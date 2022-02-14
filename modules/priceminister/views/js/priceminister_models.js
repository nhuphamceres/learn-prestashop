/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to a commercial license from SARL SMC
 * Use, copy, modification or distribution of this source file without written
 * license agreement from the SARL SMC is strictly forbidden.
 * In order to obtain a license, please contact us: contact@common-services.com
 * ...........................................................................
 * INFORMATION SUR LA LICENCE D'UTILISATION
 *
 * L'utilisation de ce fichier source est soumise a une licence commerciale
 * concedee par la societe SMC
 * Toute utilisation, reproduction, modification ou distribution du present
 * fichier source sans contrat de licence ecrit de la part de la SARL SMC est
 * expressement interdite.
 * Pour obtenir une licence, veuillez contacter la SARL SMC a l'adresse:
 *                  contact@common-services.com
 *
 * @author    Olivier B. / Debusschere A.
 * @copyright Copyright (c) Since 2010 S.A.R.L S.M.C - http://www.common-services.com
 * @license   Commercial license
 * Contact by Email :  support.priceminister@common-services.com
 */

var pmModelInitialized1 = false;
var on_load_attr = true;
$(document).ready(function () {
    if (pmModelInitialized1) return;
    pmModelInitialized1 = true;

    if (typeof($.fn.sortable) == 'function')
        $("#pm-model-container").sortable();

    $('#model-add').click(function () {
        var cloned = $('#pm-master-model .pm-model').clone().prependTo('.pm-model-group');

        $(cloned).find('.pm-model-header').hide();
        $(cloned).find('.pm-model-body').slideDown();
        showAttrFeatDetails(cloned);

        $(cloned).find('.product_type, select[name*="[size_def]"]').chosen(chosen_param);

        return (false);
    });

    $('#pm-model-container').delegate('.pm-model-delete', 'click', function () {
        $(this).parents().get(2).remove();
        return (false);
    });

    $('#pm-model-container').delegate('.pm-model-action-delete', 'click', function () {
        $(this).parents().get(4).remove();
        return (false);
    });

    $('#pm-model-container').delegate('.pm-model-minimize', 'click', function () {
        var model = $(this).parents().get(1);

        if (!$(model).find('select.product_type').val().length) {
            alert($('#model-error-product-type').val());
            return (false);
        }
        if (!$(model).find('input.model-model-name').val().length) {
            alert($('#model-error-model-name').val());
            return (false);
        }
        $(model).find('.pm-model-header-product-type').html($(model).find('select.product_type').val());
        $(model).find('.pm-model-header-model-name').html($(model).find('input.model-model-name').val());

        $(model).find('.pm-model-body').slideUp();
        $(model).find('.pm-model-header').slideDown();
        return (false);
    });

    $('#pm-model-container').delegate('.pm-model-action-edit', 'click', function () {
        var model = $(this).parents().get(3);
        $(model).find('.pm-model-header').slideUp();
        $(model).find('.pm-model-body').slideDown();
        return (false);
    });

    $('#pm-model-container').delegate('select.product_type', 'change', function () {
        var current = $(this);
        setValues(null, current);
        getProductTypeTemplate(current);
        return (false);
    });

    showAttrFeatDetails(null);

    //array keys setting on submit
    $('#configuration_form_submit_btn').bind('click', function () {
    });


    //Mapping

    $('.attribute-mapping-collapse a').click(function () {
        lang = $(this).attr('rel');
        $('#attribute-mapping-collapse-' + lang + ' a').toggle();
        $('#attribute-mapping-' + lang).toggle();
    });

    $('.feature-mapping-collapse a').click(function () {
        lang = $(this).attr('rel');
        $('#feature-mapping-collapse-' + lang + ' a').toggle();
        $('#feature-mapping-' + lang).toggle();
    });


    $('#attribute-mapping-collapse-group a').click(function () {
        $('#attribute-mapping').toggle();
    });

    $('#feature-mapping-collapse-group a').click(function () {
        $('#feature-mapping').toggle();
    });


    $('.add-attr-mapping').click(function () {
        var result;
        result = $(this).attr('id').match('.*-(.*)-(.*)-(.*)$');
        var id_lang = result[1];
        var id_group = result[2];

        var currentIndex = $('#new-mapping-' + id_lang + '-' + id_group).attr('rel');
        var newIndex = parseInt(currentIndex) + 1;

        var source = $('div[id^="attribute-group-' + id_lang + '-' + id_group + '-"]').filter(':last');

        var source_select = source.find('select[name^="mapping[prestashop]"]');
        var source_size = source_select.find('option').size();
        var source_val = source_select.find('option:selected').val();

        if (source_size <= 1)
            return (false);

        var cloned = source.clone().appendTo('#new-mapping-' + id_lang + '-' + id_group);

        cloned.attr('id', 'attribute-group-' + id_lang + '-' + id_group + '-' + newIndex);

        var selected_select = cloned.find('select[name^="mapping[prestashop]"]');
        selected_select.find('option[value=""]').remove();
        selected_select.find('option[value="' + source_val + '"]').remove();

        // Remove used options
        $(selected_select).find("option.used-option").remove();
        selected_select.attr('name', 'mapping[prestashop][' + id_lang + '][' + id_group + '][]');

        cloned.find('select[rel=color]').remove();

        var selected_input = cloned.find('select[name^="mapping[priceminister]"]');
        selected_input.val('');
        selected_input.attr('name', 'mapping[priceminister][' + id_lang + '][' + id_group + '][]');


        if (!cloned.find('option').length) {
            cloned.remove();
            return (false);
        }
        $('#new-mapping-' + id_lang + '-' + id_group).attr('rel', newIndex);

        cloned.find('.add-attr-mapping').remove();
        cloned.find('.del-attr-mapping').show().click(function () {
            $(this).parent().remove();
        });

    });

    $('.del-attr-mapping').click(function () {
        $(this).parent().remove();
    });


    // Features Fields MAPPING
    //
    $('.feature_mapping_type').change(function () {
        $(this).removeClass('mapping-type-required');

        var result;
        result = $(this).attr('name').match('.*\\[(.*)\\]\\[(.*)\\]$');

        if (!result.length)
            return;

        var id_lang = result[1];
        var id_group = result[2];

        console.log(result);

        $('input[name^="features_mapping[priceminister][' + id_lang + '][' + id_group + ']"], select[name^="features_mapping[priceminister][' + id_lang + '][' + id_group + ']"]').each(function (key, obj) {
            console.log(obj);
        });

    });


    $('.add-feature-mapping').click(function () {

        var result;
        result = $(this).attr('id').match('.*-(.*)-(.*)-(.*)$');
        var id_lang = result[1];
        var id_group = result[2];

        var currentIndex = $('#new-feature-mapping-' + id_lang + '-' + id_group).attr('rel');
        var newIndex = parseInt(currentIndex) + 1;

        var source = $('div[id^="feature-group-' + id_lang + '-' + id_group + '-"]').filter(':last');
        var source_select = source.find('select[name^="features_mapping[prestashop]"]');
        var source_size = source_select.find('option').size();
        var source_val = source_select.find('option:selected').val();

        if (source_size <= 1)
            return (false);

        var cloned = source.clone().appendTo('#new-feature-mapping-' + id_lang + '-' + id_group);

        cloned.attr('id', 'feature-group-' + id_lang + '-' + id_group + '-' + newIndex);

        var selected_select = cloned.find('select[name^="features_mapping[prestashop]"]');
        selected_select.find('option[value=""]').remove();
        selected_select.find('option[value="' + source_val + '"]').remove();

        // Remove used options
        $(selected_select).find("option.used-option").remove();
        selected_select.attr('name', 'features_mapping[prestashop][' + id_lang + '][' + id_group + '][]');

        cloned.find('select[rel=color]').remove();

        var selected_input = cloned.find('select[name^="features_mapping[priceminister]"]');
        selected_input.val('');
        selected_input.attr('name', 'features_mapping[priceminister][' + id_lang + '][' + id_group + '][]');

        if (!cloned.find('option').length) {
            cloned.remove();
            return (false);
        }
        $('#new-feature-mapping-' + id_lang + '-' + id_group).attr('rel', newIndex);

        cloned.find('.add-feature-mapping').remove();
        cloned.find('.del-feature-mapping').show().click(function () {
            $(this).parent().remove();
        });
    });

});


function setValues(data, reference) {
    var container = $(reference).parent().parent().parent();

    if (data === null) {
        $(container).find('div.product_type_template_container').first().html("");
    } else {
        if (typeof data.product_type_template !== "undefined") {
            $(container).find('div.product_type_template_container').first().html(data.product_type_template);
            showAttrFeatDetails(container);
        }
    }
}


function getProductTypeTemplate(current) {
    el = current;
    base_url = $("#pm_module_url").val();
    $.ajax({
        type: 'POST',
        url: base_url + "functions/form_data.php",
        data: {
            product_type: el.val(),
            action: 'product_type_template'
        },
        dataType: 'json',
        success: function (data) {
            setValues(data, el);
        },
        error: function (data) {
            setValues(null, el);
            $('#memory_peak').html("--");
            console.log(data);
            // alert(data);
        }
    });
}


function showAttrFeatDetails(reference) {
    var ref;
    if (reference !== null) {
        ref = $(reference).find("select.main");
    } else {
        ref = $("select.main");
    }
    $(ref).unbind("change").bind("change", function () {
        var pattern = /([\[]).([^\]])*(opt[\]])$/i;
        var val = $(this).val();
        var name = $(this).attr("name");
        attr_name = name.match(pattern);
        if (attr_name === null) {
            return;
        }
        try {
            attr_name = attr_name[0].replace("[", "").replace("]", "").replace("_opt", "");
        } catch (e) {
            return;
        }
        var default_hide = false;
        var default_display = false;

        $(this).parent().find("select.inline-element:not(select.main)").hide();
        var suffix = "__";
        if (val === "Attribute Value") {
            suffix = "_attr";
            //default_display = true;
        } else if (val === "Feature Value") {
            suffix = "_feat";
            //default_display = true;
        } else if (val === "PM Value") {
            suffix = "";
            //default_hide = true;
        }

        //Not first load of the page
        if (!on_load_attr) {
            $(this).parent().find("select[name*='[" + attr_name + "_attr]']").first().val("");
            $(this).parent().find("select[name*='[" + attr_name + "_feat]']").first().val("");
            $(this).parent().find("select[name*='[" + attr_name + "_def]']").first().val("");
            //Default element is hidden when ATTR/FEAT have no selected value
            default_hide = true;
        } else {
            //when the page is loaded by the first time
            default_hide = ($(this).parent().find("select[name*='[" + attr_name + "_feat]']").first().val() === ""
            && $(this).parent().find("select[name*='[" + attr_name + "_attr]']").first().val() === "");
            if (default_hide) {
                $(this).parent().find("select[name*='[" + attr_name + "_def]']").first().val("");
            }
        }
        if (default_hide) {
            $(this).parent().find("select[name*='[" + attr_name + "_def]']").first().parent().hide();
        } else if (default_display) {
            $(this).parent().find("select[name*='[" + attr_name + "_def]']").first().parent().show();
        }
        $(this).parent().find("select[name*='[" + attr_name + suffix + "]']").first().show();
    });

    if (reference === null) {
        $(ref).change();
    }

    // FIX
    $('select[name*="[advert]["][name*="_attr]"], select[name*="[advert]["][name*="_feat]"]').unbind("change").bind("change", function () {
        $(this).parent().find('.pmDefaultLabel').length &&
        $(this).parent().find('.pmDefaultLabel').parent().attr('style', 'display: inline; float: right;').show() &&
        $(this).parent().find('.pmDefaultLabel').parent().find('select[name*="[size_def]"], select[name*="[garmentType_def]"]') &&
        $(this).parent().find('.pmDefaultLabel').parent().find('select[name*="[size_def]"], select[name*="[garmentType_def]"]').chosen() &&
        $(this).parent().find('.pmDefaultLabel').parent().find('.chosen-container').addClass('inline-element').css('top', 0);
    });

    $("select.pmAttribute").unbind("change").bind("change", function () {
        var hideElement = $(this).val() === "";
        if (hideElement) {
            $(this).parent().find("label.pmDefaultLabel").first().parent().removeAttr('style').hide();
        } else {
            $(this).parent().find("label.pmDefaultLabel").first().parent().attr('style', 'display: inline; float: right;').find('select').css('width', '200px');
            if ($(this).parent().find("label.pmDefaultLabel").first().parent().attr('style', 'display: inline; float: right;').find('select[name*="[size_def]"], select[name*="[garmentType_def]"]')) {
                $(this).parent().find("label.pmDefaultLabel").first().parent().attr('style', 'display: inline; float: right;').find('select[name*="[size_def]"], select[name*="[garmentType_def]"]').chosen();
                $(this).parent().find("label.pmDefaultLabel").first().parent().find('.chosen-container').addClass('inline-element').css('top', 0);
            }
        }
    });

    $("select.pmFeature").unbind("change").bind("change", function () {
        var hideElement = $(this).val() === "";
        if (hideElement) {
            $(this).parent().find("label.pmDefaultLabel").first().parent().removeAttr('style').hide();
        } else {
            $(this).parent().find("label.pmDefaultLabel").first().parent().attr('style', 'display: inline; float: right;').find('select').css('width', '200px');
        }
    });

    on_load_attr = false;
}


function selectAttributeChange(obj) {
    selected_val = $(obj).val();
    var result = $(obj).attr('rel').match('(.*)-(.*)$');
    var id_lang = result[1];
    var id_group = result[2];
    ps_repeated = 0;
    self = $(obj).attr('id');
    $('select[name^="mapping[prestashop][' + id_lang + '][' + id_group + ']"]').each(function (i, el) {
        var curval = $(el).val();
        if (curval === selected_val) {
            ps_repeated += 1;
        }
    });
    if (ps_repeated > 1) {
        $(obj).val("");
        alert("This value is already mapped, please choose another option");
        return false;
    }
    return true;
}

function selectFeatureChange(obj) {
    selected_val = $(obj).val();
    var result = $(obj).attr('rel').match('(.*)-(.*)$');
    var id_lang = result[1];
    var id_group = result[2];
    ps_repeated = 0;
    self = $(obj).attr('id');
    $('select[name^="features_mapping[prestashop][' + id_lang + '][' + id_group + ']"]').each(function (i, el) {
        var curval = $(el).val();
        if (curval === selected_val) {
            ps_repeated += 1;
        }
    });
    if (ps_repeated > 1) {
        $(obj).val("");
        alert("This value is already mapped, please choose another option");
        return false;
    }
    return true;
}
