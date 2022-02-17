<?php
/**
 * The template for displaying the vendor store graphs, recent products and recent orders
 *
 * Override this template by copying it to yourtheme/wc-vendors/dashboard/report
 *
 * @package    WCVendors_Pro
 * @version    1.2.5
 */
?>

<div class="wcv_reports wcv-cols-group wcv-horizontal-gutters">

	<div class="all-50 small-100 tiny-100">
		<br/>
		<h3><?php _e( 'Orders Totals', 'wcvendors-pro' ); ?> ( <?php echo $store_report->total_orders; ?> )</h3>
		<hr/>
		<?php $order_chart_data = $store_report->get_order_chart_data(); ?>

		<?php if ( ! $order_chart_data ) : ?>
			<p><?php _e( 'No orders for this period. Adjust your dates above and click Update, or list new products for customers to buy.', 'wcvendors-pro' ); ?></p>
		<?php else : ?>
			<canvas id="orders_chart" width="350" height="200"></canvas>
			<script type="text/javascript">
				var orders_chart_label = <?php echo $order_chart_data['labels']; ?>;
				var orders_chart_data = <?php echo $order_chart_data['data']; ?>;
			</script>

		<?php endif; ?>
	</div>

	<div class="all-50 small-100 tiny-100">
		<br/>
		<h3><?php _e( 'Product totals', 'wcvendors-pro' ); ?> ( <?php echo $store_report->total_products_sold; ?> )</h3>
		<hr/>
		<?php $product_chart_data = $store_report->get_product_chart_data(); ?>

		<?php if ( ! $product_chart_data ) : ?>
			<p><?php _e( 'No sales for this period. Adjust your dates above and click Update, or list new products for customers to buy.', 'wcvendors-pro' ); ?></p>
		<?php else : ?>

			<canvas id="products_chart" width="350" height="150"></canvas>
			<script type="text/javascript">var pieData = <?php echo $product_chart_data; ?></script>

		<?php endif; ?>
	</div>

</div>

<div class="wcv_recent wcv_recent_orders wcv-cols-group wcv-horizontal-gutters">
	<div class="xlarge-50 large-50 medium-100 small-100 tiny-100">
		<h3><?php _e( 'Recent orders', 'wcvendors-pro' ); ?></h3>
		<hr/>
		<?php 
		
			$recent_orders = scd_wcv_recent_orders_table($store_report); 

		?>
		<?php if ( ! $orders_disabled ) : ?>
			<?php if ( ! empty( $recent_orders ) ) : ?>
				<a href="<?php echo WCVendors_Pro_Dashboard::get_dashboard_page_url( 'order' ); ?>"
				   class="wcv-button button"><?php _e( 'View all', 'wcvendors-pro' ); ?></a>
			<?php endif; ?>
		<?php endif; ?>
	</div>


	<div class="xlarge-50 large-50 medium-100 small-100 tiny-100">
		<h3><?php _e( 'Recent products', 'wcvendors-pro' ); ?></h3>
		<hr/>
		<?php $recent_products = $store_report->recent_products_table(); ?>
		<?php if ( ! $products_disabled ) : ?>
			<?php if ( ! empty( $recent_products ) ) : ?>
				<a href="<?php echo WCVendors_Pro_Dashboard::get_dashboard_page_url( 'product' ); ?>"
				   class="wcv-button button"><?php _e( 'View all', 'wcvendors-pro' ); ?></a>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<?php

function scd_wcv_recent_orders_table($store_report){

	$shipping_disabled = wc_string_to_bool( get_option( 'wcvendors_shipping_management_cap', 'no' ) );

	// Get the last 10 recent orders
	$max_orders = apply_filters( 'wcv_recent_orders_max', 9 );

	$recent_orders = array_splice( $store_report->orders, 0, $max_orders );

	// Create recent orders table
	$recent_order_table = new WCVendors_Pro_Table_Helper( 'wcvendors-pro', WCV_PRO_VERSION , 'recent_order', null, get_current_user_id() );

	$recent_order_table->container_wrap = false;

	// Set the columns
	$columns = array(
		'ID'           => __( 'ID', 'wcvendors-pro' ),
		'order_number' => __( 'Order', 'wcvendors-pro' ),
		'product'      => __( 'Products', 'wcvendors-pro' ),
		'totals'       => __( 'Totals', 'wcvendors-pro' ),
		'commission'   => __( 'Commission', 'wcvendors-pro' ),
	);
	$recent_order_table->set_columns( $columns );

	// Set the rows
	$rows = array();
	if ( ! empty( $recent_orders ) ) {

		foreach ( $recent_orders as $order ) {

			$products_html   = '';
			$totals_html     = '';
			$commission_html = '';
			$total_products  = 0;
			$args = array('currency' => $order->order->get_currency());
			// Make sure the order exists before attempting to loop over it.
			if ( is_object( $order->order ) ) {
				if ( is_array( $order->order_items ) ) {
					$total_products = count( $order->order_items );

					// Get products to output
					foreach ( $order->order_items as $key => $item ) {
						$where            = array(
							'vendor_id'  => get_current_user_id(),
							'order_id'   => $order->order_id,
							'product_id' => $item->get_product_id(),
						);
						$count_paid       = WCV_Commission::check_commission_status( $where, 'paid' );
						$commission_html .= ( 0 == $count_paid ? '<strong>' . __( 'Due', 'wcvendors-pro' ) . '</strong>' : '<strong>' . __( 'Paid', 'wcvendors-pro' ) . '</strong>' );
						// May need to fix for variations
						$products_html  .= '<strong>' . $item['qty'] . ' x ' . $item['name'] . '</strong>';
						$item_product_id = $item->get_product_id();

					
						$totals_html .= wc_price( $order->product_commissions[ $item_product_id ] , $args);
						if ( $total_products > 1 ) {
							$products_html   .= '<br />';
							$commission_html .= '<br />';
							$totals_html     .= '<br />';
						}
					}
				}
			}

			if ( ! $shipping_disabled ) {

				$products_html .= ( $total_products == 1 ) ? '<br /><strong>' . __( 'Shipping', 'wcvendors-pro' ) . '</strong>' : '<strong>' . __( 'Shipping', 'wcvendors-pro' ) . '</strong>';

				$totals_html .= ( $total_products == 1 ) ? '<br />' . wc_price( $order->total_shipping ) : wc_price( $order->total_shipping ,$args);
			}

			$new_row = new stdClass();

			$new_row->ID           = $order->order_id;
			$new_row->order_number = $order->order->get_order_number() . '<br />' . date_i18n( get_option( 'date_format', 'F j, Y' ), ( $order->order->get_date_created()->getOffsetTimestamp() ) );
			$new_row->product      = $products_html;
			$new_row->totals       = $totals_html;
			$new_row->commission   = $commission_html;

			$rows[] = $new_row;

		}
	}

	$recent_order_table->set_rows( $rows );

	// Disable row actions
	$recent_order_table->set_actions( array() );

	// display the table
	$recent_order_table->display();
	
	return $recent_orders;

}