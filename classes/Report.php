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
 * @Wordpress\AdminPage( title="Create Product Report", menu="Pruduct Report", slug="product_report", icon="dashicons-admin-generic" )
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
	public $rows = array();
	
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
		
		$this->header_titles = array('Order#', 'Customer E-mail', 'Customer Address', 'Date', 'Line Items', 'Taxes', 'Shipping', 'Total');
	}
	
	public function do_index() {
		
		if (isset($_POST["start_date"]) && isset($_POST["end_date"])) {
			$this->get_rows();
			if (isset($_POST["download"])) {
				// Assemble the filename for the report download
				$filename =  'Product Sales - from '.date('Y-m-d', strtotime($_POST['start_date'])).' to '.date('Y-m-d', strtotime($_POST['end_date'])).'.csv';
				ob_end_clean();
				// Send headers
				header('Content-Type: text/csv; charset=utf-8; encoding=utf-8');
				header('Content-Disposition: attachment; filename="'.$filename.'"');
				
				// Output the report header row (if applicable) and body
				$stdout = fopen('php://output', 'w');
				//$this->export_header($stdout);
				//$this->export_body($stdout);
				fputcsv($stdout, $this->header_titles);
				foreach ($this->rows as $row):
					$row['address'] = str_replace('<br/>', ';', $row['address']);
					$row['items'] = str_replace('<br/>', ';', $row['items']);
					$row['total'] = html_entity_decode(strip_tags($row['total']));
					fputcsv($stdout, $row);
				endforeach;
				exit;
			}
		}
		
		$template_content = $this->getPlugin()->getTemplateContent( 'views/CreateReport',
			array(
				'page_title' => 'Create Report',
				'header_titles' => $this->header_titles,
				'rows' => $this->rows
			)
		);
		
		echo $template_content;
	}
	
	private function get_rows() {
		global $wpdb;
		$post_status = implode("','", array('wc-on-hold', 'wc-processing', 'wc-completed') );
		$results = $wpdb->get_results( "SELECT ID, post_date FROM ".$wpdb->prefix."posts 
		WHERE post_type = 'shop_order'
		AND post_status IN ('".$post_status."')
		AND post_date BETWEEN '".$_POST["start_date"]." 00:00:00' AND '".$_POST["end_date"]." 23:59:59'");
		foreach ($results as $result) {
			$row = array();
			$row["order_id"] = $result->ID;
			$order = new \WC_Order($result->ID);
			$row["email"] = $order->get_billing_email();
			$row["address"] = $order->get_formatted_billing_address();
			$row["date"] = date("Y-m-d", strtotime($result->post_date));
			
			$order_items = $order->get_items();
			$line_items = "";
			foreach ($order_items as $item_id => $item_data) {
				$product_name = $item_data['name'];
				$item_quantity = wc_get_order_item_meta($item_id, '_qty', true);
				//$item_total = $order->get_item_meta($item_id, '_line_total', true);
				if (!empty($line_items)) $line_items .= "<br/>";
				$line_items .= $product_name." x ".$item_quantity;
			}
			$row['items'] = $line_items;
			$row['taxes'] = $order->get_total_tax();
			$row['shipping'] = $order->get_total_shipping();
			$row['total'] = $order->get_formatted_order_total();
			$this->rows[] = $row;
		}
	}
	
}