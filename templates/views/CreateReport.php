<?php
/**
 * Plugin HTML Template
 *
 * Created:  August 15, 2017
 *
 * @package  Product Report
 * @author   Max Strukov
 * @since    1.0.0
 *
 * Here is an example of how to get the contents of this template while 
 * providing the values of the $title and $content variables:
 * ```
 * $content = $plugin->getTemplateContent( 'views/CreateReport', array( 'title' => 'Some Custom Title', 'content' => 'Some custom content' ) ); 
 * ```
 * 
 * @param	Plugin		$this		The plugin instance which is loading this template
 *
 * @param	string		$title		The provided title
 * @param	string		$content	The provided content
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

?>

<!-- html content -->
<h1><?php echo $page_title ?></h1>
<div id="productreport_form">
	<?php //echo $content ?>
	<h3>Choose the date range</h3>
	<form method="post">
		<input type="date" name="start_date" value="<?php echo date('Y-m-d', strtotime("-1 day")); ?>"/>
		<input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>"/>
		<br/>
		<h4>Check order statuses</h4>
		<?php foreach ($order_statuses as $status_key => $status_name): ?>
			<input type="checkbox" name="order_status[]" value="<?php echo $status_key ?>" id="<?php echo $status_key ?>" checked><label for="<?php echo $status_key ?>"><?php echo $status_name ?></label><br/>
		<?php endforeach;?>
		<input type="checkbox" name="order_status[]" value="trash" id="trash"><label for="trash">Trash</label><br/><br/>
		<p><input type="submit" name="download" class="button button-primary" value="Download Report as CSV" onclick="jQuery(this).closest('form').attr('target', '_blank'); return true;"></p>
	</form>
	<?php if ($rows): ?>
		<table>
			<tr>
				<?php foreach ($header_titles as $title): ?>
					<th><?php echo $title; ?></th>
				<?php endforeach; ?>
			</tr>
			<?php foreach ($rows as $row): ?>
				<tr>
					<?php foreach ($row as $val): ?>
						<td><?php echo $val; ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>