<?php

/* -------------------------------------------------------
   This module contains functions used only for the SCD multivendor functionality.
   It is included by the index.php file.
   ------------------------------------------------------- */
//    add_action('dokan_get_all_cap','scd_dokan_capability');
//    function scd_dokan_capability($capabilities) {
//        $capabilities['menu']['dokan_view_scd_currency_menu']=__( 'View scd currency menu', 'dokan-lite' );
//        return $capabilities;
//    }

function scd_save_product_prices($post_id,$data) {
    //var_dump($data);
    	$shipping_scd_fee_national = ( isset( $_POST['_scd_shipping_fee_national'] ) ) ? wc_format_decimal( $_POST['_scd_shipping_fee_national'] ) : '';
	$shipping_scd_fee_international = ( isset( $_POST['_scd_shipping_fee_international'] ) ) ? wc_format_decimal( $_POST['_scd_shipping_fee_international'] ) : '';
	update_post_meta($vendor_id, '_scd_shipping_fee_national', $shipping_scd_fee_national);
	update_post_meta($vendor_id, '_scd_shipping_fee_international', $shipping_scd_fee_international);
	update_post_meta($vendor_id, '_complete_regular_price', $data['regular_price']);
	update_post_meta($vendor_id, '_complete_sale_price', $data['regular_price']);
    
        $scd_userRole = scd_get_user_role();
        $scd_userID = get_current_user_id();
        $scd_currencyVal = '';
        if (isset($data['scd_currencyVal'])) {
            //if ($_POST['scd_currencyVal'] !== '') {
            $scd_currencyVal = $data['scd_currencyVal'];
            //}
        }

        $priceField = '';
        if (isset($data['priceField'])) {
            if ($data['priceField'] !== '') {
                $priceField = $data['priceField'];
            }
        }
        // save data
        $user_curr= scd_get_user_currency();
            if ($user_curr!==FALSE && isset($data['scd_sale_price'])) {
                $scd_currencyVal=$user_curr;
                $priceField = 'regular_'.$scd_currencyVal.'_'.$data['scd_regular_price'].'-sale_'.$scd_currencyVal.'_'.$data['scd_sale_price'];
        }
        
        $curr_opt= scd_get_user_currency_option();
        if($user_curr!==FALSE && $user_curr!==get_option('woocommerce_currency') && $curr_opt=='only-default-currency'){
             $scd_currencyVal=$user_curr;
                $priceField = 'regular_'.$scd_currencyVal.'_'.$data['regular_price'].'-sale_'.$scd_currencyVal.'_'.$data['sale_price'];
        //save the equivalent price entered by user in base currency
                 $converted=scd_function_convert_subtotal($data['regular_price'], get_option('wocommerce_currency'), $scd_currencyVal , 100,TRUE );
                 
                 update_post_meta($post_id,'_regular_price',$converted);
             if($data['sale_price']!==''){
                 $converted=scd_function_convert_subtotal($data['sale_price'],get_option('wocommerce_currency'), $scd_currencyVal , 2,TRUE );
              update_post_meta($post_id,'_sale_price',$converted);
              update_post_meta($post_id,'_price',$converted);
			  update_post_meta($vendor_id, '_complete_price', $data['sale_price']);

             } else {
			  
		      update_post_meta($vendor_id, '_complete_price', $data['regular_price']);
              update_post_meta($post_id,'_price',$converted);   
             
			 }
        }elseif ($user_curr!==FALSE) {
         
        }
        if($priceField!=='')
        update_post_meta($post_id, 'scd_other_options', array(
            "currencyUserID" => $scd_userID,
            "currencyUserRole" => $scd_userRole,
            "currencyVal" => $scd_currencyVal,
            "currencyPrice" => $priceField
        ));
    }

  //wc vendors
