<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php 
	$series_name 	= $chart['columns'][0];
	$series_data 	= $chart['columns'][1];
	$x_axis_title 	= ucwords(str_replace("_",' ',$chart['columns'][0]));
	$y_axis_title 	= ucwords(str_replace("_",' ',$chart['columns'][1]));	
	
	$number_series = intval($chart['series']);
	
	switch($width)
	{
		case 'col-md-4' : $margins = '50, 15, 80, 25'; break;
		case 'col-md-6' : $margins = '50, 15, 80, 25'; break;
		default 	 : $margins = '65, 50, 100, 0'; break;
	}
?>

<script type="text/javascript">
//$(document).ready(function() {
    let chart = new Highcharts.Chart({
        chart: {
	        type: 'pie',
	        renderTo: '<?php echo $position; ?>',
            //margin: [<?php echo $margins; ?>],
            options3d: {
                enabled: true,
                alpha: 45,
                beta: 0
            }
        },
        credits: {
        	enabled: false
        },
        exporting: {
        	enabled: false
        },
        accessibility: {
            enabled: false
        },
        title: {
            text: '<?php echo $title; ?>',
            style: {fontSize: '16px'} 
        },
        tooltip: {
            pointFormat: '<b>Events: {point.y} | {point.percentage:.1f}%</b>'
            // Events:{point.y} 
        },
        plotOptions: {
            pie: {
	            size: '80%',
                allowPointSelect: true,
                cursor: 'pointer',
                depth: 35,
                dataLabels: {
                    enabled: false,
                    format: '{point.name}:<br>{point.percentage:.1f}%',
                    style: {
	                	fontSize: '12px',
	                	fontWeight: '500',
	                	width: '100px',
	                }
                },
                showInLegend: true,
            }
        },
        legend: {
	    	labelFormat: '{name}: {percentage:.1f}%',
	    	<?php if ($number_series > 2) { ?>
	    	layout: 'vertical',
	        align: 'right',
	        verticalAlign: 'top',
			y: 29,
            floating: false,
	    	<?php } ?>
        },
        series: [{
		       type: 'pie',
		       data: [
		        	<?php 
			        	foreach($chart['data'] as $row) 
			        	{
				        	//echo "['".$row[$series_name]."',".$row[$series_data]."],"; 	
				        	if ($number_series > 2) 
				        	{
					        	echo "['".$row[$series_name]."',".$row[$series_data]."],";
					        }
				        	else
				        	{
					        	if (!empty($row['color'])) 
					        	{
						        	echo "{ name: '".$row[$series_name]."', y: ".$row[$series_data].", color: '".$row['color']."'},";
					        	}
					        	else
					        	{
						        	echo "['".$row[$series_name]."',".$row[$series_data]."],";
					        	}
							}
			        	} 	
				    ?>
				]
		}]

    });
    
    /*
	| ------------------------------------------------------------------------------
	| Create SVG image code for print reports
	| ------------------------------------------------------------------------------
	*/

	if (create_chart_img)
	{
		let canvas = document.getElementById('chart_canvas_<?php echo $position; ?>');
		canvas.width    = 600;
		canvas.height   = 400;

		let ctx = canvas.getContext('2d'),
		    img = document.createElement('img');

		img.onload = () => {
		    ctx.drawImage(img, 0, 0);
		    $('#chart_img_<?php echo $position; ?>').val(canvas.toDataURL('image/png'));
		    document.dispatchEvent(new CustomEvent('chart-done', { detail: { position: '<?php echo $position; ?>' } }));
		}

		img.src = 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(chart.getSVG());
	}

//});
</script>