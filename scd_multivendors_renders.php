<?php

include 'scd_pro_currencies.php';

add_filter('scd_enable_js_conversion','scd_enable_js_conversion_func',10,1);
function scd_enable_js_conversion_func($enableJsConv) {
   $options=  get_option('scd_currency_options',true);
    if(isset($options['pricesInVendorCurrency'])&& !empty($options['pricesInVendorCurrency']))
        return false;
    return $enableJsConv;
}

add_filter('scd_multivendors_activate', 'scd_multivendors_activate_func',10,1);
function scd_multivendors_activate_func($scd_multi_activate) {
    return true;
}
function scd_check_license_active() {
    $opt_license_key = get_option('scd_license_key');
    $opt_license_start_date = get_option('scd_license_start_date');
    $opt_license_expiry_date = get_option('scd_license_expiry_date');

    if (empty($opt_license_key) && empty($opt_license_start_date) && !file_exists($GLOBALS['scd_license_file'])) {
        return FALSE;
    } else {
        if (!empty($opt_license_start_date)) {
            $startdate = new DateTime(base64_decode(get_option('scd_license_start_date')));
        } else if (file_exists($GLOBALS['scd_license_file'])) {
            $startdate = new DateTime(base64_decode(file_get_contents($GLOBALS['scd_license_file'])));
        } else { //only the license key varable remains
            return FALSE;
        }

        if(empty($opt_license_expiry_date) && is_admin()){
            scd_set_expiry($opt_license_key, $startdate);
            $opt_license_expiry_date = get_option('scd_license_expiry_date');
        }

        $todaydate = new DateTime(date('Y-m-d'));
        $duration = $startdate->diff($todaydate);

        if(!empty($opt_license_expiry_date)){
            $expirydate = new DateTime(base64_decode($opt_license_expiry_date));
            if($todaydate < $expirydate) {
                return TRUE;
            } else {    
                return FALSE;
            }
        }
        else{
            // For backward compatibility with older activations prior to 4.5.2 
            if ($duration->days > $GLOBALS['scd_license_duration']) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }
}


function scd_get_user_currency() {
       $user_curr= get_user_meta(get_current_user_id(), 'scd-user-currency',true);
        if( $user_curr){
        return $user_curr;
        } else {
        $default_curr = get_option( 'woocommerce_currency');
        return $default_curr;  
        }
   }

function scd_get_user_currency_option() {
       $curr_opt= get_user_meta(get_current_user_id(), 'user-currency-option');
        if(count($curr_opt)>0){
        return $curr_opt[0];
        } else {
            return 'only-default-currency';
        }
   }
   
   add_action( 'wp_ajax_scd_show_user_currency', 'scd_show_user_currency' );
    function scd_show_user_currency(){
        $options=array(
            'base-currency' => 'Base currency only',
            'only-default-currency' => 'Your default currency only'/*,
            'base-and-default-currency' => 'Base and default currency',
            'selected-currencies' => 'Selected currencies'*/
        );
        //echo '<a href="#" class="button"  id="scd-conv-price">SCD Currencies</a>';
        //echo  '<p id="scd-pp" style="display: none;">';    
        echo '<div class="scd-choose-curr" style="margin-left:15%;margin-top:70px; backgound-color:red;">';
        ?>
<p id="scd-action-status" style="margin-left:15%;"></p>
        <p style="color: black;">Select your default currency</p>
        <select id="scd-currency-list" class="scd-user-curr" style="width: 58%;">
            <?php
            $user_curr=scd_get_user_currency();
            //if($user_curr!==FALSE) $user_curr=$user_curr[0];
            foreach (scd_get_list_currencies() as $key => $val) {
                if($user_curr==$key){
                    echo '<option selected value="'.$key.'" >'.$key.'('.  get_woocommerce_currency_symbol($key).')</option>';
                } else {
                    echo '<option value="'.$key.'" >'.$key.'('.get_woocommerce_currency_symbol($key).')</option>';
             }
             }
            ?>
        </select>
     <?php
     
         //echo '<a  style="color:black;" class="button" href="#" id="scd-save-curr">Save change<a>';
         echo '<br><br>';
         echo '<p style="color: black;">Set products price in</p>';
         ?>
        
        <select id="scd-currency-option" class="scd-user-curr" style="width: 58%;">
            <?php
            $currency_opt=scd_get_user_currency_option();        
            foreach ($options as $key => $val) {
                if($currency_opt==$key){
                    echo '<option selected value="'.$key.'" >'.$val.'</option>';
                } else {
                    echo '<option value="'.$key.'" >'.$val.'</option>';
             }
             }
            ?>
        </select>
     <?php
     echo '<br><br>';
     echo '<a  style="color:black;" class="button" href="#" id="scd-save-currency-option">Save change<a>';
         echo '</p></div>';
         die();
    }
    
add_action( 'wp_ajax_scd_wcv_get_user_currency', 'scd_wcv_get_user_currency' );
    function scd_wcv_get_user_currency() {
           $user_curr= get_user_meta(get_current_user_id(), 'scd-user-currency',true);
            if( $user_curr){
				echo $user_curr;
			 return $user_curr;
            } else {
				$wo_currency = get_option( 'woocommerce_currency');
				echo $wo_currency ;
                return $wo_currency;    
            }
       }


     add_action( 'wp_ajax_scd_update_user_currency', 'scd_update_user_currency' );
    function scd_update_user_currency(){
        if(isset($_POST['user_currency'])){
            
            update_user_meta(get_current_user_id(), 'scd-user-currency', $_POST['user_currency']);
            echo 'Information saved. Your new custom currency is '. get_user_meta(get_current_user_id(), 'scd-user-currency')[0];
        } else {
            echo 'Currency not saved please try again';    
        }
        die();
    }
     add_action( 'wp_ajax_scd_update_user_currency_option', 'scd_update_user_currency_option' );
    function scd_update_user_currency_option(){
        if(isset($_POST['user_currency_option'])){
            
            update_user_meta(get_current_user_id(), 'user-currency-option', $_POST['user_currency_option']);
            echo 'Information saved';
        } else {
            echo 'Option not saved please try again';    
        }
        die();
    }
 
    //when vendor is connected set the target currency to his default currency
function scd_multivendor_currency($scd_target_currency) {

	if(!is_product() && !is_shop() && !is_cart() && !is_checkout()){
	  $user_currency=scd_get_user_currency();
	  if($user_currency !== false){
		$scd_target_currency=$user_currency;
	  }
	  return $scd_target_currency;
	}
	
	return $scd_target_currency;

}
 add_filter('scd_target_currency','scd_multivendor_currency',10,1);
 
 //export import products with woocommerce
    
add_filter( 'woocommerce_product_export_column_names', 'scd_add_export_column' );
add_filter( 'woocommerce_product_export_product_default_columns', 'scd_add_export_column' );
    function scd_add_export_column( $columns ) {

    // column slug => column name
    $columns['scd_other_options'] = 'Meta: scd_other_options';

    return $columns;
}

function scd_add_export_data( $value, $product ) {
    $value = get_post_meta($product->get_id(), 'scd_other_options', true);
   
    return serialize($value);
}
// Filter you want to hook into will be: 'woocommerce_product_export_product_column_{$column_slug}'.
add_filter( 'woocommerce_product_export_product_column_scd_other_options', 'scd_add_export_data', 10, 2 );

// Hook into the filter
add_filter("woocommerce_product_importer_parsed_data", "scd_csv_import_serialized", 10, 2);
function scd_csv_import_serialized($data, $importer) {
  if (isset($data["meta_data"]) && is_array($data["meta_data"])) {
    foreach (array_keys($data["meta_data"]) as $k) {
      $data["meta_data"][$k]["value"] = maybe_unserialize($data["meta_data"][$k]["value"]);
    }
  }
  return $data;
}


    //filter in the free version
 add_filter('is_scd_multivendor','is_scd_multivendor',10,1);
 function is_scd_multivendor($multi) {
    return true;
}

 add_filter('scd_disable_sidebar_currencies','fct_scd_disable_sidebar_currencies',10,1);
 function fct_scd_disable_sidebar_currencies() {
    return false;
}


add_action( 'wcv_pro_store_settings_saved', 'scd_wcv_store_shipping_fix',10,1);
function scd_wcv_store_shipping_fix($vendor_id)
{
	$shipping_scd_fee_national = ( isset( $_POST['_scd_wcv_shipping_fee_national'] ) ) ? wc_format_decimal( $_POST['_scd_wcv_shipping_fee_national'] ) : '';
	$shipping_scd_fee_international = ( isset( $_POST['_scd_wcv_shipping_fee_international'] ) ) ? wc_format_decimal( $_POST['_scd_wcv_shipping_fee_international'] ) : '';
	$shipping_scd_flate_rate = ( isset( $_POST['_scd_wcv_shipping_fees'] ) ) ? wc_format_decimal( $_POST['_scd_wcv_shipping_fees'] ) : '';
	update_post_meta($vendor_id, '_scd_wcv_shipping_fee_national', $shipping_scd_fee_national);
	update_post_meta($vendor_id, '_scd_wcv_shipping_fee_international', $shipping_scd_fee_international);
	update_post_meta($vendor_id, '_scd_wcv_shipping_fees', $shipping_scd_flate_rate);
}


add_action('wcv_after_shipping_tab','scd_wcv_shipping_product_fix',10,1);
function scd_wcv_shipping_product_fix($object_id){
	$price1 = get_post_meta($object_id, '_scd_shipping_fee_national', TRUE);
	$price2 = get_post_meta($object_id, '_scd_shipping_fee_international', TRUE);
	?>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		var rate = '<?php echo scd_get_conversion_rate(get_option('woocommerce_currency'), scd_get_user_currency())?>';

		const field1 = document.querySelector('#_shipping_fee_national')
		const field2 = document.querySelector('#_shipping_fee_international')
		const scdField1 = document.createElement('input')
		const scdField2 = document.createElement('input')
		const block = document.querySelector('#shipping')
		const button = document.querySelector('#product_save_button')
		const price1 = '<?php echo $price1; ?>'
		const price2 = '<?php echo $price2; ?>'

		scdField1.type = 'hidden'
		scdField2.type = 'hidden'

		scdField1.name = '_shipping_fee_national'
		scdField2.name = '_shipping_fee_international'
		

		block.appendChild(scdField1)
		block.appendChild(scdField2)

		field1.name = '_scd_shipping_fee_national'
		field2.name = '_scd_shipping_fee_international'

		field1.value = price1 !=="" ? price1 : field1.value;
		field2.value = price2 !=="" ? price2 : field2.value;

		button.addEventListener('click',function(){ 
			scdField1.value = field1.value / rate
			scdField2.value = field2.value / rate
		})

	}, false);