add_action('wcv_dashboard_pages_nav','scd_wcv_pro_dashborad_menu');
function scd_wcv_pro_dashborad_menu($pages){
    $pages['scd'] = array(
            'slug' => 'scd',
         'id' => 'scd',
        'class' => 'scd-wcv-nav',
          'label' => 'SCD Currency',
          'actions' => array()
        );
    return $pages;
}
//wcv_save_product
//for register our meta this hook is locate in class-wcvendors-product-controller.php function save_product
add_action('wcv_save_product', 'scd_wcv_simple_product_save');
function scd_wcv_simple_product_save($post_id) {
$data=$_POST;
$data['regular_price']=$_POST['_regular_price'];
$data['sale_price']=$_POST['_sale_price'];
scd_save_product_prices($post_id,$data);    
}

//wc-vendors plugin itegration
  add_action('wcv_product_options_general_product_data', 'scd_wcvendors_simple_product');
  function scd_wcvendors_simple_product($post_id) {
   $user_curr = scd_get_user_currency();
        $user_curr_opt = scd_get_user_currency_option();
            $curr_symbol = get_woocommerce_currency_symbol($user_curr);
            list($regprice, $saleprice) = scd_get_product_custom_price_for_currency($post->ID, $user_curr);
            
            if  ($user_curr_opt == 'base-and-default-currency') {
            echo '<label>SCD Regular price('.$user_curr.')</label>';
            echo '<input type="numeric" style="width: 57%;border-radius: 3px;border: 1px solid #ccc;" name="scd_regular_price" />';
            echo '<label>SCD Sale price('.$user_curr.')</label>';
            echo '<input type="numeric" name="scd_sale_price" style="width: 57%;border-radius: 3px;border: 1px solid #ccc;" />';
      } 
}

     //integrate scd in wc-vendors plugin
    add_filter('wcv_product_price','scd_wcv_product_price');
    function scd_wcv_product_price($price) {
        
        $user_curr = scd_get_user_currency();
         $user_curr_opt = scd_get_user_currency_option();
          $curr_symbol = get_woocommerce_currency_symbol($user_curr);
            list($regprice, $saleprice) = scd_get_product_custom_price_for_currency($price['post_id'], $user_curr);
            if(empty($regprice)){
            $regprice=get_post_meta($price['post_id'],'_regular_price',true);
            if(!empty($regprice)) 
            $regprice=scd_function_convert_subtotal($regprice, get_option('wocommerce_currency'), $user_curr , 2);
            //$saleprice=get_post_meta($post->ID,'_sale_price',true);
            //$saleprice=scd_function_convert_subtotal($saleprice, get_option('wocommerce_currency'), $user_curr , 2);
         }
            if  ($user_curr_opt == 'only-default-currency') {
                $price['label']=__('Regular Price', 'wcvendors-pro') . ' (' .$curr_symbol.')';
                $price['value']=$regprice;
            }
        return $price;
    }
    
    add_filter('wcv_product_sale_price','scd_wcv_product_sale_price');
    function scd_wcv_product_sale_price($sale_price) {
        $user_curr = scd_get_user_currency();
        $user_curr_opt = scd_get_user_currency_option();
            $curr_symbol = get_woocommerce_currency_symbol($user_curr);
            list($regprice, $saleprice) = scd_get_product_custom_price_for_currency($sale_price['post_id'], $user_curr);
           if(empty($regprice)){
//            $regprice=get_post_meta($post->ID,'_regular_price',true);
//             $regprice=scd_function_convert_subtotal($regprice, get_option('wocommerce_currency'), $user_curr , 2);
            $saleprice=get_post_meta($sale_price['post_id'],'_sale_price',true);
            if(!empty($saleprice))
            $saleprice=scd_function_convert_subtotal($saleprice, get_option('wocommerce_currency'), $user_curr , 2);
        }   
            if  ($user_curr_opt == 'only-default-currency') {
                $sale_price['label']=__('Sale Price', 'wcvendors-pro') . ' (' .$curr_symbol.')';
                 $sale_price['value']=$saleprice;
            }
            
        return $sale_price;
    }

    //add_filter('wcv_process_commission', 'scd_wcv_process_commission', 10, 6);

