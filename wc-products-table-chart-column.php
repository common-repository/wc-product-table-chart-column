<?php 
/*
Plugin Name: WC Product Table Chart Column
Description: This plugin show chart column in woocommerce products table.
Author: Amit Bhalani
Author URI: https://amitbhalani.wordpress.com/
Version: 1.0.1
*/

/* Define chart class */
class ABWCPROCHART{
	
	
	/* set the default constuctor */
	function __construct() {
		
		add_action( 'admin_enqueue_scripts', array($this, 'abwc_include_js_callback' ) );
		
		define( 'CUSTOM_PLUGIN_URL',	plugin_dir_url( __FILE__ ) );
		define( 'CUSTOM_DIR_PATH', plugin_dir_path( __FILE__ ) );
		
		add_action('admin_head', array($this,'abwc_custom_style'));
		add_filter( 'manage_product_posts_columns',  array($this,'abwc_page_column_views' ) );
		add_action( 'manage_product_posts_custom_column', array($this, 'abwc_page_custom_column_views') , 5 , 2 );
		
	}
	
	/**Add scripts and style in backend*/
	
	function abwc_include_js_callback(){
		
		wp_enqueue_script( 'sparkline-navigation', CUSTOM_PLUGIN_URL.'js/jquery.sparkline.min.js', array(), time() , false );
	
	}
	
	/**Add custom style in admin header*/
	function abwc_custom_style(){
		 echo '<style>
			.jqstooltip {
				width: auto!important;
				height: auto!important;
			}
			</style>';
	}
	
	/** Define column in product table list **/
	function abwc_page_column_views( $defaults ) {
		
	   $defaults['last_7_days_orders'] = __('Last 7 Days');
	   return $defaults;
	   
	}
	
	function abwc_page_custom_column_views( $column_name, $id ) {
		
		if ( $column_name === 'last_7_days_orders' ) {
			$result= array();
			$result2= array();
			for ($i = 7; $i >= 0; $i--)
			{
				$the_date = date('Y-m-d', strtotime("-".$i." days"));
				$count = $this->abwc_get_total_sold_by_product_id($the_date, $id);
				$revenue = $this->abwc_get_total_revenue_by_product_id($the_date, $id);
				if(!empty($count)){
					$count = $count;
				} else{
					$count = 0;
				}
				if(!empty($revenue)){
					$revenue = $revenue;
				} else{
					$revenue = 0;
				}
				$result[] = $count;
				$result2[] = $revenue;
				
			}
	
			echo '<div class="stat-chart" >
					<div id="sparkline_bar_'.$id.'">
					</div>
				</div><script>
				jQuery("#sparkline_bar_'.$id.'").sparkline(['.implode(',',$result).'], {
					type: "bar", barColor: "#aaf",
					width: "100",
					barWidth: 8,
					height: "21",
					tooltipFormat: "Order - {{value}}",
				});
				jQuery("#sparkline_bar_'.$id.'").sparkline(['.implode(',',$result2).'], {
					composite: true, fillColor: false, lineColor: "red",
					tooltipFormat: "Revenue - {{y}}",
				});
				</script>';
			
			
		}
		
	}
	
	/** Get total sales by product **/
	function abwc_get_total_sold_by_product_id($date_from, $product_id) {
		
		global $wpdb;
		
		$sql = "SELECT order_item_meta_2.meta_value as product_id, SUM( order_item_meta.meta_value ) as line_total,order_item_meta_3.max_price,SUM( order_item_meta.meta_value ) / order_item_meta_3.max_price as total_sales  FROM 
		{$wpdb->prefix}woocommerce_order_items as order_items 
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id 
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id 
		LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup as order_item_meta_3 ON order_item_meta_2.meta_value = order_item_meta_3.	product_id 
		LEFT JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID 
		WHERE posts.post_type = 'shop_order'
		AND DATE(posts.	post_date) = '".$date_from."'
		AND posts.post_status IN ( 'wc-completed','wc-processing','wc-on-hold' ) 
		AND order_items.order_item_type = 'line_item' 
		AND order_item_meta.meta_key = '_line_total' 
		AND order_item_meta_2.meta_key = '_product_id'
		AND product_id = ".$product_id."
		GROUP BY order_item_meta_2.meta_value";
		
		$result = $wpdb->get_row( $sql );
		return $result->total_sales;
		
	}

	/** Get total revenue by product **/
	function abwc_get_total_revenue_by_product_id( $date_from, $product_id ){
		
		global $wpdb;
		
		$sql = "SELECT order_item_meta_2.meta_value as product_id, SUM( order_item_meta.meta_value ) as line_total,order_item_meta_3.max_price,SUM( order_item_meta.meta_value ) / order_item_meta_3.max_price as total_sales  FROM 
		{$wpdb->prefix}woocommerce_order_items as order_items 
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id 
		LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id 
		LEFT JOIN {$wpdb->prefix}wc_product_meta_lookup as order_item_meta_3 ON order_item_meta_2.meta_value = order_item_meta_3.	product_id 
		LEFT JOIN {$wpdb->prefix}posts AS posts ON order_items.order_id = posts.ID 
		WHERE posts.post_type = 'shop_order'
		AND DATE(posts.	post_date) = '".$date_from."'
		AND posts.post_status IN ( 'wc-completed','wc-processing','wc-on-hold' ) 
		AND order_items.order_item_type = 'line_item' 
		AND order_item_meta.meta_key = '_line_total' 
		AND order_item_meta_2.meta_key = '_product_id'
		AND product_id = ".$product_id."
		GROUP BY order_item_meta_2.meta_value";
		
		$result = $wpdb->get_row( $sql );
		return $result->line_total;
		
	}
}
/*
 * Instantiate the class.
 */
$ABWCPROCHART = new ABWCPROCHART(); // go

?>