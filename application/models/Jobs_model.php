<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Jobs_model extends CI_Model
{
	private $redis_timeout;

	public function __construct()
	{
		parent::__construct();

		$this->redis_timeout    = 3500;
	}

	public function get_active_clients()
	{
		$this->db->db_select('quadrant_central');
		
		$this->db->select('id, client, seed_name, database, multi_client_enabled, multi_client_link_id, redis_host, redis_port, redis_password');
		$this->db->where('is_active', 1);
			
		$query = $this->db->get('clients');
	
		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data[] = $row;
			}
			
			return $data;
			
		} 
		else 
		{
			return NULL;
		}
	}

	// =====================================
	//  REPORTING FUNCTIONS
	// =====================================

	public function get_scheduled_reports()
	{
		$data = array();

		if (($client_list = $this->get_active_clients()) !== NULL)
		{
			foreach ($client_list as $client)
			{
				if ($this->db->db_select($client->database) === TRUE)
				{
					$this->db->select('*');
					$query = $this->db->get('scheduled_reports');

					if ($query->num_rows() > 0) 
					{
						foreach ($query->result() as $row) 
						{
							$row->database              = $client->database;
							$row->multi_client_enabled  = $client->multi_client_enabled;
							$row->multi_client_link_id  = $client->multi_client_link_id;

							$data[] = $row;
						}
					}
				}
			}
		}

		return !empty($data) ? $data : NULL;
	}

	function update_scheduled_report($scheduled_report_id, $snort_database, $data)
	{
		$this->db->db_select($snort_database);

		$this->db->where('id', $scheduled_report_id);
		if ($this->db->update('scheduled_reports', $data)) 
		{
			return TRUE;	
		}

		return FALSE; 
	}

	function get_number_of_pages($report_id, $snort_database)
	{
		# Check For Quadrant Standard
		if (substr($report_id, 0, 1) === 'q')
		{
			$db_connect = $this->load->database('console_general', TRUE);
			$report_id  = ltrim($report_id, 'q');
			$close_connection = TRUE;
		}
		else
		{
			$this->db->db_select($snort_database);
			$db_connect = $this->db;
		}

		$db_connect->distinct();
		$db_connect->select('page_position AS page');
		$db_connect->where('report_id', $report_id);
		$db_connect->order_by('page_position', 'asc');
		$query = $db_connect->get('rpt_user_report_elements');

		if (isset($close_connection))
		{
			$db_connect->close();
		}

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data[] = $row;
			}
			$return_array = array(
				'count' => $query->num_rows(),
				'data'  => $data
			);
			return $return_array;		
		} 
		$return_array = array(
			'count' => 0,
			'data'  => NULL
		);
		return $return_array;
	}

	function get_report_details($report_id, $client_id, $snort_database)
	{
		$this->db->db_select($snort_database);

		$this->db->select('rpt_user_reports.user_id, rpt_user_reports.id, rpt_user_reports.report_title, rpt_user_reports.start_date, rpt_user_reports.end_date, rpt_user_reports.shareable, rpt_user_reports.quadrant');
		$this->db->where('rpt_user_reports.id', $report_id);
		$query = $this->db->get('rpt_user_reports',1);	

		if ($query->num_rows() === 1) 
		{
			$modified_row = new stdClass;
			foreach($query->result() as $row) 
			{	
				$modified_row = new stdClass;
				foreach($row as $key=>$value) 
				{
					$modified_row->$key = $value;
				}	
				$modified_row->author = $this->get_report_author($row->user_id, $client_id);
				$data = $modified_row;
			}
			return $data;
		} 
		else 
		{
			# Let's check if Quadrant Report
			$cgdb = $this->load->database('console_general', TRUE);	
				$cgdb->select('rpt_user_reports.user_id, rpt_user_reports.id, rpt_user_reports.report_title, rpt_user_reports.start_date, rpt_user_reports.end_date, rpt_user_reports.shareable, rpt_user_reports.quadrant');
				$cgdb->where('rpt_user_reports.id', ltrim($report_id, 'q'));
				$query2 = $cgdb->get('rpt_user_reports',1);	
			$cgdb->close();

			if ($query2->num_rows() === 1) 
			{
				$modified_row = new stdClass;
				foreach($query2->result() as $row) 
				{	
					$modified_row = new stdClass;
					foreach($row as $key=>$value) 
					{
						$modified_row->$key = $value;
					}	
					$modified_row->author = 'Quadrant';
					$data = $modified_row;
				}
				return $data;
			} 
			else 
			{
				return NULL;
			}
		}
		return NULL;
	}

	function get_report_author($author_id, $client_id)
	{
		$authdb = $this->load->database('authentication',TRUE);
			$authdb->select("CONCAT(`user_profiles`.`first_name`, ' ', `user_profiles`.`last_name`) AS `author`");
			$authdb->join('users', 'users.id = user_profiles.user_id','inner');
			$authdb->where('users.client_id', intval($client_id));
			$authdb->where('user_profiles.user_id', intval($author_id));
			$query = $authdb->get('user_profiles',1);
		$authdb->close();

		if ($query->num_rows() === 1) 
		{
			foreach($query->result() as $row) 
			{	
				$data = $row->author;
			}
			return $data;	
		} 
		else 
		{
			return NULL;
		}
	}

	public function get_report_grid_for_print($report_id, $print_range, $range_from, $range_to, $current_page, $snort_database)
	{
		$num_pages  = $this->get_number_of_pages($report_id, $snort_database);

		$grid       = array();

		switch($print_range)
		{
			case 'all':
				$page_start = 1; 
				$page_end   = intval($num_pages['count']); 
				break;
			case 'current':
				$page_start = $current_page; 
				$page_end   = $current_page;
				break;
			case 'range':
				$page_start = $range_from; 
				$page_end   = $range_to;
				break;
		}

		$page_for_display = 1;

		for($page = $page_start; $page <= $page_end; $page++) 
		{	
			$grid_page = array(
				'page'          => intval($page),
				'page_display'  => intval($page_for_display),
				'page_elements'	=> array()
			);

			$num_rows   = $this->get_report_grid_rows($report_id, $page, $snort_database);
			for($row = 1; $row <= intval($num_rows); $row++) 
			{	
				$columns_per_row = $this->get_report_grid_columns_per_row($report_id, $row, $page, $snort_database);
				switch(intval($columns_per_row->num_columns))
				{
					case 1 	: $width = 'full-column';   break;
					case 2 	: $width = 'half-column';   break;
					case 3 	: $width = 'third-column';  break;
					default : $width = 'full-column';   break;
				}
				$grid_row = array(
					'row'           => $row,
					'columns'       => $columns_per_row->num_columns,
					'column_width' 	=> $width
				);
				array_push($grid_page['page_elements'], $grid_row);		
			}
			array_push($grid, $grid_page);
			$page_for_display++;
		}	
		return $grid;
	}

	public function get_report_grid_rows($report_id, $page, $snort_database)
	{
		# Check For Quadrant Standard
		if (substr($report_id, 0, 1) === 'q')
		{
			$db_connect = $this->load->database('console_general', TRUE);
			$report_id  = ltrim($report_id, 'q');
			$close_connection = TRUE;
		}
		else
		{
			$this->db->db_select($snort_database);
			$db_connect = $this->db;
		}

		$db_connect->select('IFNULL(MAX(`row_position`), 1) AS `num_rows`', FALSE);
		$db_connect->where('report_id', $report_id);
		$db_connect->where('page_position', $page);
		$query = $db_connect->get('rpt_user_report_elements', 1);

		if (isset($close_connection))
		{
			$db_connect->close();
		}

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data = $row->num_rows;
			}
			return $data;	
		} 
		else 
		{
			return NULL;
		}
	}

	public function get_report_grid_columns_per_row($report_id, $row, $page, $snort_database)
	{
		# Check For Quadrant Standard
		if (substr($report_id, 0, 1) === 'q')
		{
			$db_connect = $this->load->database('console_general', TRUE);
			$report_id  = ltrim($report_id, 'q');
			$close_connection = TRUE;
		}
		else
		{
			$this->db->db_select($snort_database);
			$db_connect = $this->db;
		}

		$db_connect->select('IFNULL(MAX(`column_position`), 1) AS `num_columns`', FALSE);	
		$db_connect->where('report_id', $report_id);
		$db_connect->where('row_position', $row);
		$db_connect->where('page_position', $page);
		$query = $db_connect->get('rpt_user_report_elements');

		if (isset($close_connection))
		{
			$db_connect->close();
		}

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data = $row;
			}
			return $data;	
		} 
		else 
		{
			return NULL;
		}
	}

	function get_report_elements_for_print($report_id, $page_from, $page_to, $snort_database)
	{
		# Check For Quadrant Standard
		if (substr($report_id, 0, 1) === 'q')
		{
			$db_connect = $this->load->database('console_general', TRUE);
			$report_id  = ltrim($report_id, 'q');
			$close_connection = TRUE;
		}
		else
		{
			$this->db->db_select($snort_database);
			$db_connect = $this->db;
		}

		$db_connect->select('rpt_user_report_elements.id, rpt_user_report_elements.metric_id, rpt_user_report_elements.chart_id, rpt_user_report_elements.row_position, rpt_user_report_elements.column_position, rpt_user_report_elements.page_position');
		$db_connect->where('rpt_user_report_elements.report_id', $report_id);
		$db_connect->where("`rpt_user_report_elements`.`page_position` BETWEEN $page_from AND $page_to", NULL, FALSE);
		$query = $db_connect->get('rpt_user_report_elements');

		if (isset($close_connection))
		{
			$db_connect->close();
		}

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data[] = $row;
			}
			$return_array = array(
				'count' => $query->num_rows(),
				'data'  => $data
			);
			return $return_array;		
		} 
		$return_array = array(
			'count' => 0,
			'data'  => NULL
		);
		return $return_array;
	}

	function get_report_element_info($report_id, $element_id, $snort_database)
	{
		# Check For Quadrant Standard
		if (substr($report_id, 0, 1) === 'q')
		{
			$db_connect = $this->load->database('console_general', TRUE);
			$report_id  = ltrim($report_id, 'q');
			$close_connection = TRUE;
		}
		else
		{
			$this->db->db_select($snort_database);
			$db_connect = $this->db;
		}

		$db_connect->select('rpt_user_report_elements.id, rpt_user_report_elements.metric_id, rpt_user_report_elements.chart_id, rpt_user_report_elements.row_position, rpt_user_report_elements.column_position, rpt_user_report_elements.page_position');
		$db_connect->where('rpt_user_report_elements.report_id', $report_id);
		$db_connect->where('rpt_user_report_elements.id', $element_id);
		$query = $db_connect->get('rpt_user_report_elements',1);

		if (isset($close_connection))
		{
			$db_connect->close();
		}

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$modified_row = new stdClass;
				foreach($row as $key=>$value) 
				{
					$modified_row->$key = $value;
				}	
				$modified_row->metric_title = $this->get_metric_title($row->metric_id);
				$data = $modified_row;
			}
			return $data;	
		} 
		else 
		{
			return NULL;
		}
	}

	function get_metric_title($metric_id)
	{
		$cgdb = $this->load->database('console_general', TRUE);
			$cgdb->select('metric_title');
			$cgdb->where('id', $metric_id);
			$query = $cgdb->get('rpt_metric_types', 1);
		$cgdb->close();

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data = $row->metric_title;
			}
			return $data;	
		} 
		else 
		{
			return NULL;
		}
	}

	function get_metric_information($metric_id)
	{
		$cgdb = $this->load->database('console_general', TRUE);
			// temp select for update
			$cgdb->select('metric_title, metric_code, sql');
			$cgdb->where('id', $metric_id);
			$query = $cgdb->get('rpt_metric_types', 1);
		$cgdb->close();

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data = $row;
			}
			return $data;	
		} 
		else 
		{
			return NULL;
		}
	}

	function get_chart_code($chart_id)
	{
		$cgdb = $this->load->database('console_general', TRUE);	
			$cgdb->select('chart_code');
			$cgdb->where('id', $chart_id);
			$query = $cgdb->get('rpt_chart_types', 1);
		$cgdb->close();

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data = $row->chart_code;
			}
			return $data;	
		} 
		else 
		{
			return NULL;
		}
	}

	function save_element_changes($report_id, $element_id, $data, $snort_database)
	{
		$this->db->db_select($snort_database);

		$this->db->where('id', $element_id);
		$this->db->where('report_id', $report_id);
		if ($this->db->update('rpt_user_report_elements', $data)) 
		{
			return TRUE;	
		}
		return FALSE; 
	}

	public function report_data_puller($metric_id, $sql_vars, $customer_seed, $user_timezone)
	{
		$this->load->model('console/reporting_elastic_model', 'rep_elastic');

		$this->rep_elastic->set_elastic_index($customer_seed);
		$this->rep_elastic->set_user_timezone($user_timezone);

		return $this->rep_elastic->report_data_puller($metric_id, $sql_vars);
	}

	public function get_email_distribution_list($client_id, $multi_console, $multi_link_id)
	{
		if ($multi_console)
		{
			$ids = $this->get_linked_client_ids(TRUE, $client_id, $multi_link_id);
		}

		$authdb = $this->load->database('authentication',TRUE);

			$authdb->select('user_profiles.first_name, user_profiles.last_name, user_profiles.office_country, user_profiles.office_phone, user_profiles.office_phone_ext');	
			$authdb->select('users.id AS user_id, users.email, users.mobile, users.country, users.code, users.client_id');	
			$authdb->select('email_distribution_list.priority, email_distribution_list.alerts, email_distribution_list.maintenance, email_distribution_list.reports_24_hour, email_distribution_list.reports_executive');
			$authdb->select('m.calling_code AS country_mobile');	
			$authdb->select('o.calling_code AS country_office');
			$authdb->select('user_groups.security_group_id');

			$authdb->join('users', 'users.id = user_profiles.user_id','inner');
			$authdb->join('user_groups', 'user_groups.user_id = users.id','inner');
			$authdb->join('email_distribution_list', 'email_distribution_list.user_id = users.id','inner');
			$authdb->join('country_codes m', 'm.iso2 = users.country','left');
			$authdb->join('country_codes o', 'o.iso2 = user_profiles.office_country','left');

			if ($multi_console)
			{
				$authdb->select('clients.client');
				$authdb->join('clients', 'clients.id = users.client_id','inner');

				$authdb->where_in('users.client_id', $ids);
				//$authdb->where('IF(`users`.`client_id` = '.intval($this->session->userdata('active_client_id')).', `user_profiles`.`on_email_distribution_list` = 1, `user_clients`.`on_email_distribution_list` = 1)',NULL,FALSE);
			}
			else
			{
				$authdb->where('users.client_id', intval($client_id));
				//$authdb->where('user_profiles.on_email_distribution_list', 1);
			}

			$authdb->where('email_distribution_list.client_id', intval($client_id));
			$authdb->where('users.banned', 0);
			$authdb->where('user_groups.application_id', 1);

			$authdb->order_by('email_distribution_list.priority', 'ASC');

			$query = $authdb->get('user_profiles');

		$authdb->close();

		if ($query->num_rows() > 0) 
		{
			foreach($query->result() as $row) 
			{	
				$data[] = $row;
			}
			$return_array = array(
				'count' => $query->num_rows(),
				'data'  => $data
			);
			return $return_array;		
		} 
		$return_array = array(
			'count' => 0,
			'data'  => NULL
		);
		return $return_array;

	}

	public function get_linked_client_ids($include_active_client, $client_id, $multi_link_id)	
	{
		$authdb = $this->load->database('authentication',TRUE);
			$authdb->select('client_companies.client_id AS id');
			$authdb->where('link_id', $multi_link_id);
			$query = $authdb->get('client_companies');
		$authdb->close();

		if ($query->num_rows() > 0) 
		{
			$return_ids = array();

			foreach($query->result() as $row) 
			{	
				if ($include_active_client)
				{
					array_push($return_ids, intval($row->id));
				}
				else
				{
					if (intval($row->id) !== intval($client_id))
					{
						array_push($return_ids, intval($row->id));
					}
				}			
			}			
			return $return_ids;

		}
		return NULL;
	}

	public function get_last_quarter_dates()
	{
		$dt     = new DateTime();
		$year   = intval($dt->format('Y'));
		$month  = intval($dt->format('m'));
		$dates  = new stdClass();

		if ($month >= 1 && $month <= 3)  // Jan - Mar
		{
			$dates->start_date  = ($year - 1).'-10-01';
			$dates->end_date    = (new DateTime('12/01/'.($year - 1)))->modify('last day of this month')->format('Y-m-d');
			$dates->quarter     = 'Q4';
		}
		else if ($month >= 4 && $month <= 6)  // Apr - Jun
		{
			$dates->start_date  = $year.'-01-01';
			$dates->end_date    = (new DateTime('03/01/'.$year))->modify('last day of this month')->format('Y-m-d');
			$dates->quarter     = 'Q1';
		}
		else if ($month >= 7 && $month <= 9)  // Jul - Sep
		{
			$dates->start_date  = $year.'-04-01';
			$dates->end_date    = (new DateTime('06/01/'.$year))->modify('last day of this month')->format('Y-m-d');
			$dates->quarter     = 'Q2';
		}
		else if ($month >= 10 && $month <= 12)  // Oct - Dec
		{
			$dates->start_date  = $year.'-07-01';
			$dates->end_date    = (new DateTime('09/01/'.$year))->modify('last day of this month')->format('Y-m-d');
			$dates->quarter     = 'Q3';
		}

		return $dates;
	}
}