function scd_wcv_process_commission($commission, $product_id, $product_price, $order, $qty, $item) {
    $options=  get_option('scd_general_options',true);
    if(!empty($options['multiCurrencyPayment'])){
     $currency=$order->get_currency();
     $commission = scd_function_convert_subtotal($commission, get_option('wocommerce_currency'), $currency, 2,TRUE);
      $commission = round($commission, 2);
    }
    return $commission;
}
// the filter_wcvendors_pro_product_form_product_variations_path callback

add_filter( 'wcvendors_pro_product_variation_path', 'scd_wcvendors_pro_product_variations',10,3 );
 function scd_wcvendors_pro_product_variations( $path) {
            $path = __DIR__ .'/templates/scd_wcvendors_pro_product_variation.php';
        return $path;
    }

add_action( 'wcv_save_product_variation', 'scd_wcv_variation_product_save', 10, 2 );
function scd_wcv_variation_product_save( $variation_id, $i) {
$variable_regular_price         = $_POST['variable_regular_price'];
$variable_sale_price            = $_POST['variable_sale_price'];
$data['regular_price']=$variable_regular_price[$i];	
$data['sale_price']=$variable_sale_price[$i];
scd_save_product_prices($variation_id,$data);
}	












add_filter('wcv_orders_table_rows',function($rows){
    $user_curr =  scd_get_user_currency();
    foreach($rows as $key => $row){
        $before = stristr($row->total, '<span class="woocommerce-Price-amount amount"',true);
        $order = wc_get_order($row->ID);
        $order_curr = $order->get_currency();
        $total = $order->get_total();
        $args = ['currency' => $order_curr];
        $row->total = $before . scd_WCV_order_detail('',$total,$args,$total,$total);
    }
    return $rows;
});

//here we are in the orders's page of a vendor
add_filter( 'wcv_order_row_actions','scd_wcv_order_row_actions',10,2);
function scd_wcv_order_row_actions($row_actions, $orderget_order_number){
    add_filter('wc_price','scd_WCV_order_detail',999,4);//apply conversion of prices on 'wc_price'filter
    return $row_actions;
}


function scd_WCV_order_detail($return, $price, $args, $unformatted_price, $original_price=""){
    $args= wp_parse_args(
        $args,
        array(
            'ex_tax_label'       => false,
            'currency'           => '',
            'decimal_separator'  => wc_get_price_decimal_separator(),
            'thousand_separator' => wc_get_price_thousand_separator(),
            'decimals'           => wc_get_price_decimals(),
            'price_format'       => get_woocommerce_price_format(),
        )
    );

    $original_price = $price;

    // Convert to float to avoid issues on PHP 8.
    $price = (float) $price;
    $unformatted_price = $price;

    //convert price from customer currency to author currency
    $basecurrency =  $args['currency']; //client
    $vend_currency = get_user_meta(get_current_user_id(), 'scd-user-currency',true);
	if($vend_currency){
		$args['currency'] =  get_user_meta(get_current_user_id(), 'scd-user-currency',true);
		if($basecurrency != $vend_currency){
			$price = scd_function_convert_subtotal($price, $basecurrency, $vend_currency);
		}
	}
    

    $negative          = $price < 0;

    /**
     * Filter raw price.
     *
     * @param float        $raw_price      Raw price.
     * @param float|string $original_price Original price as float, or empty string. Since 5.0.0.
     */
    $price = apply_filters( 'raw_woocommerce_price', $negative ? $price * -1 : $price, $original_price );

    /**
     * Filter formatted price.
     *
     * @param float        $formatted_price    Formatted price.
     * @param float        $price              Unformatted price.
     * @param int          $decimals           Number of decimals.
     * @param string       $decimal_separator  Decimal separator.
     * @param string       $thousand_separator Thousand separator.
     * @param float|string $original_price     Original price as float, or empty string. Since 5.0.0.
     */
    $price = number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

    if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
        $price = wc_trim_zeros( $price );
    }

    $formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol( $args['currency'] ) . '</span>', $price );
    $return          = '<span class="woocommerce-Price-amount amount scd-converted"><bdi>' . $formatted_price . '<span class="woocommerce-Price-currencySymbol"> '.$args['currency'].'</span></bdi></span>';

    if ( $args['ex_tax_label'] && wc_tax_enabled() ) {
        $return .= ' <small class="woocommerce-Price-taxLabel tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
    }

    /**
     * Filters the string of price markup.
     *
     * @param string       $return            Price HTML markup.
     * @param string       $price             Formatted price.
     * @param array        $args              Pass on the args.
     * @param float        $unformatted_price Price as float to allow plugins custom formatting. Since 3.2.0.
     * @param float|string $original_price    Original price as float, or empty string. Since 5.0.0.
     */
    return  $return;
}


