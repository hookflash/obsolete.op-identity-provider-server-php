/*
* Easy Grid Context Menu
*
* @package easygrid
* @author $Author: sheiko $  
* @version $Id: easygrid_contextmenu.php, v 1.5 2007/02/27 15:58:15 sheiko Exp $ 
* @copyright (c) Dmitry Sheiko http://www.cmsdevelopment.com 
*/ 
var gridAcceptContextMenu=true;
var CurrentID; 

document.write('<div id="context_menu" class="context_menu">&nbsp;</div>');

function gridHideContextMenu(){
	var context_menu=document.getElementById('context_menu');
	context_menu.style.visibility="hidden";
	document.body.onmousedown=null;
}

function acceptRightClick() {
	gridAcceptContextMenu=true;
}

function gridShowContextMenu(id, even) {
	if (gridAcceptContextMenu) {
		CurrentID=id;
		gridAcceptContextMenu=false;
	
		context_menu.document.getElementById("context_menu");
		context_menu.innerHTML = '<table cellpadding="0" cellspacing="0" border="0">' +
		'<tr><td class="leftc"><img src="contextm_icon.gif" width="26" width="26" /></td><td><a href="#" onClick="alert(\'View \'+id); return false;">View</a></td></tr>' +
		'<tr><td class="leftc"><img src="contextm_icon.gif" width="26" width="26" /></td><td><a href="#" onClick="alert(\'Modify \'+id); return false;">Modify</a></td></tr>' +
		'</table>';
		
		document.body.onmousedown=gridHideContextMenu;
		
		var rightedge = document.body.clientWidth-event.clientX;
		var bottomedge = document.body.clientHeight-event.clientY;
		
		if (rightedge < context_menu.offsetWidth)
			context_menu.style.left = document.body.scrollLeft + event.clientX - context_menu.offsetWidth;
		else
			context_menu.style.left = document.body.scrollLeft + event.clientX;
		if (bottomedge < context_menu.offsetHeight)
			context_menu.style.top = document.body.scrollTop + event.clientY - context_menu.offsetHeight;
		else
			context_menu.style.top = document.body.scrollTop + event.clientY;
		context_menu.style.visibility = "visible";
		
		setTimeout("acceptRightClick()",100);
	}
	return false;
}