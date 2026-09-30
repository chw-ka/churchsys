/*function refreshStatTable($){

	// send request
	var params = {
		worship: $("#input_worship").val(),
		option: "com_worship",
		controller: "worship",
		task: "attend_worship_list",
		tmpl: "ajax"
	}
	
	$.post("index.php", params, function(html) {
		$("#stat_table tbody").html(html);
		$("#stat_table").trigger("update");
		
		$("#stat_table tr").click( function(e){
			$("#stat_table tr.current").removeClass("current");
			$(this).addClass("current");
			$("#input_worship").val($(this).attr("id").substring(8));
		});
		

		$("#input_worship").val($("#stat_table tr.current").attr("id"));
	});
}

function refreshPresentTable($){

	// send request
	var params = {
		worship: $("#input_worship").val(),
		option: "com_worship",
		controller: "worship",
		task: "attend_present_list",
		tmpl: "ajax"
	}

	$.post("index.php", params, function(html) {
		$("#present_table tbody").html(html);
		if (html == "")
			return;
		$("#present_table").trigger("update");
        $("#present_table").trigger("sorton",[[[2,1]]]);

		$("#present_table tr").click( function(e){
			$("#present_table tr.current").removeClass("current");
			$(this).addClass("current");
			$("#selected_member").val($(this).attr("id").substring(8));
		});
	});
}*/

function onTakeSuccess(member_id, member_name, member_code, attend_time){
	var worship_id = $("#WorshipAttendanceTakeForm_worship_id").val();
	
	var c = "odd";
	if ($('#attend-worship-grid .items tbody tr:first-child').hasClass("odd"))
		c = "even";
	
	// insert attend list
	$('#attend-worship-grid .key').prepend('<span>' + worship_id + '-' + member_id + '</span>');
	$('#attend-worship-grid .items tbody').prepend('<tr class="' + c + '"><td>' + member_name + ' (' + member_code + ')</td><td>' + attend_time + '</td><td class="button-column"><a class="delete" title="Delete" href="index.php?r=WorshipAttendance/deleteByWorshipMember&amp;worship_member=' + worship_id + '-' + member_id + '"><img src="images/delete.png" alt="Delete" /></a></td></tr>');
	
	// update stat
	$.fn.yiiGridView.update("worship-stat-grid", {url:"index.php?r=worshipAttendance/ajaxWorshipStat"});
}

function renewAttendanceForm() {
	$("#WorshipAttendanceTakeForm_member_code").val("");
	$("#WorshipAttendanceTakeForm_member_name").val("");
	$("#WorshipAttendanceTakeForm_member_code").focus();
}

function renewNewMemberForm() {
	$("#WorshipAttendanceNewForm_member_name").val("");
	$("#WorshipAttendanceNewForm_member_remarks").val("");
	//$("#WorshipAttendanceNewForm_member_group").val(0);
	$("#WorshipAttendanceNewForm_member_gender").val(2);
	$("#WorshipAttendanceNewForm_member_name").focus();
}

jQuery(document).ready(function($) { 
	
	$('#new_friend_form').hide();
	$("#new_friend_small_group").val("");
	$("#new_friend_small_group").hide();
	
	// Timer
	var jcOption = {
		format: '%H:%M:%S',
	    seedTime: $("#timer").attr('rel') * 1000
	}
	$("#timer").jclock(jcOption);
	
	renewAttendanceForm();
	
	$("#btn_to_take_attendance").click( function(e){
		$('#take_attendance_form').show();
		$('#new_friend_form').hide();
		return false;
	});
	
	$("#btn_to_new_friend").click( function(e){
		$('#take_attendance_form').hide();
		$('#new_friend_form').show();
		return false;
	});
	
	
	/*
	// Table Sorter
	$("#stat_table").tablesorter(); 
	$("#present_table").tablesorter(); 
	
	// New Member Submission
	$("#newfriendform").submit( function(e){
		if ($("#new_friend_name").val() == "")
		{
			$("#new_friend_name").focus();
			return false;
		} else if ($("#new_friend_remarks").val() == "")
		{
			$("#new_friend_remarks").focus();
			return false;
		}
		
		// send request
		var params = {
			newmember: 1,
			worship: $("#input_worship").val(),
			option: "com_worship",
			controller: "worship",
			task: "attend",
			name: $("#new_friend_name").val(),
			remarks: $("#new_friend_remarks").val(),
			small_group: $("#new_friend_small_group").val(),
			tmpl: "ajax"
		}
		$.post("index.php", params, function(html) {
			$("#welcome_message").html(html);
			$("#new_friend_name").val("");
			$("#new_friend_remarks").val("");
			$("#new_friend_period").val("");
			$("#new_friend_small_group").val("");
			$("#new_friend_small_group").hide();
			
			// refresh table
			refreshPresentTable($);
			refreshStatTable($);
		});

		return false;
	});
*/
	/*
	// Delete Present Record
	$("#btn_delete_present").click( function (e){
		var delete_id = $("#selected_member").val();
		if (delete_id <= 0)
			return;
		if (!confirm("Are You Sure?")){
			return;
		}
		
		var params = {
			cid: delete_id,
			option: "com_worship",
			controller: "worship",
			task: "attend_delete",
			tmpl: "ajax"
		}
		$.post("index.php", params, function(html) {
			refreshStatTable($);
			refreshPresentTable($);
		});
		
	});
	refreshStatTable($);
	refreshPresentTable($);
*/

}); 