//convert prices in product table from basecurrenccy to vendor currency
add_filter( 'wcv_product_table_rows','scd_wcv_product_table_rows',10,1);
function scd_wcv_product_table_rows($new_rows ){
    foreach ( $new_rows as $new_row ) {

        $product = wc_get_product( $new_row->ID );
        $product_price      = wc_get_price_to_display( $product );
        $return='';
        $args=array();
        $unformatted_price='';
        $original_price='';
        $new_row->price       = scd_WCV_order_detail( $return,$product_price . $product->get_price_suffix(),$args, $unformatted_price, $original_price );
    }
    return $new_rows;
}


add_action( 'woocommerce_before_template_part', function( $template_name, $template_path, $located, $args){
    if($template_name == "overview.php" && $template_path == "wc-vendors/dashboard/reports/"){
        add_filter('wc_price','scd_WCV_order_detail',999,4);
    }
},999,4 );


//mail compatibility
add_action( 'wcvendors_email_order_details', function($order, $vendor_items, $totals_display, $vendor_id, $sent_to_vendor, $sent_to_admin, $plain_text, $email){
	$_SESSION['scd_wcv_vendor_currency'] = get_user_meta($vendor_id, 'scd-user-currency',true);
	add_filter('wc_price_args', 'scd_wcv_change_wc_price_args', 999, 1);
	add_filter('wc_price','scd_WCV_order_detail_email',999,4);
	
},1,8 );

add_action( 'wcvendors_email_customer_details', function($order, $sent_to_admin, $plain_text, $email ){
	
	unset($_SESSION['scd_wcv_vendor_currency']);
	remove_filter('wc_price_args', 'scd_wcv_change_wc_price_args', 999, 1);
	remove_filter('wc_price','scd_WCV_order_detail_email',999,4);
	
},1,4 );

function scd_wcv_change_wc_price_args($args){
	$args['currency'] =  scd_get_target_currency();
	return $args;
}

