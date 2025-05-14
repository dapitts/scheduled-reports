<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<section>

	<p>You can now create a scheduled report that will automatically generate a PDF of a report at a specific interval and send it to certain recipients within your organization.</p>

	<p>A report schedule can be created from two locations:</p>

	<p><a class="no-show" href="#saved-reports">Saved Reports</a> list via the <button class="btn btn-default btn-sm">Actions <span class="caret"></span></button> dropdown menu</p>

	<p><a class="no-show" href="#report-view">Report View</a> via the <button class="btn btn-default btn-sm">Report Options <span class="caret"></span></button> dropdown menu</p>

	<p>From either dropdown menu, select the <strong>Create Scheduled Report</strong> option and the Create Scheduled Report modal will appear. When creating a new schedule, you will need to choose the following options:</p>

	<ul class="user-guide-nav">
		<li>Time - Values: 12:00 AM - 11:00 PM. Default value: 12:00 AM</li>
		<li>Frequency - Values: Weekly, Biweekly, Monthly, Quarterly. Default value: Monthly</li>
		<li>Email Distribution List - Default value: unchecked</li>
	</ul>

	<p>
		The Email Distribution List checkbox determines where the generated PDF of the report will be sent. If unchecked (default), the PDF of the report 
		will be emailed to the person who scheduled the report. If checked, the PDF of the report will be emailed to members of your organization that are on the 
		Executive Reports distribution list.
	</p>

	<p>The following table is a summary of when the report will run and the amount of data included based on the frequency:</p>

	<table class="table gray-header valign-middle" style="margin-bottom:10px;">
		<thead>
			<tr>
				<th>Report Frequency</th>
				<th>Runs</th>
				<th>Report Data</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td>Weekly</td>
				<td>Every Monday</td>
				<td>Last week</td>
			</tr>
			<tr>
				<td>Biweekly</td>
				<td>1<sup>st</sup> and 15<sup>th</sup> of every month</td>
				<td>Last 2 weeks</td>
			</tr>
			<tr>
				<td>Monthly</td>
				<td>1<sup>st</sup> of every month</td>
				<td>Last month</td>
			</tr>
			<tr>
				<td>Quarterly</td>
				<td>1<sup>st</sup> of January, April, July and October</td>
				<td>Last quarter</td>
			</tr>
		</tbody>
	</table>

	<p>Once you have made your selections, click the <button class="btn btn-sm btn-success">Create Scheduled Report</button> button. The modal will close and you will remain on the current screen you were on.</p>

	<p>If you are on the page with the <a class="no-show" href="#saved-reports">Saved Reports</a> list, there will be a <span class="need-color"><i class="glyphicon glyphicon-ok"></i></span> check mark under the Scheduled heading. Hovering over this check mark will display the Last Run and Next Run dates of the report schedule.</p>

	<h4>Scheduling Tips</h4>
	<ul class="user-guide-nav">
		<li>Before scheduling a report, verify that your time zone is correctly configured.</li>
		<li>It is recommended to schedule a report off-hours.</li>
		<li>If scheduling more than one report, stagger the scheduling times (e.g., 2 am, 3 am).</li>
		<li>In order to receive the generated PDF of the report via email, you must be on the Executive Reports distribution list.</li>
	</ul>

	<h4>Modify Scheduled Report</h4>
	<p>A report schedule can be modified from two locations:</p>

	<p><a class="no-show" href="#saved-reports">Saved Reports</a> list via the <button class="btn btn-default btn-sm">Actions <span class="caret"></span></button> dropdown menu</p>

	<p><a class="no-show" href="#report-view">Report View</a> via the <button class="btn btn-default btn-sm">Report Options <span class="caret"></span></button> dropdown menu</p>

	<p>From either dropdown menu, select the <strong>Modify Scheduled Report</strong> option and the Modify Scheduled Report modal will appear.</p>

	<p>Once you have made your changes, click the <button class="btn btn-sm btn-success">Update Scheduled Report</button> button. The modal will close and you will remain on the current screen you were on.</p>
</section>
