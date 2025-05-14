<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">Modify Scheduled Report</h4>
		</div>
		
		<div id="modal-alert-container">
			<div class="alert alert-danger">
				<h4 class="alert-heading">Error</h4>
				<div class="modal-alert-content"></div>
			</div>
		</div>	

		<?php echo form_open('/console/reports/do_modify_scheduled_report', array('id'=>'modify_scheduled_report', 'role'=>'form', 'class'=>'element-action-form')); ?>
		<div class="modal-body">
			<div class="row">
				<div class="col-md-12">
					<h4 class="scheduled-report-title"><?php echo $report_details->report_title; ?></h4>
					<p>Select the time and how often a PDF of this report will automatically be generated and emailed to the recipient(s).</p>
					<table class="table valign-middle table-condensed table-30-70 table-schedule-options">
						<tr>
							<td>Time</td>
							<td>
								<div class="form-group">
									<?php echo form_dropdown('hour', $hour_dropdown, ($this->input->post('hour') ? $this->input->post('hour') : $set_hour), 'class="selectpicker form-control no-error show-tick" data-size="7" id="report-hour"'); ?>
								</div>
							</td>
						</tr>
						<tr>
							<td>Frequency</td>
							<td>
								<div class="form-group">
									<?php echo form_dropdown('frequency', $frequency_dropdown, ($this->input->post('frequency') ? $this->input->post('frequency') : $set_frequency), 'class="selectpicker form-control no-error show-tick" data-size="7" id="report-frequency"'); ?>
								</div>
							</td>
						</tr>
						<tr>
							<td>Runs</td>
							<td>
								<div class="form-group">
								    <input type="text" class="form-control" id="report-runs" name="report_runs" value="<?php echo $report_runs; ?>" readonly>
								</div>
							</td>
						</tr>
					</table>
					
					<div class="hidden">
						<input type="hidden" name="report_id" value="<?php echo $report_id; ?>" />
						<input type="hidden" name="scheduled_report_id" value="<?php echo $scheduled_report_id; ?>" />
						<input type="hidden" name="referer" value="" />
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<div class="form-group">
						<input type="checkbox" id="email_dist_list" name="email_dist_list" value="1" <?php echo set_checkbox('email_dist_list', '1', $email_dist_list === '1'); ?>>
						<label for="email_dist_list">Send email to <strong>Executive Reports</strong> distribution list</label>
					</div>
				</div>
			</div>
			<div class="row">	
				<div class="col-md-12">
					<div class="alert alert-info">
						<h4>Scheduling Tips</h4>
						<ul class="list-unstyled">
							<li>&bull; Before scheduling a report, verify that your time zone is correctly configured.</li>
							<li>&bull; It is recommended to schedule a report off-hours.</li>
							<li>&bull; If scheduling more than one report, stagger the scheduling times (e.g., 2 am, 3 am).</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		
		<div class="modal-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			<button type="submit" class="btn btn-success form-submit-button" data-loading-text="Updating Schedule...">Update Scheduled Report</button>
		</div>
		
		<?php echo form_close(); ?>
		
	</div>
</div>