<?php
/**
 * Plugin Name: Cal's Order Report (THM)
 * Depends: lib-modern-framework
 * Description: It provides the ability to export orders as one per line in a CSV report.
 * Version: 1.0.5
 * Author: Max Strukov ( Miller Media )
 * Author URI: www.millermedia.io
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

/* Load Only Once */
if ( ! class_exists( 'MillerMediaProductReportPlugin' ) )
{
	class MillerMediaProductReportPlugin
	{
		public static function init()
		{
			/* Plugin Core */
			$plugin	= \MillerMedia\ProductReport\Plugin::instance();
			$plugin->setPath( rtrim( plugin_dir_path( __FILE__ ), '/' ) );
			
			/* Plugin Settings */
			$settings = \MillerMedia\ProductReport\Settings::instance();
			$plugin->addSettings( $settings );
			
			/*Product Report page*/
			$report = \MillerMedia\ProductReport\Report::instance();
			
			/* Connect annotated resources to wordpress core */
			$framework = \Modern\Wordpress\Framework::instance()
				->attach( $plugin )
				//->attach( $settings )
				->attach( $report )
				;
			
			/* Enable Widgets */
			\MillerMedia\ProductReport\BasicWidget::enableOn( $plugin );
		}
		
		public static function status() {
			if ( ! class_exists( 'ModernWordpressFramework' ) ) {
				echo '<td colspan="3" class="plugin-update colspanchange">
						<div class="update-message notice inline notice-error notice-alt">
							<p><strong style="color:red">INOPERABLE.</strong> Please activate <a href="' . admin_url( 'plugins.php?page=tgmpa-install-plugins' ) . '"><strong>Modern Framework for Wordpress</strong></a> to enable the operation of this plugin.</p>
						</div>
					  </td>';
			}
		}
	}
	
	/* Autoload Classes */
	require_once 'vendor/autoload.php';
	
	/* Bundled Framework */
	if ( file_exists( __DIR__ . '/framework/plugin.php' ) ) {
		include_once 'framework/plugin.php';
	}

	/* Register plugin dependencies */
	include_once 'includes/plugin-dependency-config.php';
	
	/* Register plugin status notice */
	add_action( 'after_plugin_row_' . plugin_basename( __FILE__ ), array( 'MillerMediaProductReportPlugin', 'status' ) );
	
	/**
	 * DO NOT REMOVE
	 *
	 * This plugin depends on the modern wordpress framework.
	 * This block ensures that it is loaded before we init.
	 */
	if ( class_exists( 'ModernWordpressFramework' ) ) {
		MillerMediaProductReportPlugin::init();
	}
	else {
		add_action( 'modern_wordpress_init', array( 'MillerMediaProductReportPlugin', 'init' ) );
	}
	
}

