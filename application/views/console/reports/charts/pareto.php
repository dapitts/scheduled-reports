<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php 
	$series_name 		= $chart['columns'][0];
	$series_data 		= $chart['columns'][1];
	$x_axis_title 		= ucwords(str_replace("_",' ',$chart['columns'][0]));
	$y_axis_title 		= ucwords(str_replace("_",' ',$chart['columns'][1]));	
	$total_events 		= 0;	
	$cumulative_percent = 0; 
	$new_data_array		= array();
	
	switch($width)
	{
		case 'col-md-4' : 
			$margins = '[50, 10, 55, 55]'; 
			$legend_r_margin = 0;
			break;
		case 'col-md-6' : 
			$margins = '[50, 60, 105, 70]'; 
			$legend_r_margin = -52;
			break;
		default 	 : 
			$margins = '[50, 60, 90, 70]'; 
			$legend_r_margin = -52;
			break;
	}
	
	foreach($chart['data'] as $row) 
	{
    	$total_events = intval($total_events + $row[$series_data]);
	} 	
	$num 					= $total_events;
	$round 					= (strlen($num)-2);
	$y_axis_max 			= round($num,-$round);
	$y_axis_tickinterval 	= round($y_axis_max/5);

	if ($y_axis_tickinterval == 0)
	{
		$y_axis_tickinterval = 1;
	}

	foreach($chart['data'] as $row) 
	{	
		$event_percent 		= round(($row[$series_data] / $total_events) * 100);
		$cumulative_percent	= round($cumulative_percent + $event_percent);
		if (!empty($row['color']))
	    {
			$row_array = array(
				'name' 		=> str_replace("/", ", ", $row[$series_name]),
				'events' 	=> $row[$series_data],
				'percent'	=> $cumulative_percent
			);
		}
		else
		{
			$row_array = array(
				'name' 		=> str_replace("/", ", ", $row[$series_name]),
				'events' 	=> $row[$series_data],
				'percent'	=> $cumulative_percent
			);
		}
		array_push($new_data_array, $row_array);
	}
	
	if (!empty($chart['data_color']))
	{
		$set_color = $chart['data_color'];
	}
	
	$series 		= 1;
	$number_series 	= $chart['series'];
	$jsonArray 		= array();
	$rangeArray 	= array();
	$dataArray 		= array();
	
	while($series <= $number_series)
	{
		foreach($chart['data'] as $row) 
		{
	    	$column = $row[$series_name];
	    	$value 	= $row[$series_data];
	    	$x = "'".$column."'";
	    	$y = $value;
	    	array_push($rangeArray, $x);
	    	array_push($dataArray, $y);
		} 	
		$response = array(
			'name'	=> str_replace("'", "", reset($rangeArray))." - ".str_replace("'", "", end($rangeArray)),
			'data'	=> $dataArray,
			'range' => $rangeArray
		);
		array_push($jsonArray, $response);
		# increment series
		$series++;		
	}

	$xAxisCategories = implode(",", $rangeArray);
	$yAxisCategories = implode(",", $dataArray);
?>

<script type="text/javascript">
//$(document).ready(function() {
    let chart = new Highcharts.Chart({
        chart: {
	        renderTo: '<?php echo $position; ?>',
            margin: <?php echo $margins; ?>,
            alignTicks: false,
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
        xAxis: {
	        <?php if ($number_series > 1) { ?>
	        categories: [<?php foreach($new_data_array as $row) { echo "'".$row['name']."',"; } ?>],
	        <?php } else { ?>
	        categories: [<?php echo $xAxisCategories; ?>],
	        <?php } ?>
            visible: true,
            labels: {
	            //autoRotation: false,
				autoRotationLimit: 40,
            },
            crosshair: true,
            tickWidth: 1
        },
        yAxis: [{ 
	        // Primary yAxis [ Left Side: Total Events]
            title: {
                text: 'Total Events',
                style: {
                    color: Highcharts.getOptions().colors[1]
                }
            },
            min: 0,
            max: <?php echo $y_axis_max; ?>,
            tickInterval: <?php echo $y_axis_tickinterval; ?>,
        }, { 
	        // Secondary yAxis [ Right Side : Percent ]
	        title: {
                text: '',
            },
            labels: {
                format: '{value}%',
                style: {
                    color: Highcharts.getOptions().colors[0]
                }
            },
            gridLineWidth: 0,
			tickInterval: 25,
            max: 100,
            min: 0,
            opposite: true,
            endOnTick: true
        }],
        plotOptions: {
            spline: {
	            color: '#000000',
	            marker: {
                    enabled: true,
                    //radius: 4,
                    //lineWidth: 1,
                },
                dataLabels: {
                    enabled: false,
                    format: '{y}%',
                },
            }
        },
        tooltip: {
            shared: true,
            followPointer: true,
        },
        legend: {
	        layout: 'vertical',
	        align: 'right',
	        verticalAlign: 'top',
			enabled: true,
            floating: true,
            y: 112,
            x: <?php echo $legend_r_margin; ?>,
            borderWidth: 1,
            backgroundColor: (Highcharts.theme && Highcharts.theme.legendBackgroundColor) || '#FFFFFF',
        },
        series: [{
            name: 'Event Count',
            type: 'column',
            yAxis: 0,
            colorByPoint: false,
            zIndex: 1,
            data: [
	        <?php 
	        	foreach($new_data_array as $row) 
	        	{
		        	echo $row['events'].",";
	        	} 	
		    ?>
	        ],
	        <?php if (!empty($set_color)) { ?>
	        	color: '<?php echo $set_color; ?>'
	        <?php } ?>
        }, {
            name: 'Percent',
            type: 'spline',
            yAxis: 1,
            zIndex: 2,
            data: [
	        <?php 
	        	foreach($new_data_array as $row) 
	        	{
		        	echo $row['percent'].",";
	        	} 	
		    ?>
	        ],          
            tooltip: {
                valueSuffix: '%'
            }
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