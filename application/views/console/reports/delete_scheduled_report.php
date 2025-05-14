<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-body">

			<div class="icon"></div>
			
			<h3>Are you sure you want to delete the schedule for report:</h3>
			<h4><?php echo $report_details->report_title; ?> ?</h4>
			<h5>This action cannot be undone.</h5>
			
		</div>
		<div class="modal-footer">
			
			<?php echo form_open('/console/reports/do_delete_scheduled_report', array('id'=>'delete_scheduled_report', 'role'=>'form', 'class'=>'element-action-form')); ?>
				<div class="hidden">
					<input type="hidden" name="report_id" value="<?php echo $report_details->id; ?>" />
					<input type="hidden" name="scheduled_report_id" value="<?php echo $scheduled_report_id; ?>" />
					<input type="hidden" name="referer" value="" />
				</div>

				<button type="button" class="btn btn-lg btn-danger" data-dismiss="modal">No</button>
				<button class="btn btn-lg btn-success form-submit-button" type="submit" data-loading-text="Deleting Schedule...">Yes</button>
			<?php echo form_close(); ?>
			
		</div>
	</div>
</div>