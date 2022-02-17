<?php
/**
 * The template for displaying the vendor store information including total sales, orders, products and commission
 *
 * Override this template by copying it to yourtheme/wc-vendors/dashboard/report
 *
 * @package    WCVendors_Pro
 * @version    1.4.4
 */
$give_tax = 'yes' == get_option( 'wcvendors_vendor_give_taxes', 'no' ) ? true : false;


$store_report->commission_due           = 0;
$store_report->commission_paid          = 0;
$store_report->commission_shipping_due  = 0;
$store_report->commission_shipping_paid = 0;
$store_report->commission_tax_due       = 0;
$store_report->commission_tax_paid      = 0;
$store_report->total_products_sold      = 0;

$wcv_orders = $store_report->get_filtered_orders();
$vend_curr= get_user_meta(get_current_user_id(), 'scd-user-currency',true);
// Create the cumulative totals for commissions and products
foreach ( $wcv_orders as $wcv_order ) {
	$order_curr = $wcv_order->order->get_currency();
	$rate = scd_get_conversion_rate($order_curr, $vend_curr);
	
	if ( $wcv_order->status == 'due' ) {
		$store_report->commission_due          += $rate*$wcv_order->total_due;
		$store_report->commission_shipping_due += $rate*$wcv_order->total_shipping;
		$store_report->commission_tax_due      += $rate*$wcv_order->total_tax;
	} elseif ( $wcv_order->status == 'paid' ) {
		$store_report->commission_paid          += $rate*$wcv_order->total_due;
		$store_report->commission_shipping_paid += $rate*$wcv_order->total_shipping;
		$store_report->commission_tax_paid      += $rate*$wcv_order->total_tax;
	}

	$store_report->total_products_sold += $wcv_order->qty;
}

$commission_due_total  = ( $give_tax ) ? $store_report->commission_due + $store_report->commission_shipping_due + $store_report->commission_tax_due : $store_report->commission_due + $store_report->commission_shipping_due;
$commission_paid_total = ( $give_tax ) ? $store_report->commission_paid + $store_report->commission_shipping_paid + $store_report->commission_tax_paid : $store_report->commission_paid + $store_report->commission_shipping_paid;

?>


<div class="wcv_dashboard_datepicker wcv-cols-group">

	<div class="all-100">
		<hr/>
		<form method="post" action="" class="wcv-form  wcv-form-exclude">
			<?php $store_report->date_range_form(); ?>
		</form>
	</div>
</div>

<div class="wcv_dashboard_overview wcv-cols-group wcv-horizontal-gutters">

	<div class="xlarge-50 large-50 medium-100 small-100 tiny-100">
		<h3><?php _e( 'Commission Due', 'wcvendors-pro' ); ?></h3>
		<table role="grid" class="wcvendors-table wcvendors-table-recent_order wcv-table">

			<tbody>
			<tr>
				<td><?php _e( 'Products', 'wcvendors-pro' ); ?></td>
				<td><?php echo wc_price( $store_report->commission_due,array('currency'=>$vend_curr) ); ?></td>
			</tr>
			<tr>
				<td><?php _e( 'Shipping', 'wcvendors-pro' ); ?></td>
				<td><?php echo wc_price( $store_report->commission_shipping_due,array('currency'=>$vend_curr) ); ?></td>
			</tr>
			<?php if ( $give_tax ) : ?>
				<tr>
					<td><?php _e( 'Tax', 'wcvendors-pro' ); ?></td>
					<td><?php echo wc_price( $store_report->commission_tax_due ,array('currency'=>$vend_curr)); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<td><strong><?php _e( 'Totals', 'wcvendors-pro' ); ?></strong></td>
				<td><?php echo wc_price( $commission_due_total,array('currency'=>$vend_curr) ); ?></td>
			</tr>
			</tbody>

		</table>
	</div>

	<div class="xlarge-50 large-50 medium-100 small-100 tiny-100">
		<h3><?php _e( 'Commission paid', 'wcvendors-pro' ); ?></h3>
		<table role="grid" class="wcvendors-table wcvendors-table-recent_order wcv-table">
			<tbody>
			<tr>
				<td><?php _e( 'Products', 'wcvendors-pro' ); ?></td>
				<td><?php echo wc_price( $store_report->commission_paid,array('currency'=>$vend_curr) ); ?></td>
			</tr>
			<tr>
				<td><?php _e( 'Shipping', 'wcvendors-pro' ); ?></td>
				<td><?php echo wc_price( $store_report->commission_shipping_paid,array('currency'=>$vend_curr) ); ?></td>
			</tr>
			<?php if ( $give_tax ) : ?>
				<tr>
					<td><?php _e( 'Tax', 'wcvendors-pro' ); ?></td>
					<td><?php echo wc_price( $store_report->commission_tax_paid, array('currency'=>$vend_curr)); ?></td>
				</tr>
			<?php endif; ?>
			<tr>
				<td><strong><?php _e( 'Totals', 'wcvendors-pro' ); ?></strong></td>
				<td><?php echo wc_price( $commission_paid_total ,array('currency'=>$vend_curr)); ?></td>
			</tr>
			</tbody>

		</table>
	</div>

</div>
