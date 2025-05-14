<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<h3><?php echo $title; ?></h3>
<?php 
	if (array_key_exists('type', $chart))
	{
		$new_chart 	= array_merge($chart, array('title'=>$title));
		$table_data = json_encode($new_chart);
		echo urldecode($chart['data']);
	}
	else
	{
		if (array_key_exists('encoded', $chart))
		{
			$is_encoded = TRUE;
		}
		else
		{
			$is_encoded = FALSE;
		}
		$new_chart 	= array_merge($chart, array('title'=>$title));
		$table_data = json_encode($new_chart);
?>
<table class="table table-striped table-bordered gray-header">
	<thead>
		<tr>
			<?php foreach($chart['columns'] as $header): ?>
				<?php 
					if (strpos($header, 'total') !== false)
					{
						$align = 'txt-right';
					}
					else
					{
						$align = 'txt-left';
					}
				?>
				<th class="<?php echo $align; ?>"><?php echo ucwords(str_replace("_",' ',$header)); ?></th>
			<?php endforeach; ?>
		</tr>
	</thead>
	<tbody>
		<?php foreach($chart['data'] as $row): ?>
			<tr>
				<?php foreach($chart['columns'] as $header): ?>
					
					<?php 
						if (strpos($header, 'total') !== false)
						{
							$align = 'txt-right';
						}
						else
						{
							$align = 'txt-left';
						}
					?>
					<td class="<?php echo $align; ?>">
						<?php 
							if ($is_encoded)
							{
								echo urldecode($row[$header]); 
							}
							else
							{
								echo $row[$header]; 
							}
						?>
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php } ?>
<script type="text/javascript">
	if (create_chart_img)
	{
		$('#chart_img_<?php echo $position; ?>').val('<?php echo $table_data; ?>');
		document.dispatchEvent(new CustomEvent('chart-done', { detail: { position: '<?php echo $position; ?>' } }));
	}
</script>