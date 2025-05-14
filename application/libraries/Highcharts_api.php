<?php 
defined('BASEPATH') OR exit('No direct script access allowed');

class Highcharts_api 
{
    private $ch;
    private $url;
    private $colors;

    public function __construct()
    {
        $CI =& get_instance();
        
        $this->url      = $CI->config->item('highcharts_export_server_url');
        $this->colors   = ['#2f7ed8', '#000000', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1'];
    }

    public function get_column_2d_chart($chart_data)
    {
        $series_name    = $chart_data['chart']['columns'][0];
        $series_data    = $chart_data['chart']['columns'][1];
        $x_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][0]));
        $y_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][1]));	
        
        switch ($chart_data['width'])
        {
            case 'col-md-4' : $margins = [50, 10, 55, 45]; break;
            case 'col-md-6' : $margins = [50, 10, 55, 60]; break;
            default 	 : $margins = [50, 10, 80, 60]; break;
        }

        $number_series  = $chart_data['chart']['series'];

        $rangeArray = array();
        $dataArray  = array();
        $categories = array();
        $data       = array();
        $series     = array();

        $show_legend        = false;
        $show_categories    = true;

        if ($number_series > 1)
        {
            foreach ($chart_data['chart']['data'] as $row) 
            {
                $categories[] = str_replace('/', ', ', $row[$series_name]);

                if (!empty($row['color']))
                {
                    $data[] = array(
                        'name'  => str_replace('/', ', ', $row[$series_name]),
                        'y'     => $row[$series_data],
                        'color' => $row['color']
                    );
                }
                else
                {
                    $data[] = array(
                        'name'  => str_replace('/', ', ', $row[$series_name]),
                        'y'     => $row[$series_data]
                    );
                }
            }

            $series[] = array(
                'colorByPoint'  => true,
                'data'          => $data
            );
        }
        else
        {
            foreach ($chart_data['chart']['data'] as $row) 
            {
                $column = $row[$series_name];
                $value  = $row[$series_data];
                $x      = $column;
                $y      = $value;
                array_push($rangeArray, $x);
                array_push($dataArray, $y);
            }

            if (!empty($row['color']))
            {
                $response = array(
                    'name'  => reset($rangeArray).' - '.end($rangeArray),
                    'data'  => $dataArray,
                    'range' => $rangeArray,
                    'color' => $row['color']
                );
            }
            else
            {
                $response = array(
                    'name'  => reset($rangeArray).' - '.end($rangeArray),
                    'data'  => $dataArray,
                    'range' => $rangeArray
                );
            }

            $categories = $rangeArray; 
            $series[]   = $response;
        }

        $chart_opts = [
            'chart'         => ['type' => 'column', 'margin' => $margins, 'style' => ['fontSize' => '0.9375rem']],
            'credits'       => ['enabled' => false],
            'exporting'     => ['enabled' => false],
            'accessibility' => ['enabled' => false],
            'title'         => ['text' => $chart_data['title'], 'style' => ['fontSize' => '16px']],
            'xAxis'         => ['categories' => $categories, 'visible' => $show_categories, 'labels' => ['autoRotationLimit' => 50], 'tickWidth' => 1],
            'yAxis'         => ['min' => 0, 'title' => ['text' => $y_axis_title]],
            'tooltip'       => ['pointFormat' => '<b>Events: {point.y}</b>'],
            'legend'        => ['layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => 10, 'y' => -10, 'borderColor' => '#ccc', 'borderWidth' => 1, 'borderRadius' => 6, 'backgroundColor' => '#fff', 'floating' => true, 'enabled' => $show_legend],
            'plotOptions'   => ['column' => ['pointPadding' => 0.2, 'borderWidth' => 0]],
            'series'        => $series
        ];

        $header_fields = array(
            'Content-Type: application/json',
            'Accept: image/png'
        );

        $post_fields = array(
            'infile'        => $chart_opts,
            'globalOptions' => ['colors' => $this->colors]
        );

        $response = $this->call_api('POST', $this->url, $header_fields, json_encode($post_fields));

        if ($response['result'] !== FALSE)
        {
            if ($response['http_code'] === 200)
            {
                return array(
                    'success'   => TRUE,
                    'type'      => 'image/png',
                    'response'  => $response['result']
                );
            }
            else
            {
                return array(
                    'success'   => FALSE,
                    'response'  => $response
                );
            }
        }
        else
        {
            return array(
                'success'   => FALSE,
                'response'  => 'cURL errno: '.$response['errno'].', cURL error: '.$response['error']
            );
        }
    }

    public function get_column_3d_chart($chart_data)
    {
        $series_name    = $chart_data['chart']['columns'][0];
        $series_data    = $chart_data['chart']['columns'][1];
        $x_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][0]));
        $y_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][1]));
        
        switch($chart_data['width'])
        {
            case 'col-md-4' : $margins = [50, 15, 60, 55]; break;
            case 'col-md-6' : $margins = [50, 15, 60, 50]; break;
            default 	 : $margins = [50, 25, 80, 20]; break;
        }

        $number_series  = $chart_data['chart']['series'];

        $rangeArray = array();
        $dataArray  = array();
        $categories = array();
        $data       = array();
        $series     = array();

        $show_legend        = false;
        $show_categories    = true;

        if ($number_series > 1)
        {
            foreach ($chart_data['chart']['data'] as $row) 
            {
                $categories[] = str_replace('/', ', ', $row[$series_name]);

                if (!empty($row['color']))
                {
                    $data[] = array(
                        'name'  => str_replace('/', ', ', $row[$series_name]),
                        'y'     => $row[$series_data],
                        'color' => $row['color']
                    );
                }
                else
                {
                    $data[] = array(
                        'name'  => str_replace('/', ', ', $row[$series_name]),
                        'y'     => $row[$series_data]
                    );
                }
            }

            $series[] = array(
                'colorByPoint'  => true,
                'data'          => $data
            );
        }
        else
        {
            foreach ($chart_data['chart']['data'] as $row) 
            {
                $column = $row[$series_name];
                $value  = $row[$series_data];
                $x      = $column;
                $y      = $value;

                array_push($rangeArray, $x);
                array_push($dataArray, $y);
            } 	
            
            if (!empty($row['color']))
            {
                $response = array(
                    'name'  => reset($rangeArray).' - '.end($rangeArray),
                    'data'  => $dataArray,
                    'range' => $rangeArray,
                    'color' => $row['color']
                );
            }
            else
            {
                $response = array(
                    'name'  => reset($rangeArray).' - '.end($rangeArray),
                    'data'  => $dataArray,
                    'range' => $rangeArray
                ); 
            }

            $categories = $rangeArray; 
            $series[]   = $response;
        }

        $chart_opts = [
            'chart'         => ['type' => 'column', 'margin' => $margins, 'options3d' => ['enabled' => true, 'alpha' => 10, 'beta' => 10, 'depth' => 40, 'viewDistance' => 40], 'style' => ['fontSize' => '0.9375rem']],
            'credits'       => ['enabled' => false],
            'exporting'     => ['enabled' => false],
            'accessibility' => ['enabled' => false],
            'title'         => ['text' => $chart_data['title'], 'style' => ['fontSize' => '16px']],
            'plotOptions'   => ['column' => ['depth' => 35]],
            'xAxis'         => ['categories' => $categories, 'visible' => $show_categories, 'labels' => ['autoRotationLimit' => 50]],
            'yAxis'         => ['min' => 0, 'title' => ['text' => $y_axis_title]],
            'tooltip'       => ['pointFormat' => '<b>Events: {point.y}</b>'],
            'legend'        => ['layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => 10, 'y' => -10, 'borderColor' => '#ccc', 'borderWidth' => 1, 'borderRadius' => 6, 'backgroundColor' => '#fff', 'floating' => true, 'enabled' => $show_legend],
            'series'        => $series
        ];

        $header_fields = array(
            'Content-Type: application/json',
            'Accept: image/png'
        );

        $post_fields = array(
            'infile'        => $chart_opts,
            'globalOptions' => ['colors' => $this->colors]
        );

        $response = $this->call_api('POST', $this->url, $header_fields, json_encode($post_fields));

        if ($response['result'] !== FALSE)
        {
            if ($response['http_code'] === 200)
            {
                return array(
                    'success'   => TRUE,
                    'type'      => 'image/png',
                    'response'  => $response['result']
                );
            }
            else
            {
                return array(
                    'success'   => FALSE,
                    'response'  => $response
                );
            }
        }
        else
        {
            return array(
                'success'   => FALSE,
                'response'  => 'cURL errno: '.$response['errno'].', cURL error: '.$response['error']
            );
        }
    }

    public function get_pie_2d_chart($chart_data)
    {
        $series_name    = $chart_data['chart']['columns'][0];
        $series_data    = $chart_data['chart']['columns'][1];
        
        $x_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][0]));
        $y_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][1]));	
        
        $number_series  = $chart_data['chart']['series'];
        $data           = [];

        switch ($chart_data['width'])
        {
            case 'col-md-4' : $margins = [0, 0, 0, 0]; break;
            case 'col-md-6' : $margins = [50, 15, 80, 25]; break;
            default 	 : $margins = [65, 50, 100, 0]; break;
        }

        if ($number_series > 2)
        {
            $legend = ['labelFormat' => '{name}: {percentage:.1f}%', 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'y' => 29, 'floating' => false];
        }
        else
        {
            $legend = ['labelFormat' => '{name}: {percentage:.1f}%'];
        }

        foreach ($chart_data['chart']['data'] as $row)
        {
            if ($number_series > 2)
            {
                $data[] = array(
                    $row[$series_name],
                    $row[$series_data]
                );
            }
            else
            {
                if (!empty($row['color']))
                {
                    $data[] = array(
                        'name'  => $row[$series_name],
                        'y'     => $row[$series_data],
                        'color' => $row['color']
                    );
                }
                else
                {
                    $data[] = array(
                        $row[$series_name],
                        $row[$series_data]
                    );
                }
            }
        }

        $chart_opts = [
            'chart'         => ['type' => 'pie', 'plotBackgroundColor' => null, 'plotBorderWidth' => null, 'plotShadow' => false, 'style' => ['fontSize' => '0.9375rem']],
            'credits'       => ['enabled' => false],
            'exporting'     => ['enabled' => false],
            'accessibility' => ['enabled' => false],
            'title'         => ['text' => $chart_data['title'], 'style' => ['fontSize' => '16px']],
            'tooltip'       => ['pointFormat' => '<b>Events: {point.y} | {point.percentage:.1f}%</b>'],
            'plotOptions'   => ['pie' => ['size' => '80%', 'allowPointSelect' => true, 'cursor' => 'pointer', 'showInLegend' => true, 'dataLabels' => ['enabled' => false, 'format' => '{point.name}:<br>{point.percentage:.1f}%', 'style' => ['fontSize' => '12px', 'fontWeight' => '500', 'width' => '100px']]]],
            'legend'        => $legend,
            'series'        => [
                ['type' => 'pie', 'data' => $data]
            ]
        ];

        $header_fields = array(
            'Content-Type: application/json',
            'Accept: image/png'
        );

        $post_fields = array(
            'infile'        => $chart_opts,
            'globalOptions' => ['colors' => $this->colors]
        );

        $response = $this->call_api('POST', $this->url, $header_fields, json_encode($post_fields));

        if ($response['result'] !== FALSE)
        {
            if ($response['http_code'] === 200)
            {
                return array(
                    'success'   => TRUE,
                    'type'      => 'image/png',
                    'response'  => $response['result']
                );
            }
            else
            {
                return array(
                    'success'   => FALSE,
                    'response'  => $response
                );
            }
        }
        else
        {
            return array(
                'success'   => FALSE,
                'response'  => 'cURL errno: '.$response['errno'].', cURL error: '.$response['error']
            );
        }
    }

    public function get_pie_3d_chart($chart_data)
    {
        $series_name    = $chart_data['chart']['columns'][0];
        $series_data    = $chart_data['chart']['columns'][1];
        $x_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][0]));
        $y_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][1]));	
        
        $number_series  = $chart_data['chart']['series'];
        $data           = [];
        
        switch ($chart_data['width'])
        {
            case 'col-md-4' : $margins = [50, 15, 80, 25]; break;
            case 'col-md-6' : $margins = [50, 15, 80, 25]; break;
            default 	 : $margins = [65, 50, 100, 0]; break;
        }

        if ($number_series > 2)
        {
            $legend = ['labelFormat' => '{name}: {percentage:.1f}%', 'layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'y' => 29, 'floating' => false];
        }
        else
        {
            $legend = ['labelFormat' => '{name}: {percentage:.1f}%'];
        }

        foreach ($chart_data['chart']['data'] as $row)
        {
            if ($number_series > 2)
            {
                $data[] = array(
                    $row[$series_name],
                    $row[$series_data]
                );
            }
            else
            {
                if (!empty($row['color']))
                {
                    $data[] = array(
                        'name'  => $row[$series_name],
                        'y'     => $row[$series_data],
                        'color' => $row['color']
                    );
                }
                else
                {
                    $data[] = array(
                        $row[$series_name],
                        $row[$series_data]
                    );
                }
            }
        }

        $chart_opts = [
            'chart'         => ['type' => 'pie', 'options3d' => ['enabled' => true, 'alpha' => 45, 'beta' => 0], 'style' => ['fontSize' => '0.9375rem']],
            'credits'       => ['enabled' => false],
            'exporting'     => ['enabled' => false],
            'accessibility' => ['enabled' => false],
            'title'         => ['text' => $chart_data['title'], 'style' => ['fontSize' => '16px']],
            'tooltip'       => ['pointFormat' => '<b>Events: {point.y} | {point.percentage:.1f}%</b>'],
            'plotOptions'   => ['pie' => ['size' => '80%', 'allowPointSelect' => true, 'cursor' => 'pointer', 'depth' => 35, 'showInLegend' => true, 'dataLabels' => ['enabled' => false, 'format' => '{point.name}:<br>{point.percentage:.1f}%', 'style' => ['fontSize' => '12px', 'fontWeight' => '500', 'width' => '100px']]]],
            'legend'        => $legend,
            'series'        => [
                ['type' => 'pie', 'data' => $data]
            ]
        ];

        $header_fields = array(
            'Content-Type: application/json',
            'Accept: image/png'
        );

        $post_fields = array(
            'infile'        => $chart_opts,
            'globalOptions' => ['colors' => $this->colors]
        );

        $response = $this->call_api('POST', $this->url, $header_fields, json_encode($post_fields));

        if ($response['result'] !== FALSE)
        {
            if ($response['http_code'] === 200)
            {
                return array(
                    'success'   => TRUE,
                    'type'      => 'image/png',
                    'response'  => $response['result']
                );
            }
            else
            {
                return array(
                    'success'   => FALSE,
                    'response'  => $response
                );
            }
        }
        else
        {
            return array(
                'success'   => FALSE,
                'response'  => 'cURL errno: '.$response['errno'].', cURL error: '.$response['error']
            );
        }
    }

    public function get_spline_chart($chart_data)
    {
        $y_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][1]));

        switch ($chart_data['width'])
        {
            case 'col-md-4' : 
                $margins = [50, 15, 80, 25]; 
                $legend_font = '6px';
                break;
            case 'col-md-6' : 
                $margins = [50, 15, 80, 0]; 
                $legend_font = '10px';
                break;
            default 	 : 
                $margins = [65, 50, 100, 0]; 
                $legend_font = '12px';
                break;
        }

        $totals         = 0;
        $reportable     = 0;
        $investigated   = 0;
        $escalated      = 0;
        $critical       = 0;

        $categories     = array();

        foreach ($chart_data['chart']['data'] as $row) 
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
                    $reportable_color = $row['color'];
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

            $categories[] = $row['type'];
        }

        $chart_opts = [
            'chart'         => ['type' => 'spline', 'style' => ['fontSize' => '0.9375rem']],
            'credits'       => ['enabled' => false],
            'exporting'     => ['enabled' => false],
            'accessibility' => ['enabled' => false],
            'title'         => ['text' => $chart_data['title'], 'style' => ['fontSize' => '16px']],
            'xAxis'         => ['categories' => $categories, 'visible' => true, 'tickWidth' => 1],
            'yAxis'         => ['type' => 'logarithmic', 'min' => 1, 'title' => ['text' => $y_axis_title]],
            'legend'        => ['enabled' => false, 'itemStyle' => ['fontSize' => $legend_font]],
            'series'        => [
                ['name' => 'Non-Triggering', 'data' => [$totals <= 0 ? null : $totals, null, null, null, null], 'color' => '#7cb5ec'],
                ['name' => 'System Cleared', 'data' => [null, $reportable <= 0 ? null : $reportable, null, null, null], 'color' => '#434348'],
                ['name' => 'Resolved', 'data' => [null, null, $investigated <= 0 ? null : $investigated, null, null], 'color' => '#20B2AA'],
                ['name' => 'Non-Critical', 'data' => [null, null, null, $escalated <= 0 ? null : $escalated, null], 'color' => '#B22222'],
                ['name' => 'Critical', 'data' => [null, null, null, null, $critical <= 0 ? null : $critical], 'color' => '#d80000'],
                ['name' => 'non-trigger area', 'data' => [$totals <= 0 ? null : $totals, $reportable <= 0 ? null : $reportable], 'color' => '#7cb5ec', 'type' => 'area', 'showInLegend' => false, 'marker' => ['enabled' => false]],
                ['name' => 'system cleared area', 'data' => [null, $reportable <= 0 ? null : $reportable, $investigated <= 0 ? null : $investigated, null, null], 'color' => '#434348', 'type' => 'area', 'showInLegend' => false, 'marker' => ['enabled' => false]],
                ['name' => 'Resolved area', 'data' => [null, null, $investigated <= 0 ? null : $investigated, $escalated <= 0 ? null : $escalated, null], 'color' => '#20B2AA', 'type' => 'area', 'showInLegend' => false, 'marker' => ['enabled' => false]],
                ['name' => 'Non-Critical area', 'data' => [null, null, null, $escalated <= 0 ? null : $escalated, $critical <= 0 ? null : $critical], 'color' => '#B22222', 'type' => 'area', 'showInLegend' => false, 'marker' => ['enabled' => false]],
                ['name' => 'Critical area', 'data' => [null, null, null, null, $critical <= 0 ? null : $critical], 'color' => '#d80000', 'type' => 'area', 'showInLegend' => false, 'marker' => ['enabled' => false]]
            ]
        ];

        $header_fields = array(
            'Content-Type: application/json',
            'Accept: image/png'
        );

        $post_fields = array(
            'infile'        => $chart_opts,
            'globalOptions' => ['colors' => $this->colors]
        );

        $response = $this->call_api('POST', $this->url, $header_fields, json_encode($post_fields));

        if ($response['result'] !== FALSE)
        {
            if ($response['http_code'] === 200)
            {
                return array(
                    'success'   => TRUE,
                    'type'      => 'image/png',
                    'response'  => $response['result']
                );
            }
            else
            {
                return array(
                    'success'   => FALSE,
                    'response'  => $response
                );
            }
        }
        else
        {
            return array(
                'success'   => FALSE,
                'response'  => 'cURL errno: '.$response['errno'].', cURL error: '.$response['error']
            );
        }
    }

    public function get_pareto_chart($chart_data)
    {
        $series_name        = $chart_data['chart']['columns'][0];
        $series_data        = $chart_data['chart']['columns'][1];
        $x_axis_title       = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][0]));
        $y_axis_title       = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][1]));	
        $total_events       = 0;	
        $cumulative_percent = 0; 
        $new_data_array     = array();
        
        switch ($chart_data['width'])
        {
            case 'col-md-4' : 
                $margins = [50, 10, 55, 55]; 
                $legend_r_margin = 0;
                break;
            case 'col-md-6' : 
                $margins = [50, 60, 105, 70]; 
                $legend_r_margin = -52;
                break;
            default 	 : 
                $margins = [50, 60, 90, 70]; 
                $legend_r_margin = -52;
                break;
        }

        foreach ($chart_data['chart']['data'] as $row) 
        {
            $total_events = intval($total_events + $row[$series_data]);
        }

        $num                    = $total_events;
        $round                  = (strlen($num) - 2);
        $y_axis_max             = round($num, -$round);
        $y_axis_tickinterval    = round($y_axis_max / 5);

        if ($y_axis_tickinterval == 0)
        {
            $y_axis_tickinterval = 1;
        }

        foreach ($chart_data['chart']['data'] as $row) 
        {	
            $event_percent      = round(($row[$series_data] / $total_events) * 100);
            $cumulative_percent	= round($cumulative_percent + $event_percent);
            if (!empty($row['color']))
            {
                $row_array = array(
                    'name'      => str_replace('/', ', ', $row[$series_name]),
                    'events'    => $row[$series_data],
                    'percent'   => $cumulative_percent
                );
            }
            else
            {
                $row_array = array(
                    'name'      => str_replace('/', ', ', $row[$series_name]),
                    'events'    => $row[$series_data],
                    'percent'   => $cumulative_percent
                );
            }
            array_push($new_data_array, $row_array);
        }

        if (!empty($chart_data['chart']['data_color']))
        {
            $set_color = $chart_data['chart']['data_color'];
        }
        else
        {
            $set_color = null;
        }

        $number_series 	= $chart_data['chart']['series'];

        $rangeArray = array();
        $dataArray  = array();
        $categories = array();
        $events     = array();
        $percent    = array();

        foreach ($new_data_array as $row)
        {
            $categories[]   = $row['name'];
            $events[]       = $row['events'];
            $percent[]      = $row['percent'];
        }

        if ($number_series === 1)
        {
            foreach ($chart_data['chart']['data'] as $row) 
            {
                $column = str_replace('/', ', ', $row[$series_name]);
                $value 	= $row[$series_data];
                $x      = $column;
                $y      = $value;
                array_push($rangeArray, $x);
                array_push($dataArray, $y);
            } 	
            $response = array(
                'name'  => reset($rangeArray).' - '.end($rangeArray),
                'data'  => $dataArray,
                'range' => $rangeArray
            );
            
            $categories = $rangeArray;
        }

        $chart_opts = [
            'chart'         => ['margin' => $margins, 'alignTicks' => false, 'style' => ['fontSize' => '0.9375rem']],
            'credits'       => ['enabled' => false],
            'exporting'     => ['enabled' => false],
            'accessibility' => ['enabled' => false],
            'title'         => ['text' => $chart_data['title'], 'style' => ['fontSize' => '16px']],
            'xAxis'         => ['categories' => $categories, 'visible' => true, 'labels' => ['autoRotationLimit' => 40], 'crosshair' => true, 'tickWidth' => 1],
            'yAxis'         => [
                ['title' => ['text' => 'Total Events', 'style' => ['color' => '#000000']], 'min' => 0, 'max' => $y_axis_max, 'tickInterval' => $y_axis_tickinterval],
                ['title' => ['text' => ''], 'labels' => ['format' => '{value}%', 'style' => ['color' => '#2f7ed8']], 'gridLineWidth' => 0, 'tickInterval' => 25, 'max' => 100, 'min' => 0, 'opposite' => true, 'endOnTick' => true]
            ],
            'plotOptions'   => ['spline' => ['color' => '#000000', 'marker' => ['enabled' => true], 'dataLabels' => ['enabled' => false, 'format' => '{y}%']]],
            'tooltip'       => ['shared' => true, 'followPointer' => true],
            'legend'        => ['layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'enabled' => true, 'floating' => true, 'y' => 112, 'x' => $legend_r_margin, 'borderWidth' => 1, 'backgroundColor' => '#FFFFFF'],
            'series'        => [
                ['name' => 'Event Count', 'type' => 'column', 'yAxis' => 0, 'colorByPoint' => false, 'zIndex' => 1, 'data' => $events, 'color' => $set_color],
                ['name' => 'Percent', 'type' => 'spline', 'yAxis' => 1, 'zIndex' => 2, 'data' => $percent, 'tooltip' => ['valueSuffix' => '%']]
            ]
        ];

        $header_fields = array(
            'Content-Type: application/json',
            'Accept: image/png'
        );

        $post_fields = array(
            'infile'        => $chart_opts,
            'globalOptions' => ['colors' => $this->colors]
        );

        $response = $this->call_api('POST', $this->url, $header_fields, json_encode($post_fields));

        if ($response['result'] !== FALSE)
        {
            if ($response['http_code'] === 200)
            {
                return array(
                    'success'   => TRUE,
                    'type'      => 'image/png',
                    'response'  => $response['result']
                );
            }
            else
            {
                return array(
                    'success'   => FALSE,
                    'response'  => $response
                );
            }
        }
        else
        {
            return array(
                'success'   => FALSE,
                'response'  => 'cURL errno: '.$response['errno'].', cURL error: '.$response['error']
            );
        }
    }

    public function get_line_chart($chart_data)
    {
        $series_name    = $chart_data['chart']['columns'][0];
        $series_data    = $chart_data['chart']['columns'][1];
        $x_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][0]));
        $y_axis_title   = ucwords(str_replace('_', ' ', $chart_data['chart']['columns'][1]));	
        
        switch ($chart_data['width'])
        {
            case 'col-md-4' : $margins = [50, 10, 60, 60]; break;
            case 'col-md-6' : $margins = [50, 10, 60, 60]; break;
            default 	 : $margins = [50, 10, 60, 60]; break;
        }

        $number_series  = $chart_data['chart']['series'];

        $rangeArray = array();
        $dataArray  = array();
        $series     = array();
        $categories = array();
        
        if ($number_series > 1)
        {
            $show_legend = true;
        }
        else
        {
            $show_legend = false;
        }

        foreach ($chart_data['chart']['data'] as $row) 
        {
            $column = str_replace('/', ', ', $row[$series_name]);
            $value  = $row[$series_data];
            $x      = $column;
            $y      = $value;
            array_push($rangeArray, $x);
            array_push($dataArray, $y);
        } 

        if (!empty($row['color']))
        {
            $response = array(
                'name'  => reset($rangeArray).' - '.end($rangeArray),
                'data'  => $dataArray,
                'range' => $rangeArray,
                'color' => $row['color']
            );
        }
        else
        {
            $response = array(
                'name'  => reset($rangeArray).' - '.end($rangeArray),
                'data'  => $dataArray,
                'range' => $rangeArray
            );
        }

        $categories = $rangeArray; 
        $series[]   = $response;

        $chart_opts = [
            'chart'         => ['margin' => $margins, 'style' => ['fontSize' => '0.9375rem']],
            'credits'       => ['enabled' => false],
            'exporting'     => ['enabled' => false],
            'accessibility' => ['enabled' => false],
            'title'         => ['text' => $chart_data['title'], 'style' => ['fontSize' => '16px']],
            'xAxis'         => ['categories' => $categories, 'visible' => true, 'labels' => ['autoRotationLimit' => 50], 'tickWidth' => 1],
            'yAxis'         => ['title' => ['text' => $y_axis_title], 'plotLines' => [['value' => 0, 'width' => 1]]],
            'tooltip'       => ['pointFormat' => '<b>Events: {point.y}</b>'],
            'legend'        => ['layout' => 'vertical', 'align' => 'right', 'verticalAlign' => 'top', 'x' => 10, 'y' => -10, 'borderColor' => '#ccc', 'borderWidth' => 1, 'borderRadius' => 6, 'backgroundColor' => '#fff', 'floating' => true, 'enabled' => $show_legend],
            'series'        => $series
        ];

        $header_fields = array(
            'Content-Type: application/json',
            'Accept: image/png'
        );

        $post_fields = array(
            'infile'        => $chart_opts,
            'globalOptions' => ['colors' => $this->colors]
        );

        $response = $this->call_api('POST', $this->url, $header_fields, json_encode($post_fields));

        if ($response['result'] !== FALSE)
        {
            if ($response['http_code'] === 200)
            {
                return array(
                    'success'   => TRUE,
                    'type'      => 'image/png',
                    'response'  => $response['result']
                );
            }
            else
            {
                return array(
                    'success'   => FALSE,
                    'response'  => $response
                );
            }
        }
        else
        {
            return array(
                'success'   => FALSE,
                'response'  => 'cURL errno: '.$response['errno'].', cURL error: '.$response['error']
            );
        }
    }

    private function call_api($method, $url, $header_fields, $post_fields = NULL)
    {
        $this->ch = curl_init();

        switch ($method)
        {
            case 'POST':
                curl_setopt($this->ch, CURLOPT_POST, true);

                if (isset($post_fields))
                {
                    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_fields);
                }

                break;
            case 'PUT':
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'PUT');

                if (isset($post_fields))
                {
                    curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post_fields);
                }

                break;
            case 'DELETE':
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        if (is_array($header_fields))
        {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header_fields);
        }

        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 5);
        //curl_setopt($this->ch, CURLOPT_TIMEOUT, 10);

        if (($response['result'] = curl_exec($this->ch)) !== FALSE)
        {
            $response['http_code']  = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
        }
        else
        {
            $response['errno'] 	= curl_errno($this->ch);
            $response['error'] 	= curl_error($this->ch);
        }

        curl_close($this->ch);

        return $response;
    }
}