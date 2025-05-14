<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Crons extends CI_Controller 
{
	function __construct()
	{
		parent::__construct();

		$this->load->model('Jobs_model', 'jobs');
	}

	private function get_report_element($element_vars, $rpt_params)
	{
		$auto_save = FALSE;

		$init_report    = $element_vars['init'];
		$report_id      = $element_vars['report_id'];
		$element_id     = $element_vars['element_id'];
		$chart_id       = $element_vars['chart'];
		$start_date     = $element_vars['start_date'];
		$end_date       = $element_vars['end_date'];
		$use_basic      = $element_vars['use_basic'];

		if (isset($element_vars['metric']))
		{
			$metric_id = $element_vars['metric'];
		}

		$elm            = $this->jobs->get_report_element_info($report_id, $element_id, $rpt_params->database);

		$columns_in_row = $this->jobs->get_report_grid_columns_per_row($report_id, $elm->row_position, $elm->page_position, $rpt_params->database);

		switch (intval($columns_in_row->num_columns))
		{
			case 1 	: $element_column_width = 'col-md-12'; break;
			case 2 	: $element_column_width = 'col-md-6';  break;
			case 3 	: $element_column_width = 'col-md-4';  break;
			default : $element_column_width = 'col-md-12'; break;
		}			

		if (!empty($metric_id))
		{	
			$use_metric_id      = $metric_id;
			$new_metric         = $this->jobs->get_metric_information($metric_id);
			$use_metric_title   = $new_metric->metric_title;
			$use_metric_sql     = $new_metric->sql;
			$auto_save          = TRUE;
		}
		else
		{
			$use_metric_id      = $elm->metric_id;
			$get_metric_info    = $this->jobs->get_metric_information($elm->metric_id);
			$use_metric_title   = $get_metric_info->metric_title;
			$use_metric_sql     = $get_metric_info->sql;
		}

		$page_position      = $elm->page_position;		
		$row_position       = $elm->row_position;
		$column_position    = $elm->column_position;

		if ($use_basic === 'true')
		{
			$chart_position = 'p'.$page_position.'r'.$row_position.'c'.$column_position;
		}
		else
		{
			$chart_position = 'r'.$row_position.'c'.$column_position;
		}

		$chart_data['title']    = $use_metric_title;

		$sql_vars = array(
			'customer_title'    => $rpt_params->customer_title,
			'cust_seed'         => $rpt_params->customer_seed,
			'start_date'        => $start_date,
			'end_date'          => $end_date
		);	

		$chart_data['chart']    = $this->jobs->report_data_puller($use_metric_id, $sql_vars, $rpt_params->customer_seed, $rpt_params->user_timezone);
		$chart_data['position'] = $chart_position;
		$chart_data['width']    = $element_column_width;

		if (!is_null($chart_id))
		{
			$use_chart_id   = $chart_id;
			$use_chart_code = $this->jobs->get_chart_code($chart_id);
			$auto_save      = TRUE;
		}
		else
		{
			$use_chart_id   = $elm->chart_id;
			$use_chart_code = $elm->chart_code;
		}

		if ($auto_save)
		{
			if ($init_report === 'false')
			{
				// do auto save for changes to report element
				$new_element_data = array(
					'metric_id'         => $use_metric_id,
					'chart_id'          => $use_chart_id,
					'row_position'      => $row_position,
					'column_position'   => $column_position
				);
				$this->jobs->save_element_changes($report_id, $elm->id, $new_element_data, $rpt_params->database);
			}
		}

		if (is_null($chart_data['chart']))
		{
			$statement = '<span class="chart_no_results">No data to report for this date range.</span>';
			$chart_data['chart'] = array(
				'columns'   => array('test'),
				'series'    => 1,
				'data'      => urlencode($statement),
				'type'      => 'paragraph'
			);
			$use_chart_code = 'table';
		}

		switch ($use_chart_code)
		{
			case 'column-2d':
				$response = $this->highcharts_api->get_column_2d_chart($chart_data);
				break;
			case 'column-3d':
				$response = $this->highcharts_api->get_column_3d_chart($chart_data);
				break;
			case 'pie-2d':
				$response = $this->highcharts_api->get_pie_2d_chart($chart_data);
				break;
			case 'pie-3d':
				$response = $this->highcharts_api->get_pie_3d_chart($chart_data);
				break;
			case 'area':
				$response = $this->highcharts_api->get_spline_chart($chart_data);
				break;
			case 'pareto':
				$response = $this->highcharts_api->get_pareto_chart($chart_data);
				break;
			case 'line':
				$response = $this->highcharts_api->get_line_chart($chart_data);
				break;
			case 'table':
				$response = array(
					'success'   => TRUE,
					'type'      => 'table',
					'response'  => json_encode(array_merge($chart_data['chart'], array('title' => $chart_data['title'])))
				);
				break;
		}

		if ($use_chart_code !== 'table' && !$response['success'])
		{
			$statement = '<span class="chart_no_results">Unable to generate chart.</span>';

			$chart_data['chart'] = array(
				'columns'   => array('test'),
				'series'    => 1,
				'data'      => urlencode($statement),
				'type'      => 'paragraph'
			);

			$response = array(
				'success'   => TRUE,
				'type'      => 'table',
				'response'  => json_encode(array_merge($chart_data['chart'], array('title' => $chart_data['title'])))
			);
		}

		return $response;
	}

	private function executive_report($rpt_params)
	{
		date_default_timezone_set($rpt_params->user_timezone);

		$last_run       = date('Y-m-d H:i:s');
		$time_start     = microtime(TRUE);
		$report_id      = $rpt_params->report_id;
		$num_of_pages   = $this->jobs->get_number_of_pages($report_id, $rpt_params->database);
		$orientation    = 'portrait';
		$print_range    = 'all';

		switch ($rpt_params->date_range)
		{
			case 'last-7-days':
				$start_date = date('Y-m-d', strtotime('-1 week -1 day'));
				$end_date   = date('Y-m-d', strtotime('-1 day'));
				break;
			case 'last-14-days':
				$start_date = date('Y-m-d', strtotime('-2 weeks -1 day'));
				$end_date   = date('Y-m-d', strtotime('-1 day'));
				break;
			case 'last-30-days':
				$start_date = date('Y-m-d', strtotime('-30 days -1 day'));
				$end_date   = date('Y-m-d', strtotime('-1 day'));
				break;
			case 'last-90-days':
				$start_date = date('Y-m-d', strtotime('-90 days -1 day'));
				$end_date   = date('Y-m-d', strtotime('-1 day'));
				break;
			case 'last-month':
				$start_date = date('Y-m-d', strtotime('first day of previous month'));
				$end_date   = date('Y-m-d', strtotime('last day of previous month'));
				break;
			case 'last-quarter':
				$dates      = $this->jobs->get_last_quarter_dates();
				$start_date = $dates->start_date;
				$end_date   = $dates->end_date;
				break;
		}

		$range_from     = 1;
		$range_to       = intval($num_of_pages['count']);
		$current_page   = 1;

		$date_range = array(
			'start_date'    => $start_date, 
			'end_date'      => $end_date
		);

		$data['range']          = $date_range;
		$data['print_range']    = $print_range;

		$data['report_details']         = $this->jobs->get_report_details($report_id, $rpt_params->client_id, $rpt_params->database);	
		$data['the_grid']               = $this->jobs->get_report_grid_for_print($report_id, $print_range, $range_from, $range_to, $current_page, $rpt_params->database);

		$quad_logo_raw                  = file_get_contents($this->config->item('console_web_directory').'_/img/quadrant-q-flat.png');
		$data['quad_logo']              = 'data:image/png;base64,'.base64_encode($quad_logo_raw);

		$last_page_in_grid              = end($data['the_grid']);
		$data['total_printable_pages'] 	= $last_page_in_grid['page_display'];

		$report_elements    = $this->jobs->get_report_elements_for_print($report_id, $range_from, $range_to, $rpt_params->database);

		foreach ($report_elements['data'] as $element)
		{
			$element_vars = array(
				'init'          => 'true',
				'report_id'     => $report_id,
				'element_id'    => $element->id,
				'position'      => 'p'.$element->page_position.'r'.$element->row_position.'c'.$element->column_position,
				'chart'         => $element->chart_id,
				'metric'        => $element->metric_id,
				'start_date'    => $start_date,
				'end_date'      => $end_date,
				'use_basic'     => 'true',
			);

			$elm = $this->get_report_element($element_vars, $rpt_params);

			if ($elm['success'])
			{
				switch ($elm['type'])
				{
					case 'image/png':
						$data[$element_vars['position']] = 'data:image/png;base64,'.base64_encode($elm['response']);
						break;
					case 'table':
						$data[$element_vars['position']] = $elm['response'];
						break;
				}
			}
		}

		// Generate PDF
		if ($this->load->is_loaded('pdf_new'))
		{
			$this->pdf_new = new Pdf_new();
		}
		else
		{
			$this->load->library('pdf_new');
		}

		$this->pdf_new->load_view('console/reports/export/template', $data);
		$this->pdf_new->setPaper('letter', $orientation);
		$this->pdf_new->render();
		$pdf = $this->pdf_new->output();

		$time_end   = microtime(TRUE);
		$time_diff  = $time_end - $time_start;

		$report_title   = $data['report_details']->report_title;
		$report_author  = $data['report_details']->author;
		$created        = date('Y-m-d H:i:s', strtotime($rpt_params->created.' UTC'));
		$modified       = date('Y-m-d H:i:s', strtotime($rpt_params->modified.' UTC'));

		$email_body =  'Dear Customer,'.PHP_EOL;
		$email_body .= PHP_EOL;
		$email_body .= 'Attached you will find the PDF titled "'.$report_title.'" and the PDF titled "Understanding Your Report" that should be able to answer many of the questions you might have about the data content.'.PHP_EOL;
		$email_body .= PHP_EOL;
		$email_body .= 'Scheduled by: '.$report_author.PHP_EOL;
		$email_body .= 'Schedule created: '.$created;

		if ($created !== $modified)
		{
			$email_body .= PHP_EOL;
			$email_body .= 'Schedule modified: '.$modified;
		}

		$email_data = array(
			'distribution_list' => 'Executive Reports',
			'body'              => $email_body,
			'attachments'       => [
				[
					'data'      => $pdf,
					'file_name' => $report_title.'.pdf',
					'mime_type' => 'application/pdf'
				],
				[
					'data'      => file_get_contents($this->config->item('console_web_directory').'_/docs/Understanding Your Report.pdf'),
					'file_name' => 'Understanding Your Report.pdf',
					'mime_type' => 'application/pdf'
				]
			]
		);

		$email_list         = $this->jobs->get_email_distribution_list($rpt_params->client_id, $rpt_params->multi_client_enabled, $rpt_params->multi_client_link_id);
		$email_recipients   = array();

		if ($email_list['count'])
		{
			foreach ($email_list['data'] as $user)
			{
				if ($user->reports_executive)
				{
					$email_recipients[]  = $user;
				}
			}
		}

		if (count($email_recipients))
		{
			if ($rpt_params->email_dist_list === '0')
			{
				foreach ($email_recipients as $recipient)
				{
					if ($recipient->user_id === $rpt_params->user_id)
					{
						$send_mail_status = $this->utility->send_email('communication', $recipient->email, 'Executive Report', $email_data);
						break;
					}
				}
			}
			else
			{
				foreach ($email_recipients as $recipient)
				{
					$send_mail_status = $this->utility->send_email('communication', $recipient->email, 'Executive Report', $email_data);
				}
			}
		}

		$report_data = array(
			'last_run'  => $last_run
		);

		$this->jobs->update_scheduled_report($rpt_params->id, $rpt_params->database, $report_data);
	}

	public function process_reports()
	{
		if (is_cli())
		{
			$current_time = new DateTime();

			if (($scheduled_reports = $this->jobs->get_scheduled_reports()) !== NULL)
			{
				$this->load->library('cron');
				$reports_due = array();

				foreach ($scheduled_reports as $report)
				{
					try
					{
						$this->cron->setExpression($report->cron_expression);

						if ($this->cron->isDue($current_time, $report->user_timezone))
						{
							$reports_due[] = $report;
						}
					}
					catch (Exception $e)
					{
						continue;
					}
				}

				if (count($reports_due))
				{
					$this->load->library('highcharts_api');

					foreach ($reports_due as $report_params)
					{
						switch ($report_params->report_type)
						{
							case 'executive':
								$this->executive_report($report_params);
								break;
						}
					}
				}
			}
		}
	}
}