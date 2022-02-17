/* ------------------------------------------------------------------------------------
   This module contains javascripts functions used only for the SCD multivendor functionality.
   ----------------------------------------------------------------------------------- */


jQuery(document).ready(function () {
//--------------------------------------------------------------------------------------------
// set base currency if vendor select Base currency only 
jQuery(document).on('change', '#scd-currency-option', function (e) {
    e.preventDefault();
    if(jQuery(this).children("option:selected").val().indexOf('base-currency') !== -1){
        jQuery('#scd-currency-list').val(settings.baseCurrency);
    }
  });
  // use currency on default currency only if vendor select other currency
  jQuery(document).on('change', '#scd-currency-list', function (e) {
    e.preventDefault();
    if(jQuery(this).children("option:selected").val() !== settings.baseCurrency){
      jQuery('#scd-currency-option').val('only-default-currency');
    }
  });
//-----------------------------------------------------------------------------

	    jQuery.post(
        scd_ajax.ajax_url,
        {
            'action': 'scd_wcv_get_user_currency'
        },
        function (response) {
            localStorage.response = response;
			
			//alert(localStorage.response);
			
            let user_currency0 = ((response.replace(/[0-9]/g, '')).replace(/\s/g, '')).toString();
            if (false && (response.indexOf('FALSE0') === -1)) {
                var user_currency = response.split('<')[0].replace(' ', '').toString()[0] + response.split('<')[0].replace(' ', '').toString()[1] + response.split('<')[0].replace(' ', '').toString()[2];
                localStorage.response = user_currency;
                var elements = jQuery('.wcvendors-pro-dashboard-wrapper .amount');

				//console.log(elements);
                var len = elements.length;
               // if ((jQuery('#woocommerce-order-items')[0] === undefined)) {
                    for (var i = 0; i < len; i++) {
                        if (user_currency !== "") {
                            scd_simpleConvert(elements[i], user_currency0, i);
                        } else {
                            scd_simpleConvert(elements[i], user_currency, i);
                        }
                    }

            }
        }
    );	
	
	
jQuery('#dashboard-menu-item-scd a').click(function(e) {
       

        e.preventDefault();
        jQuery.post(
                scd_ajax.ajax_url,
                {
                    'action': 'scd_show_user_currency'
                },
        function(response) {
                    jQuery('.wcv_dashboard_overview').hide();
                    jQuery('.wcv_reports').hide();
                    jQuery('.wcv_recent').hide();
                    jQuery('.wcv-form.wcv-form-exclude').hide();
                    //jQuery('.wcv_dashboard_datepicker.wcv-cols-group').append(response);
            jQuery('.scd-choose-curr').hide();
			jQuery('.wcv_actions.wcv-cols-group').hide();             
            jQuery('.wcv-grid').append(response);
            jQuery('h3').hide();
            jQuery('.wcv-form').hide();
            jQuery('.wcv-actions').hide();
            jQuery('.wcvendors-table').hide();

 var elements = jQuery('li');
        for (var i = 0; i < elements.length; i++) {
            if (jQuery(elements[i]).hasClass('active')) {
                jQuery(elements[i]).removeClass('active');
                jQuery('#dashboard-menu-item-scd').addClass('active');
            }
        }
        }
        );
    });
    
    jQuery(document).on('click', '#scd-save-curr', function (e) {
        e.preventDefault();
        var user_currency = jQuery('#scd-currency-list').val();
        jQuery.post(
                scd_ajax.ajax_url,
                {
                    'action': 'scd_update_user_currency',
                    'user_currency': user_currency
                },
                function (response) {
                    jQuery('#scd-action-status').html(response);
                }
        );
    });
    
    jQuery(document).on('click', '#scd-save-currency-option', function (e) {
        e.preventDefault();
        var user_currency_option = jQuery('#scd-currency-option').val();
        jQuery.post(
                scd_ajax.ajax_url,
                {
                    'action': 'scd_update_user_currency_option',
                    'user_currency_option': user_currency_option
                },
                function (response) {
                    jQuery('#scd-action-status').html(response);
				
                }
        );
        // save user currency
        var user_currency = jQuery('#scd-currency-list').val();
        jQuery.post(
                scd_ajax.ajax_url,
                {
                    'action': 'scd_update_user_currency',
                    'user_currency': user_currency
                },
                function (response) {
                    jQuery('#scd-action-status').html(response);
					
                }
        );
    });

    jQuery('#scd-wcv-select-currencies').attr('multiple', 'TRUE');
    jQuery('#scd-wcv-select-currencies').val(null).trigger('change');
//jQuery('#scd-wcv-select-currencies').val(null).trigger('change');
    jQuery(".scd_wcv_select").data("placeholder", "Set currency per product...").chosen();

    jQuery('.scd_wcfm_select_price').attr('disabled', 'true');
    jQuery(".scd_wcfm_select, .scd_wcv_select").change(function () {
        var key = '';

        var newKeys, oldKeys;

        oldKeys = jQuery('#scd-bind-select-curr').val().toString().split(',');

        if (!jQuery('#scd-wcv-select-currencies').val() == '')
            newKeys = jQuery('#scd-wcv-select-currencies').val().toString().split(',');
        else {
            newKeys = '';
        }

        if (jQuery('#scd-bind-select-curr').val() !== '') {

            if (newKeys.length >= oldKeys.length) {

                if (newKeys.length > 0) {
                    key = newKeys[newKeys.length - 1];
                    for (var id = 0; id < newKeys.length; id++) {
                        if (oldKeys.includes(newKeys[id]) == false)
                            key = newKeys[id];
                    }
                }

                var myregselect = '<option id="reg_' + key + '" value=' + key + ' >Regular price (' + key + ')</option>';
                var mysalselect = '<option id="reg_' + key + '" value=' + key + ' >Sale price (' + key + ')</option>';
                jQuery('#scd_regularCurrency').append(myregselect);
                jQuery('#scd_saleCurrency').append(mysalselect);
                jQuery('#scd-bind-select-curr').val(jQuery('#scd-wcv-select-currencies').val());

            } else {
                for (var k = 0; k < oldKeys.length; k++) {
                    if (newKeys.indexOf(oldKeys[k]) == -1) {
                        jQuery('#scd_regularCurrency option[value="' + oldKeys[k] + '"]').remove();
                        jQuery('#scd_saleCurrency option[value="' + oldKeys[k] + '"]').remove();
                    }
                }
            }
            jQuery('#scd-bind-select-curr').val(jQuery('#scd-wcv-select-currencies').val());
        } else {
            if (newKeys.length > 0) {
                key = newKeys[newKeys.length - 1];
            }
            var myregselect = '<option id="reg_' + key + '" value=' + key + ' >Regular price (' + key + ')</option>';
            var mysalselect = '<option id="sale_' + key + '" value=' + key + ' >Sale price (' + key + ')</option>';
            jQuery('#scd_regularCurrency').append(myregselect);
            jQuery('#scd_saleCurrency').append(mysalselect);
            jQuery('#scd-bind-select-curr').val(jQuery('#scd-wcv-select-currencies').val());

        }

        if (jQuery(this).val() !== null) {
            var tabCurr = jQuery(this).val().toString().split(',');
            if (tabCurr.length > 0) {
                var regularBloc = '';
                var saleBloc = '';
                var newpriceField = '';
                var priceField = jQuery('#priceField').val();
                var tabC;
                for (var i = 0; i < tabCurr.length; i++) {
                    regularBloc = 'regular_' + tabCurr[i] + '_';
                    saleBloc = '-sale_' + tabCurr[i] + '_';
                    var regularPrice = '', salePrice = '';
                    if (priceField.indexOf(regularBloc) > -1) {
                        regularPrice = priceField.substr(priceField.indexOf(regularBloc) + regularBloc.length,
                                priceField.indexOf(saleBloc) - priceField.indexOf(regularBloc) - regularBloc.length);

                        tabC = priceField.toString().split(',');
                        var pos = -1;
                        for (var j = 0; j < tabC.length; j++) {
                            if (tabC[j].indexOf('sale_' + tabCurr[i]) > -1) {
                                pos = j;
                            }
                        }

                        if (pos > -1) {
                            var tc = tabC[pos].toString().split('_');
                            if (tc.length > 0) {
                                salePrice = tc[tc.length - 1];
                            }
                        }
                    }
                    if (i == 0) {
                        newpriceField = 'regular_' + tabCurr[i] + '_' + regularPrice + '-sale_' + tabCurr[i] + '_' + salePrice;
                    } else {
                        newpriceField = newpriceField + ',regular_' + tabCurr[i] + '_' + regularPrice + '-sale_' + tabCurr[i] + '_' + salePrice;
                    }
                }
                jQuery('#priceField').val(newpriceField);
            }
        }
    });

    // binding '#scd_regularCurrency' and #scd_saleCurrency'
    jQuery('#scd_regularCurrency').change(function () {
        jQuery('#scd_saleCurrency').val(jQuery('#scd_regularCurrency').val()).change();
        //jQuery('#scd_regularPriceCurrency').val( jQuery('#regularField_'+jQuery('#scd_regularCurrency').val()).val());
        //jQuery('#scd_salePriceCurrency').val( jQuery('#saleField_'+jQuery('#scd_saleCurrency').val()).val());
        var priceField = jQuery('#priceField').val();

        var regularBloc = 'regular_' + jQuery('#scd_regularCurrency').val() + '_';
        var saleBloc = '-sale_' + jQuery('#scd_regularCurrency').val() + '_';
        var price = priceField.substr(priceField.indexOf(regularBloc) + regularBloc.length,
                priceField.indexOf(saleBloc) - priceField.indexOf(regularBloc) - regularBloc.length);
        jQuery('#scd_regularPriceCurrency').val(price);

        var tabCurr = priceField.toString().split(',');
        var pos = -1;
        for (var j = 0; j < tabCurr.length; j++) {
            if (tabCurr[j].indexOf('sale_' + jQuery('#scd_saleCurrency').val()) > -1) {
                pos = j;
            }
        }

        if (pos > -1) {
            var tc = tabCurr[pos].toString().split('_');
            if (tc.length > 0) {
                jQuery('#scd_salePriceCurrency').val(tc[tc.length - 1]);

            }
        }

    });
    // end binding

    // start save regular price entered for each currency when hoverout field  
    jQuery('#scd_regularPriceCurrency').focusout(function () {
        // jQuery('#regularField_'+jQuery('#scd_regularCurrency').val()).val(jQuery(this).val());

        var priceField = jQuery('#priceField').val();
        var regularBloc = 'regular_' + jQuery('#scd_regularCurrency').val() + '_';
        var saleBloc = '-sale_' + jQuery('#scd_regularCurrency').val() + '_';

        priceField = priceField.substr(0, priceField.indexOf(regularBloc)) + regularBloc + jQuery(this).val() +
                priceField.substr(priceField.indexOf(saleBloc));
        jQuery('#priceField').val(priceField);

    });
    // end save regular price

    // start save sale price entered for each currency when hoverout field  
    jQuery('#scd_salePriceCurrency').focusout(function () {
        //jQuery('#saleField_'+jQuery('#scd_saleCurrency').val()).val(jQuery(this).val());

        var priceField = jQuery('#priceField').val();
        var tabCurr = priceField.toString().split(',');
        var pos = -1;
        for (var j = 0; j < tabCurr.length; j++) {
            if (tabCurr[j].indexOf('sale_' + jQuery('#scd_saleCurrency').val()) > -1) {
                pos = j;
            }
        }
        if (pos > -1) {
            tabCurr[pos] = tabCurr[pos].substr(0, tabCurr[pos].indexOf('sale')) + 'sale_' + jQuery('#scd_saleCurrency').val() + '_' + jQuery(this).val();
            priceField = tabCurr[0];
            for (var j = 1; j < tabCurr.length; j++) {
                priceField = priceField + ',' + tabCurr[j];
            }

            jQuery('#priceField').val(priceField);
        }
    });
    // end save sale price

});
