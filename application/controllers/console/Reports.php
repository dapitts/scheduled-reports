<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller 
{
	private $customer_title;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->tank_auth->check_login_status();
		
		$this->utility->check_section_access('reports');
		
		$this->customer_seed 	= rtrim($this->config->item('seed_name'),'-');
		$this->elastic_index 	= rtrim($this->config->item('seed_name'),'-'); 
		$this->customer_title 	= $this->session->userdata('active_client_title');
		$this->user_timezone 	= $this->session->userdata('user_timezone');

		$this->load->model('console/reporting_elastic_model', 'reporting');
		$this->load->library('cron');
	}
	
	public function index()
	{
		if (($saved_reports = $this->reporting->get_saved_reports()) !== NULL)
		{
			$current_time = new DateTime();

			foreach ($saved_reports as $report)
			{
				if ($report->scheduled)
				{
					try
					{
						$this->cron->setExpression($report->cron_expression);

						$report->last_run   = isset($report->last_run) ? date('Y-m-d H:i:s', strtotime($report->last_run)) : '&nbsp;';
						$report->next_run   = $this->cron->getNextRunDate($current_time, 0, FALSE, $this->user_timezone)->format('Y-m-d H:i:s');
					}
					catch (Exception $e)
					{
						$report->next_run   = 'N/A';
					}
				}
			}
		}

		$data['saved_report_list']      = $saved_reports;
		$data['shared_report_list']     = $this->reporting->get_shared_reports();
		$data['quadrant_report_list']   = $this->reporting->get_quadrant_reports();

		$this->load->view('assets/header');
		$this->load->view('console/reports/start', $data);
		$this->load->view('assets/footer');
		
	}

	public function create_scheduled_report($report_id)
	{
		if (!$this->url_hasher->_verify_uri_hash())
		{
			$this->load->view('console/reports/tamper');
		}
		else
		{
			$data['hour_dropdown']          = $this->hour_dropdown();
			$data['set_hour']               = '0';
			$data['frequency_dropdown']     = $this->frequency_dropdown();
			$data['set_frequency']          = 'monthly';
			$data['report_runs']            = $this->report_runs($data['set_frequency'], $data['set_hour']);
			$data['report_id']              = $report_id;
			$data['report_details']         = $this->reporting->get_report_details($report_id);
			$data['user_id']                = $this->session->userdata('user_id');
			$data['user_timezone']          = $this->user_timezone;
			$data['customer_seed']          = $this->customer_seed;
			$data['customer_title']         = $this->customer_title;
			$data['client_id']              = $this->session->userdata('active_client_id');

			$this->load->view('console/reports/create_scheduled_report', $data);
		}
	}

	public function do_create_scheduled_report()
	{
		$this->form_validation->set_rules('hour', 'Time', 'trim|required|integer');
		$this->form_validation->set_rules('frequency', 'Frequency', 'trim|required');
		$this->form_validation->set_rules('report_id', 'Report ID', 'trim|required');
		$this->form_validation->set_rules('report_type', 'Report Type', 'trim|required|in_list[executive]');
		$this->form_validation->set_rules('user_id', 'User ID', 'trim|required|integer');
		$this->form_validation->set_rules('user_timezone', 'User Timezone', 'trim|required');
		$this->form_validation->set_rules('customer_seed', 'Customer Seed', 'trim|required');
		$this->form_validation->set_rules('customer_title', 'Customer Title', 'trim|required');
		$this->form_validation->set_rules('client_id', 'Client ID', 'trim|required|integer');
		$this->form_validation->set_rules('referer', 'Referer', 'trim');

		if ($this->form_validation->run()) 
		{
			$error_msg  = '';
			$frequency  = $this->input->post('frequency');

			try
			{
				$this->cron->setPart($this->cron::MINUTE, '0');
				$this->cron->setPart($this->cron::HOUR, $this->input->post('hour'));

				switch ($frequency)
				{
					case 'weekly':
						$date_range = 'last-7-days';

						$this->cron->setPart($this->cron::DAY, '*');
						$this->cron->setPart($this->cron::MONTH, '*');
						$this->cron->setPart($this->cron::WEEKDAY, '1');
						break;
					case 'biweekly':
						$date_range = 'last-14-days';

						$this->cron->setPart($this->cron::DAY, '1,15');
						$this->cron->setPart($this->cron::MONTH, '*');
						$this->cron->setPart($this->cron::WEEKDAY, '*');
						break;
					case 'monthly':
						$date_range = 'last-month';

						$this->cron->setPart($this->cron::DAY, '1');
						$this->cron->setPart($this->cron::MONTH, '*');
						$this->cron->setPart($this->cron::WEEKDAY, '*');
						break;
					case 'quarterly':
						$date_range = 'last-quarter';

						$this->cron->setPart($this->cron::DAY, '1');
						$this->cron->setPart($this->cron::MONTH, '1,4,7,10');
						$this->cron->setPart($this->cron::WEEKDAY, '*');
						break;
				}
			}
			catch (Exception $e)
			{
				$error_msg  .= '<p>'.$e->getMessage().'</p>';
			}

			if ($error_msg !== '')
			{
				# Set Error
				$response = array(
					'success'       => false,
					'message'       => $error_msg,
					'csrf_name'     => $this->security->get_csrf_token_name(),
					'csrf_value'    => $this->security->get_csrf_hash()
				);
				echo json_encode($response);
			}
			else
			{
				$report_id      = $this->input->post('report_id');
				$referer        = $this->input->post('referer');
				$report_details = $this->reporting->get_report_details($report_id);

				$report_data = array(
					'report_id'         => $report_id,
					'report_type'       => $this->input->post('report_type'),
					'user_id'           => $this->input->post('user_id'),
					'user_timezone'     => $this->input->post('user_timezone'),
					'customer_seed'     => $this->input->post('customer_seed'),
					'customer_title'    => $this->input->post('customer_title'),
					'client_id'         => $this->input->post('client_id'),
					'date_range'        => $date_range,
					'cron_expression'   => $this->cron->getExpression(),
					'email_dist_list'   => $this->input->post('email_dist_list') ?? '0'
				);

				$rv = $this->reporting->create_scheduled_report($report_data);

				if ($rv['success'])
				{
					if (!empty($referer))
					{
						if ($referer === 'start')
						{
							# Set Success Alert Response
							$this->session->set_userdata('my_flash_message_type', 'success');
							$this->session->set_userdata('my_flash_message', '<p>The schedule for report: <strong>'.$report_details->report_title.'</strong>, has been successfully created.</p>');

							$response = array(
								'success'   => true,
								'redirect'  => true,
								'goto_url'  => '/console/reports'
							);
							echo json_encode($response);
						}		
					}
					else
					{
						$scheduled_report_id    = $rv['scheduled_report_id'];
						$modify_link_href       = '/console/reports/modify_scheduled_report/'.$scheduled_report_id.'/'.$this->url_hasher->_create_hash_key('console/reports/modify_scheduled_report',$scheduled_report_id);
						$delete_link_href       = '/console/reports/delete_scheduled_report/'.$scheduled_report_id.'/'.$report_id.'/'.$this->url_hasher->_create_hash_key('console/reports/delete_scheduled_report',$scheduled_report_id.'/'.$report_id);

						$response = array(
							'success'           => true,
							'redirect'          => false,
							'modify_link_href'  => $modify_link_href,
							'modify_link_text'  => 'Modify Scheduled Report',
							'delete_link_href'  => $delete_link_href,
							'delete_link_text'  => 'Delete Scheduled Report',
							'message'           => '<p>The schedule has been successfully created.</p>'
						);
						echo json_encode($response);
					}			
				}
				else
				{
					# Set Error
					$response = array(
						'success'       => false,
						'message'       => '<p>Oops, something went wrong.</p>',
						'csrf_name'     => $this->security->get_csrf_token_name(),
						'csrf_value'    => $this->security->get_csrf_hash()
					);
					echo json_encode($response);
				}
			}
		}
		else
		{
			$response = array(
				'success'       => false,
				'message'       => validation_errors(),
				'csrf_name'     => $this->security->get_csrf_token_name(),
				'csrf_value'    => $this->security->get_csrf_hash()
			);
			echo json_encode($response);
		}
	}

	public function modify_scheduled_report($scheduled_report_id)
	{
		if (!$this->url_hasher->_verify_uri_hash())
		{
			$this->load->view('console/reports/tamper');
		}
		else
		{
			if (($scheduled_report = $this->reporting->get_scheduled_report($scheduled_report_id)) !== NULL)
			{
				$cron_expression = explode(' ', $scheduled_report->cron_expression);

				switch ($scheduled_report->date_range)
				{
					case 'last-7-days':
						$frequency  = 'weekly';
						break;
					case 'last-14-days':
						$frequency  = 'biweekly';
						break;
					case 'last-month':
					case 'last-30-days':
						$frequency  = 'monthly';
						break;
					case 'last-quarter':
					case 'last-90-days':
						$frequency  = 'quarterly';
						break;
				}

				$data['hour_dropdown']          = $this->hour_dropdown();
				$data['set_hour']               = $cron_expression[$this->cron::HOUR];
				$data['frequency_dropdown']     = $this->frequency_dropdown();
				$data['set_frequency']          = $frequency;
				$data['report_runs']            = $this->report_runs($frequency, $data['set_hour']);
				$data['report_id']              = $scheduled_report->report_id;
				$data['scheduled_report_id']    = $scheduled_report_id;
				$data['report_details']         = $this->reporting->get_report_details($scheduled_report->report_id);
				$data['email_dist_list']        = $scheduled_report->email_dist_list;

				$this->load->view('console/reports/modify_scheduled_report', $data);
			}
		}
	}

	public function do_modify_scheduled_report()
	{
		$this->form_validation->set_rules('hour', 'Time', 'trim|required|integer');
		$this->form_validation->set_rules('frequency', 'Frequency', 'trim|required');
		$this->form_validation->set_rules('report_id', 'Report ID', 'trim|required');
		$this->form_validation->set_rules('scheduled_report_id', 'Scheduled Report ID', 'trim|required|integer');
		$this->form_validation->set_rules('referer', 'Referer', 'trim');

		if ($this->form_validation->run()) 
		{
			$error_msg  = '';
			$frequency  = $this->input->post('frequency');

			try
			{
				$this->cron->setPart($this->cron::MINUTE, '0');
				$this->cron->setPart($this->cron::HOUR, $this->input->post('hour'));

				switch ($frequency)
				{
					case 'weekly':
						$date_range = 'last-7-days';

						$this->cron->setPart($this->cron::DAY, '*');
						$this->cron->setPart($this->cron::MONTH, '*');
						$this->cron->setPart($this->cron::WEEKDAY, '1');
						break;
					case 'biweekly':
						$date_range = 'last-14-days';

						$this->cron->setPart($this->cron::DAY, '1,15');
						$this->cron->setPart($this->cron::MONTH, '*');
						$this->cron->setPart($this->cron::WEEKDAY, '*');
						break;
					case 'monthly':
						$date_range = 'last-month';

						$this->cron->setPart($this->cron::DAY, '1');
						$this->cron->setPart($this->cron::MONTH, '*');
						$this->cron->setPart($this->cron::WEEKDAY, '*');
						break;
					case 'quarterly':
						$date_range = 'last-quarter';

						$this->cron->setPart($this->cron::DAY, '1');
						$this->cron->setPart($this->cron::MONTH, '1,4,7,10');
						$this->cron->setPart($this->cron::WEEKDAY, '*');
						break;
				}
			}
			catch (Exception $e)
			{
				$error_msg  .= '<p>'.$e->getMessage().'</p>';
			}

			if ($error_msg !== '')
			{
				# Set Error
				$response = array(
					'success'       => false,
					'message'       => $error_msg,
					'csrf_name'     => $this->security->get_csrf_token_name(),
					'csrf_value'    => $this->security->get_csrf_hash()
				);
				echo json_encode($response);
			}
			else
			{
				$report_id              = $this->input->post('report_id');
				$scheduled_report_id    = $this->input->post('scheduled_report_id');
				$referer                = $this->input->post('referer');
				$report_details         = $this->reporting->get_report_details($report_id);

				$report_data = array(
					'date_range'        => $date_range,
					'cron_expression'   => $this->cron->getExpression(),
					'email_dist_list'   => $this->input->post('email_dist_list') ?? '0'
				);

				$check_success  = $this->reporting->update_scheduled_report($scheduled_report_id, $report_data);

				if ($check_success)
				{
					if (!empty($referer))
					{
						if ($referer === 'start')
						{
							# Set Success Alert Response
							$this->session->set_userdata('my_flash_message_type', 'success');
							$this->session->set_userdata('my_flash_message', '<p>The schedule for report: <strong>'.$report_details->report_title.'</strong>, has been successfully updated.</p>');

							$response = array(
								'success'   => true,
								'redirect'  => true,
								'goto_url'  => '/console/reports'
							);
							echo json_encode($response);
						}		
					}
					else
					{
						$response = array(
							'success'   => true,
							'redirect'  => false,
							'message'   => '<p>The schedule has been successfully updated.</p>'
						);
						echo json_encode($response);
					}				
				}
				else
				{
					# Set Error
					$response = array(
						'success'       => false,
						'message'       => '<p>Oops, something went wrong.</p>',
						'csrf_name'     => $this->security->get_csrf_token_name(),
						'csrf_value'    => $this->security->get_csrf_hash()
					);
					echo json_encode($response);
				}
			}
		}
		else
		{
			$response = array(
				'success'       => false,
				'message'       => validation_errors(),
				'csrf_name'     => $this->security->get_csrf_token_name(),
				'csrf_value'    => $this->security->get_csrf_hash()
			);
			echo json_encode($response);
		}
	}

	public function delete_scheduled_report($scheduled_report_id, $report_id)
	{
		if (!$this->url_hasher->_verify_uri_hash())
		{
			$this->load->view('console/reports/tamper');
		}
		else
		{
			$data['scheduled_report_id']    = $scheduled_report_id;
			$data['report_details']         = $this->reporting->get_report_details($report_id);

			$this->load->view('console/reports/delete_scheduled_report', $data);
		}
	}

	public function do_delete_scheduled_report()
	{
		$this->form_validation->set_rules('report_id', 'Report ID', 'trim|required');
		$this->form_validation->set_rules('scheduled_report_id', 'Scheduled Report ID', 'trim|required|integer');
		$this->form_validation->set_rules('referer', 'Referer', 'trim');

		if ($this->form_validation->run()) 
		{
			# Assign Variables
			$report_id              = $this->input->post('report_id');
			$scheduled_report_id    = $this->input->post('scheduled_report_id');
			$referer                = $this->input->post('referer');
			# First Get Existing Data->Title 
			$report_details         = $this->reporting->get_report_details($report_id);
			# Do Scheduled Report Delete
			$check_success          = $this->reporting->delete_scheduled_report($scheduled_report_id, $report_id);

			if ($check_success)
			{
				if (!empty($referer))
				{
					if ($referer === 'start')
					{
						# Set Success Alert Response
						$this->session->set_userdata('my_flash_message_type', 'success');
						$this->session->set_userdata('my_flash_message', '<p>The schedule for report: <strong>'.$report_details->report_title.'</strong>, has been successfully deleted.</p>');

						$response = array(
							'success'   => true,
							'redirect'  => true,
							'goto_url'  => '/console/reports'
						);
						echo json_encode($response);
					}		
				}
				else
				{
					$create_link_href   = '/console/reports/create_scheduled_report/'.$report_id.'/'.$this->url_hasher->_create_hash_key('console/reports/create_scheduled_report',$report_id);

					$response = array(
						'success'           => true,
						'redirect'          => false,
						'create_link_href'  => $create_link_href,
						'create_link_text'  => 'Create Scheduled Report',
						'message'           => '<p>The schedule has been successfully deleted.</p>'
					);
					echo json_encode($response);
				}				
			}
			else
			{
				# Set Error
				$response = array(
					'success'   => false,
					'message'   => '<p>Oops, something went wrong.</p>'
				);
				echo json_encode($response);
			}
		}
		else
		{
			$response = array(
				'success'   => false,
				'message'   => validation_errors()
			);
			echo json_encode($response);
		}
	}

	private function day_of_week_dropdown()
	{
		$day_of_week_list = array(
			'*' => 'All',
			0   => 'Sun',
			1   => 'Mon',
			2   => 'Tue',
			3   => 'Wed',
			4   => 'Thu',
			5   => 'Fri',
			6   => 'Sat'
		);

		return $day_of_week_list;
	}

	private function month_dropdown()
	{
		$month_list = array(
			'*' => 'All',
			1   => 'Jan',
			2   => 'Feb',
			3   => 'Mar',
			4   => 'Apr',
			5   => 'May',
			6   => 'Jun',
			7   => 'Jul',
			8   => 'Aug',
			9   => 'Sep',
			10  => 'Oct',
			11  => 'Nov',
			12  => 'Dec'
		);

		return $month_list;
	}

	private function day_of_month_dropdown()
	{
		$day_of_month_list = array();

		for ($i = 1; $i < 32; $i++)
		{
			$day_of_month_list[$i] = $i;
		}

		$day_of_month_list['L'] = 'Last';

		return $day_of_month_list;
	}

	private function hour_dropdown()
	{
		$hour_list = array(
			0   => '12:00 AM',
			1   => '1:00 AM',
			2   => '2:00 AM',
			3   => '3:00 AM',
			4   => '4:00 AM',
			5   => '5:00 AM',
			6   => '6:00 AM',
			7   => '7:00 AM',
			8   => '8:00 AM',
			9   => '9:00 AM',
			10  => '10:00 AM',
			11  => '11:00 AM',
			12  => '12:00 PM',
			13  => '1:00 PM',
			14  => '2:00 PM',
			15  => '3:00 PM',
			16  => '4:00 PM',
			17  => '5:00 PM',
			18  => '6:00 PM',
			19  => '7:00 PM',
			20  => '8:00 PM',
			21  => '9:00 PM',
			22  => '10:00 PM',
			23  => '11:00 PM'
		);

		return $hour_list;
	}

	private function minute_dropdown()
	{
		$minute_list = array();

		for ($i = 0; $i < 60; $i++)
		{
			$minute_list[$i] = $i;
		}

		return $minute_list;
	}

	private function date_range_dropdown()
	{
		$date_range_list = array(
			'last-14-days'  => 'Last 14 Days',
			'last-30-days'  => 'Last 30 Days',
			'last-90-days'  => 'Last 90 Days',
			'last-month'    => 'Last Month',
			'last-quarter'  => 'Last Quarter'
		);

		return $date_range_list;
	}

	private function frequency_dropdown()
	{
		$frequency_list = array(
			'weekly'    => 'Weekly',
			'biweekly'  => 'Biweekly',
			'monthly'   => 'Monthly',
			'quarterly' => 'Quarterly'
		);

		return $frequency_list;
	}

	private function report_runs($frequency, $hour)
	{
		switch ($frequency)
		{
			case 'weekly':
				$run_date   = 'Every Monday';
				break;
			case 'biweekly':
				$run_date   = '1st and 15th of every month';
				break;
			case 'monthly':
				$run_date   = '1st of every month';
				break;
			case 'quarterly':
				$run_date   = '1st of January, April, July and October';
				break;
		}

		$run_time = $this->hour_dropdown()[intval($hour)];

		return $run_date.' at '.$run_time;
	}

	private function validate_month($month)
	{
		$len    = count($month);
		$errors = '';

		if ($len > 1 && $month[0] === '*')
		{
			$errors .= '<p>Month - "All" value cannot be used with other values.</p>';
		}

		return $errors;
	}

	private function validate_day_of_month($month, $day_of_month)
	{
		$dom_len    = count($day_of_month);
		$last_dom   = $day_of_month[$dom_len - 1];
		$errors     = '';

		if ($last_dom === 'L')  // Last
		{
			if ($dom_len > 1)
			{
				return '<p>Day - "Last" value cannot be used with other values.</p>';
			}
			else
			{
				return '';
			}
		}
		else
		{
			foreach (array_reverse($day_of_month) as $day)
			{
				switch (intval($day))
				{
					case 31:
						if ($month[0] === '*')
						{
							$errors .= '<p>Day - invalid day 31 (Feb, Apr, Jun, Sep, Nov).</p>';
						}
						else
						{
							$months = [];

							foreach ($month as $mo)
							{
								switch (intval($mo))
								{
									case 2:
										$months[] = 'Feb';
										break;
									case 4:
										$months[] = 'Apr';
										break;
									case 6:
										$months[] = 'Jun';
										break;
									case 9:
										$months[] = 'Sep';
										break;
									case 11:
										$months[] = 'Nov';
										break;
								}
							}

							$len = count($months);

							if ($len > 1)
							{
								$errors .= '<p>Day - invalid day 31 ('.implode(', ', $months).').</p>';
							}
							else if ($len === 1)
							{
								$errors .= '<p>Day - invalid day 31 ('.$months[0].').</p>';
							}
						}
						break;
					case 30:
						if ($month[0] === '*' || in_array('2', $month))
						{
							$errors .= '<p>Day - invalid day 30 (Feb).</p>';
						}
						break;
					case 29:
						if (date('L') !== '1' && ($month[0] === '*' || in_array('2', $month)))  // Not a leap year
						{
							$errors .= '<p>Day - invalid day 29 (Feb).</p>';
						}
						break;
					default:
						break 2;
				}
			}

			return $errors;
		}
	}
}