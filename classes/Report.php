<?php
/**
 * Plugin Class File
 *
 * Created:   August 15, 2017
 *
 * @package:  Product Report
 * @author:   Max Strukov
 * @since:    1.0.0
 */

namespace MillerMedia\ProductReport;

use \Modern\Wordpress\Pattern\Singleton;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/**
 * Report Class
 *
 * @Wordpress\AdminPage( title="Create Product Report", menu="Cal's Order Report", slug="product_report", icon="dashicons-admin-generic" )
 */
class Report extends Singleton
{
	
	/**
	 * Instance Cache - Required for singleton
	 * @var	self
	 */
	protected static $_instance;
	
	
	/**
	 * @var 	\Modern\Wordpress\Plugin		Provides access to the plugin instance
	 */
	protected $plugin;
	
	private $header_titles;
	private static $wholesale_category = 28;
	private $general_rows = array();
	private $discount_rows = array();
	
	/**
 	 * Get plugin
	 *
	 * @return	\Modern\Wordpress\Plugin
	 */
	public function getPlugin()
	{
		return $this->plugin;
	}
	
	/**
	 * Set plugin
	 *
	 * @return	this			Chainable
	 */
	public function setPlugin( \Modern\Wordpress\Plugin $plugin=NULL )
	{
		$this->plugin = $plugin;
		return $this;
	}
	
	/**
	 * Constructor
	 *
	 * @param	\Modern\Wordpress\Plugin	$plugin			The plugin to associate this class with, or NULL to auto-associate
	 * @return	void
	 */
	public function __construct( \Modern\Wordpress\Plugin $plugin=NULL )
	{
		$this->setPlugin( $plugin ?: \MillerMedia\ProductReport\Plugin::instance() );
		
		$this->header_titles = array('Order#', 'Date', 'Customer E-mail', 'Customer', 'Customer Address 1', 'Customer Address 2', 'Customer City, State, Zip', 'Customer Country (if not USA)', 'Status', 'Taxes', 'Shipping', 'Total', 'Line Items');
		
	}
	
	public function do_index() {
		
		if (isset($_POST["start_date"]) && isset($_POST["end_date"])) {
			$start_date = date('Y-m-d', strtotime($_POST['start_date']));
			$end_date = date('Y-m-d', strtotime($_POST['end_date']));
			if (isset($_POST["general_csv"])) {
				$this->get_general_report($start_date, $end_date, $_POST["order_status"]);
				// Assemble the filename for the report download
				$filename =  'General report - from '.date('Y-m-d', strtotime($_POST['start_date'])).' to '.date('Y-m-d', strtotime($_POST['end_date'])).'.csv';
				ob_end_clean();
				// Send headers
				header('Content-Type: text/csv; charset=utf-8; encoding=utf-8');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				// Output the report header row (if applicable) and body
				$stdout = fopen('php://output', 'w');
				fputcsv($stdout, $this->header_titles);
				foreach ($this->general_rows as $row):
					$row['items'] = str_replace('<br/>', ';', $row['items']);
					$row['total'] = html_entity_decode(strip_tags($row['total']));
					fputcsv($stdout, $row);
				endforeach;
				exit;
			}
			
			if (isset($_POST["discount"])) {
				$this->get_discount_report($start_date, $end_date, $_POST["order_status"]);
				// Assemble the filename for the report download
				$filename =  'Discount report - from '.date('Y-m-d', strtotime($_POST['start_date'])).' to '.date('Y-m-d', strtotime($_POST['end_date'])).'.csv';
				ob_end_clean();
				// Send headers
				header('Content-Type: text/csv; charset=utf-8; encoding=utf-8');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				// Output the report header row (if applicable) and body
				$stdout = fopen('php://output', 'w');
				foreach ($this->discount_rows as $row):
					$row[1] = html_entity_decode(strip_tags($row[1]));
					fputcsv($stdout, $row);
				endforeach;
				exit;
			}

		}
		
		$template_content = $this->getPlugin()->getTemplateContent( 'views/CreateReport',
			array(
				'page_title' => 'Create Report',
				'header_titles' => $this->header_titles,
				'order_statuses' => wc_get_order_statuses(),
				'general_rows' => $this->general_rows,
				'discount_rows' => $this->discount_rows
			)
		);
		
		echo $template_content;
	}
	
	private function get_general_report($date_from, $date_to, $order_statuses) {
		global $wpdb;
		if ($order_statuses) $status = "AND post_status IN ('".implode("','", $order_statuses)."')";
		else $status = '';
		$results = $wpdb->get_results( "SELECT ID, post_date,post_status FROM ".$wpdb->prefix."posts 
		WHERE post_type = 'shop_order' ".$status." 
		AND post_date BETWEEN '".$date_from." 00:00:00' AND '".$date_to." 23:59:59'");
		foreach ($results as $result) {
			$row = array();
			$row["order_id"] = $result->ID;
			$row["date"] = date("m/d/Y", strtotime($result->post_date));
			$order = new \WC_Order($result->ID);
			$row["email"] = $order->get_billing_email();
			$row["customer"] = $order->get_billing_first_name()." ".$order->get_billing_last_name();
			$row["address1"] = $order->get_billing_address_1();
			$row["address2"] = $order->get_billing_address_2();
			$row["city,state,zip"] = $order->get_billing_city().", ".$order->get_billing_state()." ".$order->get_billing_postcode();
			$row["country"] = ($order->get_billing_country()=="US") ? "" : $order->get_billing_country();
			$row["status"] = $result->post_status;
			$row['taxes'] = $order->get_total_tax();
			$row['shipping'] = $order->get_total_shipping();
			$row['total'] = $order->get_formatted_order_total();
			$order_items = $order->get_items();
			$line_items = "";
			foreach ($order_items as $item_id => $item_data) {
				$product_name = $item_data['name'];
				$item_quantity = wc_get_order_item_meta($item_id, '_qty', true);
				if (!empty($line_items)) $line_items .= "<br/>";
				$line_items .= $product_name." x ".$item_quantity;
			}
			$row['items'] = $line_items;

			$this->general_rows[] = $row;
		}
	}
	
	private function get_discount_report($date_from, $date_to, $order_statuses) {
		
		$args = array(
			'post_type' => 'shop_order',
			'post_status' => $order_statuses,
			'date_query'    => array(
				'after'   => $date_from." 00:00:00",
				'before' => $date_to." 23:59:59"
			),
			'orderby' => 'ID',
			'order' => 'ASC',
			'posts_per_page' => -1
		);
		$total_sales = 0;
		$total_wholesale_discount = 0;
		$total_wholesale_sales = 0;
		$the_query = new \WP_Query($args);
		if ($the_query->have_posts()) :
			while ($the_query->have_posts()) : $the_query->the_post();
				$order = wc_get_order(get_the_id());
				$total_sales += $order->get_total();
				$items = $order->get_items();
				foreach ($items as $item_id => $item) {
					$product = wc_get_product($item['product_id']);
					$categories = wp_get_post_terms($product->get_id(), 'product_cat', array('fields'=>'ids'));
					if (in_array(self::$wholesale_category, $categories)) {
						$total_wholesale_discount += ($item['total']/0.9 - $item['total']);
						$total_wholesale_sales += $item['total'];
					}
				}
			endwhile;
		endif;
		
		$this->discount_rows = array(
			array("Total Sales", get_woocommerce_currency_symbol().$total_sales),
			array("Total Wholesale Discount", get_woocommerce_currency_symbol().$total_wholesale_discount),
			array("Total Wholesale Sales", get_woocommerce_currency_symbol().$total_wholesale_sales)
		);
		
	}
	
}