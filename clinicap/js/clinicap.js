/**
 * 
 */


//GLOBALS
var RCPROJECTS;
var CURRENTPID; 


function displayMessage(target,classes,message,delay){
	
	var targetElement = jQuery(target);
	targetElement.empty();
	var div = jQuery('<div/>');
		div.addClass(classes);
		div.text(message);
	targetElement.append(div);
	window.setTimeout(function() { targetElement.empty()}, delay);
}

function getStudies(){
	
	var jqxhr = jQuery.ajax({
		url : "ws/dataws.php",
		type : "post",
		data : {
			action : "getStudies"
		}
	}).done(function(data) {
		if (data.responseCode == 200) {
				updateStudies(data.data);
		}
		else if(data.responseCode == 1000){
			location.reload();
		} 
		else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});

}

function updateStudies(data){
	var studyDIV = jQuery("#ocStudiesData");
	
	var studyTable = jQuery('<table id="studyTable"/>');
	studyTable.addClass("table");
	studyTable.addClass("table-bordered");
	studyTable.addClass("table-hover");
	studyTable.append("<thead><th></th><th>UniqueProtocolID</th><th>Study Name</th><th>Study OID</th><th>Clone</th></thead>");
	var studyTableBody = jQuery("<tbody/>");
	
	for(i=0;i<data.length;i++){
		
		var tr = jQuery("<tr/>");
		tr.attr("id","study_"+data[i].oid);
		tr.addClass("studyRow");
		tr.append('<td><input type="checkbox" class="studycb"/></td>');
		tr.append("<td>"+data[i].id+"</td>");
		tr.append("<td>"+data[i].name+"</td>");
		tr.append("<td>"+data[i].oid+"</td>");

		var button = jQuery("<button/>");
			button.addClass("btn btn-default btn-sm clone_study btn-primary");
			button.attr("id","clone_"+data[i].oid);
			
		var span = jQuery('<span/>');
			span.addClass("glyphicon glyphicon-arrow-right");
		
			button.text("Clone");
			//button.append(span);
			var td = jQuery("<td/>");
				td.append(button);
			
		tr.append(td);	
		studyTableBody.append(tr);
	}
	
	studyTable.append(studyTableBody);
	studyDIV.empty();
	studyDIV.append(studyTable);
	
}

function getProjects(withNew){
	
	var jqxhr = jQuery.ajax({
		url : "ws/dataws.php",
		type : "post",
		data : {
			action : "getProjects"
		}
	}).done(function(data) {
		if (data.responseCode == 200) {
				//updateStudies(data.data);
			RCPROJECTS = data.data;
			updateProjects();
			if(withNew){
				displayMessage("#projectMessageDiv","alert alert-success","New project has been created.",4000);
			}
			
		}
		else if(data.responseCode == 1000){
			location.reload();
		} 
		else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});
}

function updateProjects(){
	
	var projectDIV = jQuery("#rcProjectsData");
	
	if(RCPROJECTS.length>0){
		
		var projectTable = jQuery('<table id="projectTable"/>');
		projectTable.addClass("table");
		projectTable.addClass("table-bordered");
		projectTable.addClass("table-hover");
		projectTable.append("<thead><th></th><th>Project ID</th><th>Project Name</th><th>Creation Time</th></thead>");
		var projectTableBody = jQuery("<tbody/>");	
		
		for(i=0;i<RCPROJECTS.length;i++){
			
			var tr = jQuery("<tr/>");
			tr.attr("id","project_"+RCPROJECTS[i].project_id);
			tr.addClass("projectRow");
			tr.append('<td><input type="checkbox" class="projectcb" id=pcb_"'+RCPROJECTS[i].project_id+'"/></td>');
			tr.append("<td>"+RCPROJECTS[i].project_id+"</td>");
			tr.append("<td>"+RCPROJECTS[i].project_title+"</td>");
			tr.append("<td>"+RCPROJECTS[i].creation_time+"</td>");
			
			projectTableBody.append(tr);
		}
		
		projectTable.append(projectTableBody);
		projectDIV.empty();
		projectDIV.append(projectTable);
	}
	else{
		projectDIV.append("Error! Wrong credentials or user has got no projects.");
	}
	
}

