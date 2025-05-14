<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Reporting_elastic_model extends CI_Model 
{
	public function __construct()
	{
		parent::__construct();

		$this->load->library('elastic');
		$this->load->library('parser');
	}

	# |------------------------------------------------------------------------------------------------------------------------------------------------------
	# | [START] REPORTING SECTION FUNCTIONALITY
	# |------------------------------------------------------------------------------------------------------------------------------------------------------

	function update_report_scheduling($schedule, $report_id)
	{
		$this->db->set('scheduled', $schedule);
		$this->db->where('id', $report_id);
		if ($this->db->update('rpt_user_reports')) 
		{
			return TRUE;	
		}
		return FALSE; 
	}

	public function delete_report_and_schedule($report_id)
	{
		if ($this->do_delete_report($report_id))
		{
			if ($this->db->delete('scheduled_reports', array('report_id' => $report_id, 'user_id' => $this->session->userdata('user_id'))))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

    private function do_delete_report($report_id)
    {
	    if ($this->db->delete('rpt_user_reports', array('id' => $report_id, 'user_id' => $this->session->userdata('user_id'))))
	    {
		    if ($this->do_delete_report_elements($report_id))
		    {
				return TRUE;
		    }
	    }
	    return FALSE;
    }

	function get_saved_reports()
	{	
		$this->db->select('rpt_user_reports.id, rpt_user_reports.report_title, rpt_user_reports.start_date, rpt_user_reports.end_date, rpt_user_reports.shareable, rpt_user_reports.scheduled');
		$this->db->select('(SELECT MAX(`page_position`) FROM `rpt_user_report_elements` where `rpt_user_report_elements`.`report_id` = `rpt_user_reports`.`id`) AS num_of_pages', FALSE);
		$this->db->select('scheduled_reports.id AS scheduled_report_id, scheduled_reports.cron_expression, scheduled_reports.last_run');
		$this->db->join('scheduled_reports', 'scheduled_reports.report_id = rpt_user_reports.id', 'left');
		$this->db->where('rpt_user_reports.user_id', $this->session->userdata('user_id'));
		$query = $this->db->get('rpt_user_reports');		
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

	function get_scheduled_report($scheduled_report_id)
	{
		$this->db->select('id, report_id, date_range, cron_expression, email_dist_list');
		$this->db->where('id', $scheduled_report_id);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$query = $this->db->get('scheduled_reports');

		if ($query->num_rows() === 1)
		{
			foreach ($query->result() as $row)
			{	
				return $row;
			}
		} 
		else
		{
			return NULL;
		}
	}

	function get_scheduled_report_id($report_id)
	{
		$this->db->select('id');
		$this->db->where('report_id', $report_id);
		$this->db->where('user_id', $this->session->userdata('user_id'));
		$query = $this->db->get('scheduled_reports');

		if ($query->num_rows() === 1)
		{
			foreach ($query->result() as $row)
			{	
				return $row->id;
			}
		} 
		else
		{
			return NULL;
		}
	}

	function create_scheduled_report($data)
	{
		if ($this->db->insert('scheduled_reports', $data)) 
		{
			$scheduled_report_id = $this->db->insert_id();

			if ($this->update_report_scheduling(1, $data['report_id']))
			{
				return array('success' => TRUE, 'scheduled_report_id' => $scheduled_report_id);
			}
		}

		return array('success' => FALSE);
	}

	function update_scheduled_report($scheduled_report_id, $data)
	{
		$this->db->where('id', $scheduled_report_id);

		if ($this->db->update('scheduled_reports', $data)) 
		{
			return TRUE;
		}

		return FALSE;
	}

	function delete_scheduled_report($scheduled_report_id, $report_id)
	{
		if ($this->db->delete('scheduled_reports', array('id' => $scheduled_report_id, 'user_id' => $this->session->userdata('user_id'))))
		{
			if ($this->update_report_scheduling(0, $report_id))
			{
				return TRUE;
			}
		}

		return FALSE;
	}

	function get_report_details($report_id)
	{
		$this->db->select('rpt_user_reports.user_id, rpt_user_reports.id, rpt_user_reports.report_title, rpt_user_reports.start_date, rpt_user_reports.end_date, rpt_user_reports.shareable, rpt_user_reports.quadrant, rpt_user_reports.scheduled');
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
				$modified_row->author = $this->get_report_author($row->user_id);
				$data = $modified_row;
			}
			return $data;
		} 
		else 
		{
			# Let's check if Quadrant Report
			$cgdb = $this->load->database('console_general', TRUE);	
				$cgdb->select('rpt_user_reports.user_id, rpt_user_reports.id, rpt_user_reports.report_title, rpt_user_reports.start_date, rpt_user_reports.end_date, rpt_user_reports.shareable, rpt_user_reports.quadrant, rpt_user_reports.scheduled');
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

	# |------------------------------------------------------------------------------------------------------------------------------------------------------
	# | [END] REPORTING SECTION FUNCTIONALITY
	# |------------------------------------------------------------------------------------------------------------------------------------------------------


	# |---------------------------------------------------------------------------
	# |	[START] REPORT METRICS
	# |---------------------------------------------------------------------------

	public function set_elastic_index($elastic_index)
	{
		$this->elastic_index = $elastic_index;
	}

	public function set_user_timezone($user_timezone)
	{
		$this->user_timezone = $user_timezone;
	}

	# |---------------------------------------------------------------------------
	# |	[END] REPORT METRICS
	# |---------------------------------------------------------------------------

}