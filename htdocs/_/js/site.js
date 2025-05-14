// @prepros-prepend 'full_versions/jquery-1.11.3.js'
// @prepros-prepend 'full_versions/highcharts/highcharts-11.2.0.src.js'
// @prepros-prepend 'full_versions/highcharts/highcharts-3d-11.2.0.src.js'
// @prepros-prepend 'full_versions/highcharts/modules/no-data-to-display-11.2.0.src.js'
// @prepros-prepend 'full_versions/highcharts/modules/exporting-11.2.0.src.js'

// remap jQuery to $
(function($) {

	/* trigger when page is ready */
	$(document).ready(function ()
	{

		Highcharts.setOptions({
			colors: ["#2f7ed8", "#000000", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"]
			//colors: ["#2f7ed8", "#434348", "#90ed7d", "#f7a35c", "#8085e9", "#f15c80", "#e4d354", "#2b908f", "#f45b5b", "#91e8e1"]
			
			//colors: ['#058DC7', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4']
			//colors: ['#2f7ed8', '#0d233a', '#8bbc21', '#910000', '#1aadce', '#492970', '#f28f43', '#77a1e5', '#c42525', '#a6c96a']
			//colors: ['#4572A7', '#AA4643', '#89A54E', '#80699B', '#3D96AE', '#DB843D', '#92A8CD', '#A47D7C', '#B5CA92']
		});

		// Button Loading State ::
		$('button[type="submit"]').on('click', function () {
			$(this).button('loading');
		});

		$('.selectpicker').selectpicker({
			style: 'btn-select',
			showTick: true,
			//iconBase:'fontawesome'
		});

		// -----------------------------
		// Reporting Functions [START]
		// -----------------------------

		// Create Scheduled Report Ajax Form Submit ::
		var create_scheduled_report = { 
		    beforeSubmit:  showGeneralRequest, 
		    success:       showCreateScheduledReportResponse,
		    error:         showGeneralError,
		    type:          'POST',
		    timeout:       30000 
		};

		$(document).on("submit", "#create_scheduled_report", function() {  
		    $(this).ajaxSubmit(create_scheduled_report); 
		    return false; 
		});

		// Modify Scheduled Report Ajax Form Submit ::
		var modify_scheduled_report = { 
		    beforeSubmit:  showGeneralRequest, 
		    success:       showModifyScheduledReportResponse,
		    error:         showGeneralError,
		    type:          'POST',
		    timeout:       30000 
		};

		$(document).on("submit", "#modify_scheduled_report", function() {  
		    $(this).ajaxSubmit(modify_scheduled_report); 
		    return false; 
		});

		// Delete Scheduled Report Ajax Form Submit ::
		var delete_scheduled_report = { 
		    beforeSubmit:  showGeneralRequest, 
		    success:       showDeleteScheduledReportResponse,
		    error:         showGeneralError,
		    type:          'POST',
		    timeout:       30000 
		};

		$(document).on("submit", "#delete_scheduled_report", function() {  
		    $(this).ajaxSubmit(delete_scheduled_report); 
		    return false; 
		});

		$(document).on('change', '#report-hour', function() {
			let hour        = parseInt($(this).val(), 10),
			    frequency   = $('#report-frequency').val(),
			    run_date    = report_run_date(frequency),
			    run_time    = report_run_time(hour),
			    report_runs = run_date+' at '+run_time;

			$('#report-runs').val(report_runs);
		});

		$(document).on('change', '#report-frequency', function() {
			let hour        = parseInt($('#report-hour').val(), 10),
			    frequency   = $(this).val(),
			    run_date    = report_run_date(frequency),
			    run_time    = report_run_time(hour),
			    report_runs = run_date+' at '+run_time;

			$('#report-runs').val(report_runs);
		});

	    // -----------------------------
		// Reporting Functions [END]
		// -----------------------------

	});

	// =================================================
	// BLOCK Functions - Start Here
	// =================================================

	// -----------------------------
	// Reporting Functions [START]
	// -----------------------------

	showCreateScheduledReportResponse = function(responseText) {
		var _response 	= responseText;
		var _reponseObj = jQuery.parseJSON(_response);
		var _success 	= _reponseObj.success;

		// Success
		if (_success) {	
			if (_reponseObj.redirect) {
				window.location = _reponseObj.goto_url;
			} else {
				$('#schedule-element').attr('href', _reponseObj.modify_link_href);
				$('#schedule-element').text(_reponseObj.modify_link_text);

				var delete_link = '<li><a data-toggle="modal" data-target="#decision_modal" href="'+_reponseObj.delete_link_href+'">'+_reponseObj.delete_link_text+'</a></li>';
				$('#schedule-element').parent().after(delete_link);
				$('#removeModal').modal('hide');

				showReportActionSuccess(_reponseObj.message);
			}
		}
		// Fail
		if (!_success) {
			if (_reponseObj.csrf_name) {
				$('input[name="'+_reponseObj.csrf_name+'"]').val(_reponseObj.csrf_value);
			}

			showValidationError(_reponseObj.message);
		}
	};

	showModifyScheduledReportResponse = function(responseText)  { 
		var _response 	= responseText;
		var _reponseObj = jQuery.parseJSON(_response);
		var _success 	= _reponseObj.success;
		
		// Success
		if (_success) {	
			if (_reponseObj.redirect) {
				window.location = _reponseObj.goto_url;
			} else {
				$('#removeModal').modal('hide');

				showReportActionSuccess(_reponseObj.message);
			}
		}
		// Fail
		if (!_success) {
			if (_reponseObj.csrf_name) {
				$('input[name="'+_reponseObj.csrf_name+'"]').val(_reponseObj.csrf_value);
			}

			showValidationError(_reponseObj.message);
		}
	};

	showDeleteScheduledReportResponse = function(responseText)  { 
		var _response 	= responseText;
		var _reponseObj = jQuery.parseJSON(_response);
		var _success 	= _reponseObj.success;
		
		// Success
		if (_success) {	
			if (_reponseObj.redirect) {
				window.location = _reponseObj.goto_url;
			} else {
				$('#schedule-element').attr('href', _reponseObj.create_link_href);
				$('#schedule-element').text(_reponseObj.create_link_text);
				$('#schedule-element').parent().next().remove();

				$('#decision_modal').modal('hide');

				showReportActionSuccess(_reponseObj.message);
			}
		}
		// Fail
		if (!_success) {
			showValidationError(_reponseObj.message);
		}
	};

	closeModalAlertBox = function() {
		$('#modal-alert-container').fadeOut();
	};

	showButtonSubmit = function()
	{
		//$('form.element-action-form button[data-dismiss="modal"]').hide();
		$('button[data-dismiss="modal"]').hide();
		$('[data-modal-action-button]').hide();
		$('form.element-action-form button[type="submit"]').button('loading');		
	};

	removeButtonSubmit = function()
	{
		//$('form.element-action-form button[data-dismiss="modal"]').show();
		$('button[data-dismiss="modal"]').show();
		$('[data-modal-action-button]').show();
		$('form.element-action-form button[type="submit"]').button('reset');
	};

	showValidationError = function(xMessage) {
		$('.modal-alert-content').html(xMessage);
		$('#modal-alert-container').show();
		removeButtonSubmit();
		setTimeout(function(){closeModalAlertBox();}, 3000);
	};

	showGeneralRequest = function(formData, jqForm, options) { 
		showButtonSubmit();
		//return true;
	};

	showGeneralError = function() {
		$('.modal-alert-content').html('<p>Oops, Something went wrong!</p>');
		$('#modal-alert-container').show();
		removeButtonSubmit();
		setTimeout(function(){closeModalAlertBox();}, 3000);
	};

	showReportActionSuccess = function(xMessage) {
		$('.report-alert-content').html(xMessage);
		$('#report-alert-container').show();
		setTimeout(function(){closeReportSuccessAlertBox();}, 3000);
	};

	closeReportSuccessAlertBox = function() {
		 $('#report-alert-container').fadeOut('fast', function() {
		 	$('.alert-heading').text('Success');
		 	$('#alert-status').removeClass('alert-danger');
  		});
	};

	report_run_date = function(frequency) {
		switch (frequency)
		{
			case 'weekly':
				return 'Every Monday';
				break;
			case 'biweekly':
				return '1st and 15th of every month';
				break;
			case 'monthly':
				return '1st of every month';
				break;
			case 'quarterly':
				return '1st of January, April, July and October';
				break;
		}
	};

	report_run_time = function(hour) {
		let time_arr = ['12:00 AM','1:00 AM','2:00 AM','3:00 AM','4:00 AM','5:00 AM','6:00 AM','7:00 AM','8:00 AM','9:00 AM','10:00 AM','11:00 AM','12:00 PM','1:00 PM','2:00 PM','3:00 PM','4:00 PM','5:00 PM','6:00 PM','7:00 PM','8:00 PM','9:00 PM','10:00 PM','11:00 PM'];

		return time_arr[hour];
	};

	// -----------------------------
	// Reporting Functions [END]
	// -----------------------------

})(window.jQuery);