function cloneStudyToProject(button_id){
	var id = button_id.split("_");
	var sid = "study_S_"+id[2];
	
	var studyProtID = jQuery("#"+sid+" td:nth-child(2)").text();
	
	var c = confirm('Create a RedCap project with the name "'+studyProtID+'"?');
	
	if(c){
		
		var projectDIV = jQuery("#rcProjectsData");
		projectDIV.empty();
		projectDIV.append('<div class="loader"></div>');
		
		var jqxhr = jQuery.ajax({
			url : "ws/dataws.php",
			type : "post",
			data : {
				action : "createProject",
				name: studyProtID
			}
		}).done(function(data) {
			if (data.responseCode == 200) {
				
				getProjects(true);
				
			}
			else if(data.responseCode == 1000){
				location.reload();
			} 
			else {
				console.log("Hiba: " + data.message);
			}
		}).fail(function() {
			console.log("Error");
		});
	}
}



function displayProjectDetails(rowid){
	var id = rowid.split("_");
	var projectid = id[1];
	var detailsDiv = jQuery("#pdetails");
	
	if(projectid == CURRENTPID){
		detailsDiv.empty();
		CURRENTPID = null;
	}
	else{
		CURRENTPID = projectid;
		var table = jQuery("<table/>");
		table.addClass("table table-bordered");
		table.attr("id","pdetailstable");
		table.append("<thead><th>Property</th><th>Value</th></thead>");
		
		tbody = jQuery("<tbody/>");
		
		for(i=0;i<RCPROJECTS.length;i++){
			var pid = RCPROJECTS[i].project_id;
			
			if(pid == projectid){
				var selected =RCPROJECTS[i];
				
				jQuery.each(selected, function(key,value){
					var tr = jQuery('<tr/>');
					var td1 = jQuery('<td/>');
					var td2 = jQuery('<td/>');
					
					td1.text(key);
					td2.text(value);
					tr.append(td1);
					tr.append(td2);
					tbody.append(tr);
				});
				
			}
		}
		table.append(tbody);
		detailsDiv.empty();
		detailsDiv.append(table);
	}
}



function getStudyMetaData(){
	
	var studyProtocolID = '';
	
	jQuery(".studyRow").each(function(){
		 var row = jQuery(this);
	     if (row.find('input[type="checkbox"]').is(':checked')){ 
	    	studyProtocolID = row.find('td:nth-child(2)').text();
	     }
	});
	console.log(studyProtocolID);
	
	var DIV = jQuery("#studyMeta");
	DIV.empty();
	DIV.append('<div class="loader"></div>');
	
	var jqxhr = jQuery.ajax({
		url : "ws/dataws.php",
		type : "post",
		data : {
			action : "getStudyMetaData",
			studyProtocolId: studyProtocolID
		}
	}).done(function(data) {
		if (data.responseCode == 200) {
			displayEventsForms(data.data);
			
			
		}
		else if(data.responseCode == 1000){
			location.reload();
		} 
		else {
			console.log("Hiba: " + data.message);
		}
	}).fail(function() {
		console.log("Error");
	});
}


function displayEventsForms(data){
	
	var UL = jQuery("<ul/>");
	
	
	for (var event in data){
		var LIevent = jQuery("<li/>");
		var cb = jQuery('<input type="checkbox"/>');
		cb.attr("checked",true);
		LIevent.append(cb);
		LIevent.append("<b>"+event+"</b> ");

		
		var forms = data[event];
		var ul = jQuery("<ul/>");
		
		for(i=0;i<forms.length;i++){
			var LIform = jQuery("<li/>");
			var cb2 = jQuery('<input type="checkbox"/>');
			cb2.attr("checked",true);
			LIform.append(cb2);
			LIform.append(" "+forms[i]);
			ul.append(LIform);
		}
		
		LIevent.append(ul);
		UL.append(LIevent);
		
	}
	
	
	var DIV = jQuery("#studyMeta");
	DIV.empty();
	DIV.append(UL);
	
	
}