</script>
<?php
}

add_action( 'wcvendors_settings_after_shipping_tab', 'scd_wcv_shipping_fix',10);
function scd_wcv_shipping_fix()
{
	$vendor_id = get_current_user_id();
	$price1 = get_post_meta($vendor_id, '_scd_wcv_shipping_fee_national', TRUE);
	$price2 = get_post_meta($vendor_id, '_scd_wcv_shipping_fee_international', TRUE);
	$flat_rate = get_post_meta($vendor_id, '_scd_wcv_shipping_fees', TRUE);
?>
<script>
	document.addEventListener('DOMContentLoaded', function () {
		var rate = '<?php echo scd_get_conversion_rate(get_option('woocommerce_currency'), scd_get_user_currency())?>';

		const field1 = document.querySelector('#_wcv_shipping_fee_national')
		const field2 = document.querySelector('#_wcv_shipping_fee_international')
		const scdField1 = document.createElement('input')
		const scdField2 = document.createElement('input')
		const block = document.querySelector('#shipping-flat-rates')
		const button = document.querySelector('#store_save_button')
		const price1 = '<?php echo $price1; ?>'
		const price2 = '<?php echo $price2; ?>'
		const flat_rate = <?php echo json_encode($flat_rate); ?>
		
		

		scdField1.type = 'hidden'
		scdField2.type = 'hidden'

		scdField1.name = '_wcv_shipping_fee_national'
		scdField2.name = '_wcv_shipping_fee_international'
		

		block.appendChild(scdField1)
		block.appendChild(scdField2)

		field1.name = '_scd_wcv_shipping_fee_national'
		field2.name = '_scd_wcv_shipping_fee_international'

		field1.value = price1 !=="" ? price1 : field1.value;
		field2.value = price2 !=="" ? price2 : field2.value;
		
		/**fix ship Country rate option*/
		vals1 = document.querySelectorAll("input[name='_wcv_shipping_fees[]']");
		for (i = 0; i < vals1.length; i++) {
			vals1[i].value = flat_rate[i];
		}

		button.addEventListener('click',function(){ 
			scdField1.value = field1.value / rate
			scdField2.value = field2.value / rate
			
			/**Flat Country rate fix*/
			const nodeList = (document.querySelectorAll("input[name='_wcv_shipping_fees[]']"));
			for (let i= 0; i< nodeList.length; i++){
				const hiddeField = document.createElement('input')
				hiddeField.name = '_wcv_shipping_fees[]'
				hiddeField.type = 'hidden'
				hiddeField.value = nodeList[i].value / rate
				
				nodeList[i].value =  nodeList[i].value
				nodeList[i].name  =  '_scd_wcv_shipping_fees[]'
				block.appendChild(hiddeField);
			}
			/**End Flat Country rates fix*/
		})

	}, false);
</script>
<?php
}

