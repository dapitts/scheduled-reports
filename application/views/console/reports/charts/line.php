<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php 
	$series_name 	= $chart['columns'][0];
	$series_data 	= $chart['columns'][1];
	$x_axis_title 	= ucwords(str_replace("_",' ',$chart['columns'][0]));
	$y_axis_title 	= ucwords(str_replace("_",' ',$chart['columns'][1]));	
	
	switch($width)
	{
		case 'col-md-4' : $margins = '[50,10,60,60]'; break;
		case 'col-md-6' : $margins = '[50,10,60,60]'; break;
		default 	 : $margins = '[50,10,60,60]'; break;
	}
	
	$series 		= 1;
	$number_series 	= $chart['series'];
	$jsonArray 		= array();
	$rangeArray 	= array();
	$dataArray 		= array();
	
	if ($number_series > 1)
	{
		$show_legend = 'true';
	}
	else
	{
		$show_legend = 'false';
	}
	
	// while($series <= $number_series)
	// {
		foreach($chart['data'] as $row) 
		{
	    	$column = $row[$series_name];
	    	$value 	= $row[$series_data];
	    	$x = "'".$column."'";
	    	$y = $value;
	    	array_push($rangeArray, $x);
	    	array_push($dataArray, $y);
		} 		
		if (!empty($row['color']))
	    {
		    $response = array(
				'name'	=> str_replace("'", "", reset($rangeArray))." - ".str_replace("'", "", end($rangeArray)),
				'data'	=> $dataArray,
				'range' => $rangeArray
				,
				'color' => $row['color']
			);
	    }
	    else
	    {
			$response = array(
				'name'	=> str_replace("'", "", reset($rangeArray))." - ".str_replace("'", "", end($rangeArray)),
				'data'	=> $dataArray,
				'range' => $rangeArray
			);
	    }
		array_push($jsonArray, $response);
		# increment series
		$series++;		
	// }

	$xAxisCategories = implode(",", $rangeArray);
	$yAxisCategories = implode(",", $dataArray);	
	
?>



<script type="text/javascript">
//$(document).ready(function() {
    let chart = new Highcharts.Chart({
	    chart: {
            renderTo: '<?php echo $position; ?>',
            margin: <?php echo $margins; ?>
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
            style: {
	            fontSize: '16px'
	        }, 
        },
        xAxis: {
            categories: [<?php echo $xAxisCategories; ?>],
            visible: true,
            tickWidth: 1,
            labels: {
				autoRotationLimit: 50
            }
        },
        
        yAxis: {
            //min: 0,
            title: {
                text: '<?php echo $y_axis_title; ?>'
            },
            plotLines: [{
                value: 0,
                width: 1,
                //color: '#808080'
            }]

        },
        tooltip: {
            pointFormat: '<b>Events: {point.y}</b>',
        },
        legend: {
	        layout: 'vertical',
	        align: 'right',
	        verticalAlign: 'top',
	        x: 10,
	        y: -10,
	        borderColor: '#ccc',
	        borderWidth: 1,
	        borderRadius: 6,
	        backgroundColor: '#fff',
	        floating: true,
	        enabled: <?php echo $show_legend; ?>
	    },
        
        series: <?php echo json_encode($jsonArray); ?>

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