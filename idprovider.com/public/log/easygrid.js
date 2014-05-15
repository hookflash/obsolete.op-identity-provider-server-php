/*
* Easy Grid
*
* @package easygrid
* @author $Author: sheiko $  
* @version $Id: easygrid.js, v 1.5 2007/02/27 15:58:15 sheiko Exp $ 
* @since v.1.5 
* @copyright (c) Dmitry Sheiko http://www.cmsdevelopment.com 
*/ 


var EGConfig = { 
		WorkplaceDivID: "TblBody", 
		LeightIndicatorDivID: "TblLength", 
		RangeFormID: "RangeForm", 
		PaginationFormID: "PaginationForm",
		ArrowToUp : '<img src="arr_top.gif" width="13" height="8" border="0" alt="Ascent Sorting">',
		ArrowToDown : '<img src="arr_bottom.gif" width="13" height="8" border="0" alt="Descent Sorting">',
		TblLength : 0,
		PaginationFrameHalfLength : 2,
		sUrl : "controller.php",
		ContextMenu : "on"
		};

var columns = { counter: 0, settings: new Array() };
var filters = { counter: 0, settings: new Array() };

/** 
* Apply parameters
*/ 	
function settings(obj, json) {
	obj.settings[obj.counter++] = eval( '(' + json + ')' ); 
}

if(document.implementation && document.implementation.createDocument) var isMozilla=true;
else var isMozilla=false;


function refreshByClick(fieldname, the_value, e) {
	if(!isMozilla) keycode=event.keyCode;
	else keycode=e.which;
	makeRequest('filter_field='+fieldname+'&filter_value='+the_value+	String.fromCharCode(keycode));
}


/** 
* Build pagination
*/ 				
function gridPagination() {
	var Output = '';
	var Range = document.getElementById(EGConfig.RangeFormID).limit.value - document.getElementById(EGConfig.RangeFormID).offset.value;
	var Offset = document.getElementById(EGConfig.RangeFormID).offset.value;
	var RangeFinish = Offset*1+Range*1;
	var PageNumber = 0;
	var Params = '';

	if(Offset>=Range) { 
		Params = Offset-Range; if(Params<0) Params=0;
		Output += '<a onclick="setGridRange('+(Params*1)+', '+(Params*1+Range)+');">&lt;</a>';
		
	}

	PageNumber = Math.ceil(EGConfig.TblLength/Range);
	if(Offset<1)
		CurrentPage = 0;
	else
		CurrentPage = Math.ceil(Offset/Range);	
	for(i=0; i<PageNumber;i++) {		
		if(i>=CurrentPage-EGConfig.PaginationFrameHalfLength && i<=CurrentPage+EGConfig.PaginationFrameHalfLength) {
			Output += '<a onclick="setGridRange('+(i*Range)+', '+(i*Range+Range)+');">'+i+'</a>';
		}
	}

	if((EGConfig.TblLength>Range && !RangeFinish) || EGConfig.TblLength>RangeFinish) {
		Output += '<a onclick="setGridRange('+(RangeFinish*1)+', '+(RangeFinish*1+Range)+');">&gt;</a>';
	}
	return Output;
}

/** 
* Get filed lists
*/ 				
function tableFieldList() {
	var output = '';
	for(i=0;i<columns.settings.length;i++) {
		output += columns.settings[i].field+',';
	}
	return output;
}

/** 
* Get table headers
*/ 				
function tableHeaders() {
	var output = '<thead><tr>';
	for(i=0;i<columns.settings.length;i++) {
		output += '<th width="'+columns.settings[i].width+'" nowrap="nowrap"><table cellpadding="0" cellspacing="0" border="0"><tr><td>'+columns.settings[i].title+'</td><td><a onclick="sortTbl(\''+columns.settings[i].field+'\',\'asc\')">'+EGConfig.ArrowToUp+'</a><br /><a onclick="sortTbl(\''+columns.settings[i].field+'\',\'desc\')">'+EGConfig.ArrowToDown+'</a></td></tr></table></th>';
	}
	output += '</tr></thead>';
	return output;
}

/** 
* Build new grid stage basen on given by server array
*/ 				
function buildList(json) {
	var output = '';
	var res = '';
	
	var in_list = eval( '(' + json + ')' ); 
	
	EGConfig.TblLength = in_list.tlength;
	document.getElementById(EGConfig.LeightIndicatorDivID).innerHTML = in_list.tlength;
	document.getElementById(EGConfig.PaginationFormID).innerHTML = gridPagination();
	
	
	for(i=0;i<in_list.value.length;i++) {
		if(EGConfig.ContextMenu=="on")
			output += '<tr id="id_'+i+'" oncontextmenu="return gridShowContextMenu(\'id_'+i+'\', event)">';
		else	
			output += '<tr id="id_'+i+'">';
		for(j=0;j<in_list.columns.length;j++) {
			eval ( "res = in_list.value[i]."+in_list.columns[j]+";" );
			output += '<td>'+res+'</td>';
		}
		output += '</tr>';
	}
	if(output.length==0) return '<tr><td colspan="5" class="grid_indication"></td></tr>';
	return tableHeaders()+ "<tbody>" +output+ "</tbody>";
}

/** 
* Make request for grid sorting
*/ 				
function sortTbl(field,direction) {
	return makeRequest('orderby='+field+'&direction='+direction);
}

/** 
* Make request for grid limitation
*/ 				
function setGridRange(offset, limit) {
	document.getElementById(EGConfig.RangeFormID).offset.value = offset;
	document.getElementById(EGConfig.RangeFormID).limit.value = limit;
	makeRequest('offset='+offset+'&limit='+limit);
}

/** 
* Make request for the new grid stage
*/ 				
function makeRequest(postData){
	if(postData.length!=0) postData += "&";
	postData += "fields="+tableFieldList();
	serverRequest(EGConfig.sUrl, postData, callback);
}

/** 
* Callback when we ask the new grid stage
*/ 				
var callback = function(obj) { 
		var message = "";
		message = obj.responseText;
		if(message.substr(0,1)=="{") {
			document.getElementById(EGConfig.WorkplaceDivID).innerHTML = '<table class="grid_table" cellspacing="0" cellpadding="0">' + buildList(obj.responseText) + "</table>";
		} else alert(message);
}; 

/** 
* Create Request Object for various platforms
* 
*/ 				

function createRequestObject() {
    var request = null;
    if(!request) try {
        request=new ActiveXObject('Msxml2.XMLHTTP');
    } catch (e){}
    if(!request) try {
        request=new ActiveXObject('Microsoft.XMLHTTP');
    } catch (e){}
    if(!request) try {
        request=new XMLHttpRequest();
    } catch (e){}
    return request;
}  

/** 
* Make server request
* 
* @param POST-request performing
* @param url  - Request address
* @param data - Parameters as a string
* @param  callback - (facultative) a callback-function
*/ 				

function serverRequest(url, data, callback) {
    var request = createRequestObject();
    if(!request) return false;
    request.onreadystatechange  = function() { 
            if(request.readyState == 4 && callback) callback(request);
    };

    request.open('POST', url, true);
    request.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	try {
		request.send(data);
	} catch (e) {
		alert('The server does not respond');
	}
    return true;
}  