function scd_WCV_order_detail_email($return, $price, $args, $unformatted_price, $original_price=""){
    $args= wp_parse_args(
        $args,
        array(
            'ex_tax_label'       => false,
            'currency'           => '',
            'decimal_separator'  => wc_get_price_decimal_separator(),
            'thousand_separator' => wc_get_price_thousand_separator(),
            'decimals'           => wc_get_price_decimals(),
            'price_format'       => get_woocommerce_price_format(),
        )
    );

    $original_price = $price;

    // Convert to float to avoid issues on PHP 8.
    $price = (float) $price;
    $unformatted_price = $price;

    //convert price from customer currency to author currency
    $basecurrency =  $args['currency']; //client
    $vend_currency = $_SESSION['scd_wcv_vendor_currency'];
	$args['currency'] =  $vend_currency;
    if($basecurrency != $vend_currency){
        $price = scd_function_convert_subtotal($price, $basecurrency, $vend_currency);
    }

    $negative          = $price < 0;

    /**
     * Filter raw price.
     *
     * @param float        $raw_price      Raw price.
     * @param float|string $original_price Original price as float, or empty string. Since 5.0.0.
     */
    $price = apply_filters( 'raw_woocommerce_price', $negative ? $price * -1 : $price, $original_price );

    /**
     * Filter formatted price.
     *
     * @param float        $formatted_price    Formatted price.
     * @param float        $price              Unformatted price.
     * @param int          $decimals           Number of decimals.
     * @param string       $decimal_separator  Decimal separator.
     * @param string       $thousand_separator Thousand separator.
     * @param float|string $original_price     Original price as float, or empty string. Since 5.0.0.
     */
    $price = number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

    if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
        $price = wc_trim_zeros( $price );
    }

    $formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], '<span class="woocommerce-Price-currencySymbol">' . get_woocommerce_currency_symbol( $args['currency'] ) . '</span>', $price );
    $return          = '<span class="woocommerce-Price-amount amount scd-converted"><bdi>' . $formatted_price . '<span class="woocommerce-Price-currencySymbol"> '.$args['currency'].'</span></bdi></span>';

    if ( $args['ex_tax_label'] && wc_tax_enabled() ) {
        $return .= ' <small class="woocommerce-Price-taxLabel tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
    }

    /**
     * Filters the string of price markup.
     *
     * @param string       $return            Price HTML markup.
     * @param string       $price             Formatted price.
     * @param array        $args              Pass on the args.
     * @param float        $unformatted_price Price as float to allow plugins custom formatting. Since 3.2.0.
     * @param float|string $original_price    Original price as float, or empty string. Since 5.0.0.
     */
    return  $return;
}





// fix the convertion on wc vendors>commissions pages
add_filter( 'wcvendors_commissions_columns','scd_wcvendors_commissions_columns',999,1);
function scd_wcvendors_commissions_columns( $columns ){
	?>
	<script>
		if(typeof countscd == "undefined"){
			jQuery('document').ready(function(){
				var wait = setInterval(function(){
					if(jQuery('.woocommerce-Price-amount').length){
						
						var scd_prices_html = Array();
						jQuery('.woocommerce-Price-amount >bdi').each(function(){
							scd_prices_html.push(jQuery(this).html().split('</span>')[1]);
						});
						
						var scd_prices = Array();
						jQuery('.order_id >a').each(function(index){
			scd_prices.push(jQuery(this).html(),scd_prices_html[3*index],scd_prices_html[3*index+1],scd_prices_html[3*index+2]);						
						});
						jQuery.post("<?php echo admin_url('admin-ajax.php');?>",
							{
							'action': 'scd_convert_commission_ajax',
							'commissions': scd_prices,
							},
							function (response, status) {
								
								var elements = JSON.parse(response);
								jQuery('.woocommerce-Price-amount').each(function(index){
									jQuery(this).replaceWith(jQuery(elements[index]));
								});
							}
						);
						clearInterval(wait);
					}
				},500);
			});
		}
		var countscd = 0;
	</script>
	<?php
	return $columns;
}

add_action('wp_ajax_scd_convert_commission_ajax',function(){
	$prices = [];
	for($i =0; $i< count($_POST['commissions'])/4; $i++){
		$order = wc_get_order( intval($_POST['commissions'][4*$i]) );
		$curr = $order->get_currency();
		$rate = scd_get_conversion_rate($curr, get_option('woocommerce_currency'));
		$prices[] = wc_price($rate*floatval($_POST['commissions'][4*$i+1]));
		$prices[] = wc_price($rate*floatval($_POST['commissions'][4*$i+2]));
		$prices[] = wc_price($rate*floatval($_POST['commissions'][4*$i+3]));
	}
	echo json_encode($prices);
	die();
});


add_action('woocommerce_before_account_orders',function($order){
	add_filter('wc_price','scd_WCV_order_detail',999,4);
},10,1);

add_action( 'woocommerce_order_details_before_order_table', function($order ){
	add_filter('wc_price','scd_WCV_order_detail',999,4);
},10,1); 

?>