//add_action('woocommerce_after_single_product','scd_wcv_fix_shipping_display_in_store',10);
function scd_wcv_fix_shipping_display_in_store(){
	global $post;
	$vendor_id = get_post_field( 'post_author', $post->ID);
	$price1 = get_post_meta($vendor_id, '_scd_wcv_shipping_fee_national', TRUE);
	$price2 = get_post_meta($vendor_id, '_scd_wcv_shipping_fee_international', TRUE);
	$user_curr = get_user_meta($vendor_id, 'scd-user-currency', true);
	$rate = scd_get_conversion_rate($_SESSION['scd_target_currency'], $user_curr);
	if(isset($_SESSION['scd_target_currency'])){
		$rate = scd_get_conversion_rate($_SESSION['scd_target_currency'], $user_curr);
	}
?>
	<script>
		document.addEventListener('DOMContentLoaded',function(){
			const line  = document.querySelectorAll('#tab-wcv_shipping_tab tr')
			const price1 = '<?php echo $price1; ?>'
			const price2 = '<?php echo $price2; ?>'	
			const rate = '<?php echo $rate; ?>'	
			line[1].lastElementChild.innerText = price1 !== "" ? price1*rate : line[1].lastElementChild.innerText
			line[2].lastElementChild.innerText = price2 !== "" ? price2*rate : line[2].lastElementChild.innerText
			
			/*for(var i=1; i<line.length; i++){
				line[i].lastElementChild.innerText = '10';
			}*/
		},false)
	</script>
<?php
}