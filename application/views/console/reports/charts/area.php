<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php 
	$series_name 	= $chart['columns'][0];
	$series_data 	= $chart['columns'][1];
	$x_axis_title 	= ucwords(str_replace("_",' ',$chart['columns'][0]));
	$y_axis_title 	= ucwords(str_replace("_",' ',$chart['columns'][1]));	
	
	switch($width)
	{
		case 'col-md-4' : 
			$margins = '50, 15, 80, 25'; 
			$legend_font = '6px';
			break;
		case 'col-md-6' : 
			$margins = '50, 15, 80, 0'; 
			$legend_font = '10px';
			break;
		default 	 : 
			$margins = '65, 50, 100, 0'; 
			$legend_font = '12px';
			break;
	}
	
	$totals 		= 0;
	$reportable 	= 0;
	$investigated 	= 0;
	$escalated 		= 0;
	$critical 		= 0;
	
	foreach($chart['data'] as $row) 
	{
    	if ($row['type'] === 'Total Transactions')
    	{
	    	$totals = intval($row['total_events']);
    	}
    	if ($row['type'] === 'Reportable')
    	{
	    	$reportable = intval($row['total_events']);
	    	if (!empty($row['color']))
			{
	    		$reportable_color 	= $row['color'];
	    	}
    	}
    	if ($row['type'] === 'Investigated')
    	{
	    	$investigated = intval($row['total_events']);
	    	if (!empty($row['color']))
			{
	    		$investigated_color = $row['color'];
	    	}
    	}
    	if ($row['type'] === 'Escalated')
    	{
	    	$escalated = intval($row['total_events']);
	    	if (!empty($row['color']))
			{
	    		$escalated_color = $row['color'];
	    	}
    	}
    	if ($row['type'] === 'Critical')
    	{
	    	$critical = intval($row['total_events']);
	    	if (!empty($row['color']))
			{
	    		$critical_color = $row['color'];
	    	}
    	}
	} 	
?>

<script type="text/javascript">
//$(document).ready(function() {
    let chart = new Highcharts.Chart({
        chart: {
	        renderTo: '<?php echo $position; ?>',
            type: 'spline',
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
	        }  
        },
        xAxis: {
            categories: ['Total Transactions','Reportable','Investigated','Escalated','Critical'],
            visible: true,
            tickWidth: 1
        },
        yAxis: {
	        type: 'logarithmic',
	        min: 1,
            title: {
                text: 'Total Events'
            }
        },
        plotOptions: {
            area: {
                events: {
		            legendItemClick: function () {
		                return false; 
		            }
		        }
            },  
            spline: {
                events: {
		            legendItemClick: function () {
		                return false; 
		            }
		        }
            },
            
        },
        legend: {
            enabled: false,
            itemStyle: {
	            fontSize: '<?php echo $legend_font; ?>'
            }
        },
        series: [
	        {
	            name: 'Non-Triggering',
	            data: [<?php echo ($totals <= 0)?'null':$totals; ?>, null, null, null, null],
	            color: '#7cb5ec'
	        }, 
	        {
	            name: 'System Cleared',
	            data: [null,<?php echo ($reportable <= 0)?'null':$reportable; ?>, null, null, null],
	            color: '#434348'
	        }, 
	        {
	            name: 'Resolved',
	            data: [null, null,<?php echo ($investigated <= 0)?'null':$investigated; ?>, null, null],
	            color: '#20B2AA'
	        }, 
	        {
	            name: 'Non-Critical',
	            data: [null, null, null,<?php echo ($escalated <= 0)?'null':$escalated; ?>, null],
	            color: '#B22222'
	        }, 
	        {
	            name: 'Critical',
	            data: [null, null, null, null,<?php echo ($critical <= 0)?'null':$critical; ?>],
	            color: '#d80000'
	        },
	        {
	            name: 'non-trigger area',
	            type: 'area',
	            showInLegend: false,
	            marker: {enabled:false},
	            data: [<?php echo ($totals <= 0)?'null':$totals; ?>,<?php echo ($reportable <= 0)?'null':$reportable; ?>],
	            color: '#7cb5ec'
	        },
	        {
	            name: 'system cleared area',
	            type: 'area',
	            showInLegend: false,
	            marker: {enabled:false},
	            data: [null,<?php echo ($reportable <= 0)?'null':$reportable; ?>,<?php echo ($investigated <= 0)?'null':$investigated; ?>, null, null],
	            color: '#434348'
	        },
	        {
	            name: 'Resolved area',
	            type: 'area',
	            showInLegend: false,
	            marker: {enabled:false},
	            data: [null, null,<?php echo ($investigated <= 0)?'null':$investigated; ?>,<?php echo ($escalated <= 0)?'null':$escalated; ?>, null],
	            color: '#20B2AA'
	        }, 
	        {
	            name: 'Non-Critical area',
	            type: 'area',
	            showInLegend: false,
	            marker: {enabled:false},
	            data: [null, null, null,<?php echo ($escalated <= 0)?'null':$escalated; ?>,<?php echo ($critical <= 0)?'null':$critical; ?>],
	            color: '#B22222'
	        }, 
	        {
	            name: 'Critical area',
	            type: 'area',
	            showInLegend: false,
	            marker: {enabled:false},
	            data: [null, null, null, null,<?php echo ($critical <= 0)?'null':$critical; ?>],
	            color: '#d80000'
	        },
        ]


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