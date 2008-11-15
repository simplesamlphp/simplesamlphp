//*** This code is copyright 2002-2003 by Gavin Kistner, gavin@refinery.com
//*** It is covered under the license viewable at http://phrogz.net/JS/_ReuseLicense.txt
//*** Reuse or modification is free provided you abide by the terms of that license.
//*** (Including the first two lines above in your source code satisfies the conditions.)

// Add a new stylesheet to the document;
// url [optional] A url to an external stylesheet to use
// idx [optional] The index in document.styleSheets to insert the new sheet before
function AddStyleSheet(url,idx){
	var css,before=null,head=document.getElementsByTagName("head")[0];

	if (document.createElement){
		if (url){
			css = document.createElement('link');
			css.rel  = 'stylesheet';
			css.href = url;
		} else css = document.createElement('style');
		css.media = 'all';
		css.type  = 'text/css';

		if (idx>=0){
			for (var i=0,ct=0,len=head.childNodes.length;i<len;i++){
				var el = head.childNodes[i];
				if (!el.tagName) continue;
				var tagName = el.tagName.toLowerCase();
				if (ct==idx){
					before = el;
					break;
				}
				if (tagName=='style' || tagName=='link' && (el.rel && el.rel.toLowerCase()=='stylesheet' || el.type && el.type.toLowerCase()=='text/css') ) ct++;
			}
		}
		head.insertBefore(css,before);

		return document.styleSheets[before?idx:document.styleSheets.length-1];
	} else return alert("I can't create a new stylesheet for you. Sorry.");
}
// e.g. var newBlankSheetAfterAllOthers = AddStyleSheet(); 
// e.g. var newBlankSheetBeforeAllOthers = AddStyleSheet(null,0);
// e.g. var externalSheetAfterOthers = AddStyleSheet('http://phrogz.net/JS/Classes/docs.css');
// e.g. var externalSheetBeforeOthers = AddStyleSheet('http://phrogz.net/JS/Classes/docs.css',0);


// Cross-browser method for inserting a new rule into an existing stylesheet.
// ss       - The stylesheet to stick the new rule in
// selector - The string value to use for the rule selector
// styles   - The string styles to use with the rule
function AddRule(ss,selector,styles){
	if (!ss) return false;
	if (ss.insertRule) return ss.insertRule(selector+' {'+styles+'}',ss.cssRules.length);
	if (ss.addRule){
		ss.addRule(selector,styles);
		return true;
	}
	return false;
}

// e.g. AddRule( document.styleSheets[0] , 'a:link' , 'color:blue; text-decoration:underline' );
// e.g. AddRule( AddStyleSheet() , 'hr' , 'display:none' );
