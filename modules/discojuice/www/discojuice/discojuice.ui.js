/*
 * DiscoJuice
 * Author: Andreas Åkre Solberg, UNINETT, andreas.solberg@uninett.no
 * Licence undecided.
 */
if (typeof DiscoJuice == "undefined") var DiscoJuice = {};


DiscoJuice.UI = {
	// Reference to the top level DiscoJuice object
	"parent" : DiscoJuice,
	
	// The current data model
	"control": null,
	
	// Reference to the 
	"popup": null,
	
	"alreadyLoaded": {},
	
	// Entities / items
	"resulthtml": 'Loading data…',

	"show": function() {
		this.control.load();
	
		this.popup.fadeIn("slow");
		$("div#discojuice_overlay").show(); // fadeIn("fast");
		this.focusSearch();
	},
	
	"focusSearch": function() {
		$("input.discojuice_search").focus();
	},
	
	"hide": function() {
		$("div#discojuice_overlay").fadeOut("slow"); //fadeOut("fast");
		this.popup.fadeOut("slow");
	},
	
	"clearItems": function() {
		this.resulthtml = '';
		this.alreadyLoaded = {};
	},
	
	"sprintf": function() {
	   if (!arguments || arguments.length < 1 || !RegExp)
	   {
	      return;
	   }
	   var str = arguments[0];
	   var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
	   var a = b = [], numSubstitutions = 0, numMatches = 0;
	   while (a = re.exec(str))
	   {
	      var leftpart = a[1], pPad = a[2], pJustify = a[3], pMinLength = a[4];
	      var pPrecision = a[5], pType = a[6], rightPart = a[7];

	      numMatches++;
	      if (pType == '%')
	      {
	         subst = '%';
	      }
	      else
	      {
	         numSubstitutions++;
	         if (numSubstitutions >= arguments.length)
	         {
	            alert('Error! Not enough function arguments (' + (arguments.length - 1)
	               + ', excluding the string)\n'
	               + 'for the number of substitution parameters in string ('
	               + numSubstitutions + ' so far).');
	         }
	         var param = arguments[numSubstitutions];
	         var pad = '';
	                if (pPad && pPad.substr(0,1) == "'") pad = leftpart.substr(1,1);
	           else if (pPad) pad = pPad;
	         var justifyRight = true;
	                if (pJustify && pJustify === "-") justifyRight = false;
	         var minLength = -1;
	                if (pMinLength) minLength = parseInt(pMinLength);
	         var precision = -1;
	                if (pPrecision && pType == 'f')
	                   precision = parseInt(pPrecision.substring(1));
	         var subst = param;
	         switch (pType)
	         {
	         case 'b':
	            subst = parseInt(param).toString(2);
	            break;
	         case 'c':
	            subst = String.fromCharCode(parseInt(param));
	            break;
	         case 'd':
	            subst = parseInt(param) ? parseInt(param) : 0;
	            break;
	         case 'u':
	            subst = Math.abs(param);
	            break;
	         case 'f':
	            subst = (precision > -1) ? Math.round(parseFloat(param) * Math.pow(10, precision)) / Math.pow(10, precision) : parseFloat(param);
	            break;
	         case 'o':
	            subst = parseInt(param).toString(8);
	            break;
	         case 's':
	            subst = param;
	            break;
	         case 'x':
	            subst = ('' + parseInt(param).toString(16)).toLowerCase();
	            break;
	         case 'X':
	            subst = ('' + parseInt(param).toString(16)).toUpperCase();
	            break;
	         }
	         var padLeft = minLength - subst.toString().length;
	         if (padLeft > 0)
	         {
	            var arrTmp = new Array(padLeft+1);
	            var padding = arrTmp.join(pad?pad:" ");
	         }
	         else
	         {
	            var padding = "";
	         }
	      }
	      str = leftpart + padding + subst + rightPart;
	   }
	   return str;
	},
	
	"addItem": function(item, countrydef, search, distance, quickentry, enabled) {
		var textLink = '';
		var classes = (enabled ? 'enabled' : 'disabled');
//		if (item.weight < -50) classes += 'hothit';

		var iconpath = this.parent.Utils.options.get('discoPath', '') + 'logos/';
		var flagpath = this.parent.Utils.options.get('discoPath', '') + 'flags/';
		var clear = false;
		
		var debugweight = this.parent.Utils.options.get('debug.weight', false);
		
		var relID = item.entityID;
		if (item.subID) {
			relID += '#' + item.subID;
		}
		
		if (this.alreadyLoaded[relID]) return;
		this.alreadyLoaded[relID] = true;
		
		// Add icon element first
		if (item.icon && this.parent.Utils.options.get('showIcon', true)) {
			textLink += '<img class="logo" src="' + iconpath + item.icon + '" />';
			clear = true;
		}
		
		if (quickentry) {
			textLink += '<span style="font-size: 80%; -webkit-border-radius: 3px; -moz-border-radius: 3px; border-radius: 3px; border: 1px solid #ccc; background: #eee; color: #777; padding: 3px 2px 0px 2px; margin: 3px; float: left; left: -10px">&#8629;</span>';
		}
		
		// Add title
		textLink += '<span class="title">' + item.title + '</span>';
		
		// Add matched search term
		if (search && search !== true) {
			textLink += '<span class="substring">&#8212; ' + search + '</span>';
		} else if (item.descr) {
			textLink += '<span class="substring">&#8212; ' +  item.descr + '</span>';
		}
		
		

		if (
				(countrydef || (distance != undefined))
				&& this.parent.Utils.options.get('showLocationInfo', true)
			) {
				
			textLink += '<span class="location">';
			if (countrydef) {
				textLink += '<span class="country">';
				if (countrydef.flag) textLink += '<img src="' + flagpath + countrydef.flag + '" alt="' + escape(countrydef.country) + '" /> ';
				textLink += countrydef.country + '</span>';
			}
	
			
			if (distance != undefined) {
				if (distance < 1) {
					textLink += '<span class="distance">' + DiscoJuice.Dict.nearby + '</span>';
				} else {
					textLink += '<span class="distance">' +  Math.round(distance) + ' km' + '</span>';
				}

			}
			textLink += '</span>';
		}
		
		if (debugweight) {
			textLink += '<div class="debug">';
			
			if (item.subID) {
				textLink += '<input value="' + item.subID + '" />';
			}
			
			var w = 0;
			if (item.weight) {
				w += item.weight;
			}
			if (item.distanceweight) {
				w += item.distanceweight;
			}
			textLink += 'Weight <strong style="color: #888">' + Math.round(100*w)/100 + '</strong> ';

			if (item.weight) {
				textLink += ' (base ' + item.weight + ')   ';
			}
			if (item.distanceweight) {
				textLink += '(dist ' + Math.round(100*item.distanceweight)/100 + ')';
			}


			textLink += '</div>';
		}

		
		// Add a clear bar. 
		if (clear) {
			textLink += '<hr style="clear: both; height: 0px; visibility:hidden" />';
		}
		

		
		// Wrap in A element
		textLink = '<a href="" class="' + classes + '" rel="' + escape(relID) + '" title="' + escape(item.title) + '">' + 
			textLink + '</a>';


		this.resulthtml += textLink;
	},
	
	
	"setScreen": function (content) {
		$("div.discojuice_listContent").hide();
		$("div#locatemediv").hide();
		$("div#search").hide();

		$("div.filters").hide();
		
		$("div#discojuice_page div.discojuice_content").html(content);		
		
		$("div#discojuice_page").show();
		$("div#discojuice_page_return").show();
		
		console.log($("div#discojuice_page"));
		
	},
	
	"returnToProviderList": function () {
		$("div.discojuice_listContent").show();
		$("div#discojuice_page").hide();
		$("div#discojuice_page_return").hide();
		
		if (this.parent.Utils.options.get('location', false) && navigator.geolocation) {
			$("div#locatemediv").show();
		}
		$("div#search").show();
		$("div.filters").show();
	},
		
	"refreshData": function(showmore, show, listcount) {
		var that = this;
		
		this.parent.Utils.log('DiscoJuice.UI refreshData()');
		
		this.popup.find("div.scroller").empty().append(this.resulthtml);
		this.popup.find("div.scroller a").each(function() {
			var overthere = that;	// Overthere is a reference to the UI object
			$(this).click(function(event) {
				event.preventDefault();
				event.stopPropagation();
				//overthere.hide();
							
				// The "rel" attribute is containing: 'entityid#subid'
				// THe following code, decodes that.
				var relID = unescape($(this).attr('rel'));
				var entityID = relID;
				var subID = undefined;
				if (relID.match(/^.*#.+?$/)) {
					var matched = /^(.*)#(.+?)$/.exec(relID);
					entityID = matched[1];
					subID = matched[2];
				}
				overthere.control.selectProvider(entityID, subID);
			});
		});
		
		if (showmore) {
			
			var moreLink = '<a class="discojuice_showmore textlink" href="">' + this.sprintf(DiscoJuice.Dict.moreLink, show, this.parent.Control.data.length)+ '…</a>';
			this.popup.find("p.discojuice_moreLinkContainer").empty().append(moreLink);
			this.popup.find("p.discojuice_moreLinkContainer a.discojuice_showmore").click(function(event) {
				event.preventDefault();
				that.control.increase();
			});
		} else {
			this.popup.find("p.discojuice_moreLinkContainer").empty();
			if (listcount > 10) {
				var moreLink = '<span style="color: #888">' + listcount + ' entries listed</span>';
				this.popup.find("p.discojuice_moreLinkContainer").append(moreLink);
			} 
		}
	},
	
	"error": function(message) {
		console.log("error" + message);
		this.popup.find("div#discojuice_error").show();
		this.popup.find("div.discojuice_errortext").append('<p style="border-bottom: 1px dotted #ddd; margin-bottom: 3px" class="discojuice_errortext">' + message + '</p>');
	},

	"enable": function(control) {
		var imgpath = this.parent.Utils.options.get('discoPath', '') + 'images/';
		
		var textSearch = this.parent.Utils.options.get('textSearch',  DiscoJuice.Dict.orSearch);
		var textHelp = this.parent.Utils.options.get('textHelp', DiscoJuice.Dict.help);
		var textHelpMore = this.parent.Utils.options.get('textHelpMore', DiscoJuice.Dict.helpMore);
	
		var subtitleText = this.parent.Utils.options.get('subtitle', null);
		var subtitleHTML = (subtitleText !== null ? '<p class="discojuice_subtitle">' + subtitleText + '</p>' : '');
	
		var html = 	'<div style="display: none" class="discojuice">' +
			'<div class="top">' +
				'<a href="#" class="discojuice_close">&nbsp;</a>' +
				'<p class="discojuice_maintitle">' + this.parent.Utils.options.get('title', 'Title')  +  '</p>' +
				subtitleHTML +
			'</div>' +
			
			'<div class="discojuice_listContent" style="">' +
				'<div class="scroller">' +
					'<div class="loadingData" ><img src="' + imgpath + 'spinning.gif" /> ' + DiscoJuice.Dict.loading + '...</div>' +
				'</div>' +
				'<p class="discojuice_moreLinkContainer" style="margin: 0px; padding: 4px">&nbsp;</p>' +
			'</div>' +
			
			'<div id="discojuice_page" style="display: none"  class="" >' +
				'<div class="discojuice_content" style="">' + 
				'</div>' +
			'</div>' +
			
			'<div id="discojuice_page_return" style="display: none"  class="" >' +
				'<div class="" style="">' + 
				'<input id="discojuice_returntoproviderlist" type="submit" value="« ' + DiscoJuice.Dict.pageReturn + '" />' +
				'</div>' +
			'</div>' +
	
			'<div id="search" class="" >' +
				'<p><input type="search" class="discojuice_search" results=5 autosave="discojuice" name="searchfield" placeholder="' + textSearch + '" value="" /></p>' +
				'<div class="discojuice_whatisthis" style="margin-top: 15px; font-size: 11px;">' +
					'<a  href="#" class="textlink discojuice_what">' + textHelp + '</a>' +
					'<p class="discojuice_whattext">' + textHelpMore + '</p>' +
				'</div>' +
			'</div>' +
			
			'<div id="discojuice_error" style="display: none"  class="" >' +
				'<img src="' + imgpath + 'error.png" style="float: left" />' +
				'<div class="discojuice_errortext" style="clear: none; margin-top: 0px; margin-left: 30px; font-size: 11px;">' + 
				'</div>' +
			'</div>' +
			
			'<div id="locatemediv">' +
				'<div class="locatemebefore">' +
					'<p style="margin-top: 10px"><a id="locateme" href="">' +
						'<img style="float: left; margin-right: 5px; margin-top: -10px" src="' + imgpath + 'target.png" alt="locate me..." />' +
						DiscoJuice.Dict.locateMe + '</a>' +
					'</p>' +
					'<p style="color: #999" id="locatemeinfo"></p>' +
				'</div>' +
				'<div style="clear: both" class="locatemeafter"></div>' +
			'</div>' +
			
			'<div style="display: none">' + 
				'<button id="discojuiceextesion_listener" />' +
			'</div>' +
			
			'<div class="bottom">' +
//				'<p style="margin 0px; color: #ccc; font-size: 75%; float: left">Settings</p>' +
				'<div class="filters" style="padding: 0px; margin: 0px"></div>' +
				'<p id="dj_help" style="margin 0px; text-align: right; color: #ccc; font-size: 75%">' + 
				'DiscoJuice &copy; UNINETT ' + 
				'<img class="" style="position: relative; bottom: -4px; right: -5px" alt="Information" src="' + imgpath + 'info.png" />'
				'</p>' +

			'</div>' +
	

		'</div>';
		var that = this;
		
		if (this.parent.Utils.options.get('overlay', true) === true) {
			var overlay = '<div id="discojuice_overlay" style="display: none"></div>';
			$(overlay).appendTo($("body"));
		}
		
		this.popup = $(html).appendTo($("body"));


		if (this.parent.Utils.options.get('always', false) === true) {
			this.popup.find(".discojuice_close").hide();
			this.show();
		} else {
			// Add a listener to the sign in button.
			$(control).click(function(event) {
				event.preventDefault();
				that.show();
				return false;
			});
		}
		
		this.popup.find("p#dj_help").click(function() {
			that.setScreen(
				'<h2>' + DiscoJuice.Dict.about + '</h2>' +
				'<p style="margin: .5em 0px">' + that.sprintf(DiscoJuice.Dict.aboutDescr, '<a href="http://uninett.no">', '</a>') + '</p>' +

				'<p style="margin: .5em 10px"><a href="http://discojuice.org" target="_blank">' + DiscoJuice.Dict.aboutMore + '</a></p>' +
				'<p style="margin: .5em 0px; font-size: 80%">' + DiscoJuice.Dict.version + ': ' + DiscoJuice.Version);
		});

		this.popup.find("#discojuiceextesion_listener").click(function() {
			that.control.discojuiceextension();
		});

		this.popup.find("#discojuice_page_return input").click(function(e) {
			e.preventDefault();
			that.returnToProviderList();
		});

		// Add listeners to the close button.
		this.popup.find(".discojuice_close").click(function() {
			that.hide();
		});

 		// Add toogle for what is this text.
		this.popup.find(".discojuice_what").click(function() {
			that.popup.find(".discojuice_whatisthis").toggleClass("show");
		});


		if (this.parent.Utils.options.get('location', false) && navigator.geolocation) {
			var that = this;
			$("a#locateme").click(function(event) {
				var imgpath = that.parent.Utils.options.get('discoPath', '') + 'images/';

				that.parent.Utils.log('Locate me. Detected click event.');
				event.preventDefault();
 				event.stopPropagation();
				$("div.locatemebefore").hide();
				$("div.locatemeafter").html('<div class="loadingData" ><img src="' + imgpath + 'spinning.gif" /> ' + DiscoJuice.Dict.locating + '...</div>');
				that.control.locateMe();
			});
		} 
		
	},
	

	
	"setLocationText": function(html) {
		return $("div.locatemeafter").html(html);
	},
	
	"addContent": function(html) {
		return $(html).appendTo($("body"));
	},
	"addFilter": function(html) {
		return $(html).prependTo(this.popup.find('.filters'));
//		this.popup.find('.filters').append(html).css('border', '1px solid red');
	}
};

