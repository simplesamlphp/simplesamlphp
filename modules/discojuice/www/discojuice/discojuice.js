/*
 * jQuery doTimeout: Like setTimeout, but better! - v1.0 - 3/3/2010
 * http://benalman.com/projects/jquery-dotimeout-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function($){var a={},c="doTimeout",d=Array.prototype.slice;$[c]=function(){return b.apply(window,[0].concat(d.call(arguments)))};$.fn[c]=function(){var f=d.call(arguments),e=b.apply(this,[c+f[0]].concat(f));return typeof f[0]==="number"||typeof f[1]==="number"?this:e};function b(l){var m=this,h,k={},g=l?$.fn:$,n=arguments,i=4,f=n[1],j=n[2],p=n[3];if(typeof f!=="string"){i--;f=l=0;j=n[1];p=n[2]}if(l){h=m.eq(0);h.data(l,k=h.data(l)||{})}else{if(f){k=a[f]||(a[f]={})}}k.id&&clearTimeout(k.id);delete k.id;function e(){if(l){h.removeData(l)}else{if(f){delete a[f]}}}function o(){k.id=setTimeout(function(){k.fn()},j)}if(p){k.fn=function(q){if(typeof p==="string"){p=g[p]}p.apply(m,d.call(n,i))===true&&!q?o():e()};o()}else{if(k.fn){j===undefined?e():k.fn(j===false);return true}else{e()}}}})(jQuery);


// Making sure that console.log does not throw errors on Firefox + IE etc.
if (typeof console == "undefined") var console = { log: function() {} };

var DiscoJuice = {};


/*
 * Country codes available here http://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
 */
DiscoJuice.Constants = {
	"Countries": {
		'CZ': 'Czech',
		'DK': 'Denmark',
		'FI': 'Finland',
		'FR': 'France',
		'DE': 'Germany',
		'GR': 'Greece',
		'HR': 'Croatia',
		'IE': 'Ireland',
		'IT': 'Italy',
		'JP': 'Japan',
		'HU': 'Hungary',
		'LU': 'Luxembourg',
		'NL': 'Netherlands',
		'NO': 'Norway',
		'PL': 'Poland',
		'PT': 'Portugal',
		'SI': 'Slovenia',
		'ES': 'Spain',
		'SE': 'Sweden',
		'CH': 'Switzerland',
		'TR': 'Turkey',
		'US': 'USA',
		'GB': 'UK',
		'XX': 'Experimental'
	},
	"Flags": {
		'CZ': 'cz.png',
		'DK': 'dk.png',
		'FI': 'fi.png',
		'FR': 'fr.png',
		'DE': 'de.png',
		'GR': 'gr.png',
		'HR': 'hr.png',
		'IE': 'ie.png',
		'IT': 'it.png',
		'JP': 'jp.png',
		'HU': 'hu.png',
		'LU': 'lu.png',
		'NL': 'nl.png',
		'NO': 'no.png',
		'PL': 'pl.png',
		'PT': 'pt.png',
		'SI': 'si.png',
		'ES': 'es.png',
		'SE': 'se.png',
		'CH': 'ch.png',
		'TR': 'tr.png',
		'GB': 'gb.png',
		'US': 'us.png'
	}
};

DiscoJuice.Utils = {
	"log": function(string) {
		console.log(string);
		// opera.postError(string);
	},
	"options": function() {
		var options;
		return {
			"get": function (key, def) {
	//			DiscoJuice.log(options);
	//			DiscoJuice.log('Getting [' + key + '] default [' + def + '] val [' + options[key] + ']');
				if (!options) return def;
				if (!options[key]) return def;
				return options[key];
			},
			"set": function(opts) {
				options = opts;
			},
			"update": function(key, value) {
				options[key] = value;
			}
		}
	}(),
	
	/* Functions for setting, reading and erasing cookies */
	"createCookie": function(value, type) {
		var type = type || 'EntityID';
		var name = '_DiscoJuice_' + type;
		var days = 1825;
		if (days) {
			var date = new Date();
			date.setTime(date.getTime()+(days*24*60*60*1000));
			var expires = "; expires="+date.toGMTString();
		}
		else var expires = "";
		document.cookie = name+"="+escape(value)+expires+"; path=/";
	},
	"readCookie": function(type) {
		var type = type || 'EntityID';
		var name = '_DiscoJuice_' + type;
		var days = 1825;
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i=0;i < ca.length;i++) {
			var c = ca[i];
			while (c.charAt(0)==' ') c = c.substring(1,c.length);
			if (c.indexOf(nameEQ) == 0) return unescape(c.substring(nameEQ.length,c.length));
		}
		return null;
	},
	"eraseCookie": function (type) {
		var type = type || 'EntityID';
		var name = '_DiscoJuice_' + type;
		DiscoJuice.createCookie(name,"",-1);
	},
	/* ------ ------ ------ ------ ------ */


	/*
	 * Performs a search 'term' against an entity.
	 * If no match, return false.
	 * If match return the keyword that matches.
	 */
	"searchMatch": function(item, term) {
		if (item.title.toLowerCase().search(term.toLowerCase()) !== -1) return true;
		var key, i, keyword;
		
		if (item.keywords) {
			for(key in item.keywords) {
				keyword = item.keywords[key];
				for(i = 0; i < keyword.length; i++) {
					if (keyword[i].toLowerCase().search(term.toLowerCase()) !== -1) return keyword[i];
				}
			}
		}
		return false;
	},



	// calculate distance between two locations
	"calculateDistance": function (lat1, lon1, lat2, lon2) {
		var R = 6371; // km
		var dLat = this.toRad(lat2-lat1);
		var dLon = this.toRad(lon2-lon1); 
		var a = Math.sin(dLat/2) * Math.sin(dLat/2) +
				Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) * 
				Math.sin(dLon/2) * Math.sin(dLon/2); 
		var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
		var d = R * c;
		return d;
	},

	"toRad": function (deg) {
		return deg * Math.PI/180;
	}




};




/*
	Plugin for JQuery.
	*/
(function($) {
	$.fn.DiscoJuice = function(options) {
		return this.each(function() {
			DiscoJuice.Utils.options.set(options);
			
			DiscoJuice.Control.ui = DiscoJuice.UI;
			DiscoJuice.UI.control = DiscoJuice.Control;
			
			DiscoJuice.UI.enable(this);

		});
	};
})(jQuery);


/*
 * DiscoJuice
 *  Work is based upon mock up made by the Kantara ULX group.
 * 
 * Author: Andreas Åkre Solberg, UNINETT, andreas.solberg@uninett.no
 * Licence undecided. Awaiting alignment with the licence of the origin Kantara mockup.
 */
if (typeof DiscoJuice == "undefined") var DiscoJuice = {};


DiscoJuice.UI = {
	// Reference to the top level DiscoJuice object
	"parent" : DiscoJuice,
	
	// The current data model
	"control": null,
	
	// Reference to the 
	"popup": null,
	
	
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
	},
	
	// addItem(item, description, {country, flag}, keywordmatch, distance)	 		
	// addItem(current, current.descr || null, countrydef, search, current.distance);
	"addItem": function(item, countrydef, search, distance) {
		var textLink = '';
		var classes = '';
		if (item.weight < -50) classes += 'hothit';

		var iconpath = this.parent.Utils.options.get('discoPath', '') + 'logos/';
		var flagpath = this.parent.Utils.options.get('discoPath', '') + 'flags/';
		var clear = false;
		
		var debugweight = this.parent.Utils.options.get('debug.weight', false);
		
		
		// Add icon element first
		if (item.icon) {
			textLink += '<img class="logo" src="' + iconpath + item.icon + '" />';
			clear = true;
		}
		
		// Add title
		textLink += '<span class="title">' + item.title + '</span>';
		
		// Add matched search term
		if (search && search !== true) {
			textLink += '<span class="substring">– ' + search + '</span>';
		} else if (item.descr) {
			textLink += '<span class="substring">– ' +  item.descr + '</span>';
		}
		
		

		if (countrydef || (distance != undefined)) {
				
			textLink += '<span class="location">';
			if (countrydef) {
				textLink += '<span class="country">';
				if (countrydef.flag) textLink += '<img src="' + flagpath + countrydef.flag + '" alt="' + escape(countrydef.country) + '" /> ';
				textLink += countrydef.country + '</span>';
			}
	
			
			if (distance != undefined) {
				if (distance < 1) {
					textLink += '<span class="distance">Nearby</span>';
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
		
		
		var relID = item.entityID;
		if (item.subID) {
			relID += '#' + item.subID;
		}
		
		// Wrap in A element
		textLink = '<a href="" class="' + classes + '" rel="' + escape(relID) + '" title="' + escape(item.title) + '">' + 
			textLink + '</a>';


		this.resulthtml += textLink;
	},
		
	"refreshData": function(showmore, show, listcount) {
		var that = this;
		
		this.parent.Utils.log('DiscoJuice.UI refreshData()');
		
		this.popup.find("div.scroller").empty().append(this.resulthtml);
		this.popup.find("div.scroller a").each(function() {
			var overthere = that;	// Overthere is a reference to the UI object
			$(this).click(function(event) {
				event.preventDefault();
				overthere.hide();
							
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
			var moreLink = '<a class="discojuice_showmore textlink" href="">Results limited to ' + show + ' entries – show more…</a>';
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

	"enable": function(control) {
		var imgpath = this.parent.Utils.options.get('discoPath', '') + 'images/';
		
		var textSearch = this.parent.Utils.options.get('textSearch', 'or search for a provider, in example Univerity of Oslo');
		var textHelp = this.parent.Utils.options.get('textHelp', 'Help me, I cannot find my provider');
		var textHelpMore = this.parent.Utils.options.get('textHelpMore', 'If your institusion is not connected to Foodle, you may create a new account using any of the Guest providers, such as <strong>OpenIdP (Guest users)</strong>.');
	
		var html = 	'<div style="display: none" class="discojuice">' +
			'<div class="top">' +
				'<a href="#" class="discojuice_close">&nbsp;</a>' +
				'<p class="discojuice_maintitle">' + this.parent.Utils.options.get('title', 'Title')  +  '</p>' +
				'<p class="discojuice_subtitle">' + this.parent.Utils.options.get('subtitle', 'Subtitle') + '</p>' +
			'</div>' +
			
			'<div class="discojuice_listContent" style="">' +
				'<div class="scroller">' +
					'<div class="loadingData" ><img src="' + imgpath + 'spinning.gif" /> Loading list of providers...</div>' +
				'</div>' +
				'<p class="discojuice_moreLinkContainer" style="margin: 0px; padding: 4px">&nbsp;</p>' +
			'</div>' +
	
			'<div id="search" class="" >' +
				'<p><input type="search" class="discojuice_search" results=5 autosave="discojuice" name="searchfield" placeholder="' + textSearch + '" value="" /></p>' +
				'<div class="discojuice_whatisthis" style="margin-top: 15px; font-size: 11px;">' +
					'<a  href="#" class="textlink discojuice_what">' + textHelp + '</a>' +
					'<p class="discojuice_whattext">' + textHelpMore + '</p>' +
				'</div>' +
			'</div>' +
			
			'<div id="locatemediv">' +
				'<div class="locatemebefore">' +
					'<p style="margin-top: 10px"><a id="locateme" href="">' +
						'<img style="float: left; margin-right: 5px; margin-top: -10px" src="' + imgpath + 'target.png" alt="locate me..." />' +
						'Locate me more accurately using HTML5 Geo-Location</a>' +
					'</p>' +
					'<p style="color: #999" id="locatemeinfo"></p>' +
				'</div>' +
				'<div style="clear: both" class="locatemeafter"></div>' +
			'</div>' +
			
			'<div style="display: none">' + 
				'<button id="discojuiceextesion_listener" />' +
			'</div>' +
			
			'<div class="filters bottom">' +
				'<p style="margin 0px; text-align: right; color: #ccc; font-size: 75%">DiscoJuice &copy; UNINETT</p>' +
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

		this.popup.find("#discojuiceextesion_listener").click(function() {
			that.control.discojuiceextension();
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
				that.parent.Utils.log('Locate me. Detected click event.');
				var imgpath = that.parent.Utils.options.get('discoPath', '') + 'images/';
				event.preventDefault();
 				event.stopPropagation();
				$("div.locatemebefore").hide();
				$("div.locatemeafter").html('<div class="loadingData" ><img src="' + imgpath + 'spinning.gif" /> Getting your location...</div>');
				that.control.locateMe();
			});
		} else {
			$("dd#locatemediv").hide();
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

/*
 * DiscoJuice
 *  Work is based upon mock up made by the Kantara ULX group.
 * 
 * Author: Andreas Åkre Solberg, UNINETT, andreas.solberg@uninett.no
 * Licence undecided. Awaiting alignment with the licence of the origin Kantara mockup.
 */
if (typeof DiscoJuice == "undefined") var DiscoJuice = {};


DiscoJuice.Control = {
	// Reference to the top level DiscoJuice object
	"parent" : DiscoJuice,

	// Reference to the UI object...
	"ui": null,	
	"data": null,
	
	// Set filter values to filter the result.
	"filters": {},
	
	"location": null,
	"showdistance": false,

	"maxhits": 25,
	
	"extensionResponse": null,
	
	/*
	 * Fetching JSON Metadata using AJAX.
	 * Callback postLoad is called when data is returned.
	 */
	"load": function() {
		var that = this;		
		if (this.data) return;
		var metadataurl = this.parent.Utils.options.get('metadata');
		var parameters = {};
		
		this.parent.Utils.log('metadataurl is ' + metadataurl);
		if (!metadataurl) return;

		// If SP EntityID is set in configuration make sure it is sent as a parameter
		// to the feed endpoint.
		var discosettings = this.parent.Utils.options.get('disco');
		if (discosettings) {
			parameters.entityID = discosettings.spentityid;
		}
		
		$.getJSON(metadataurl, parameters, function(data) {
			that.data = data;
			that.parent.Utils.log('Successfully loaded metadata (' + data.length + ')');
			that.postLoad();
		});
		
		
	},
	
	"postLoad": function() {
		if (!this.data) return;
		
		// Iterate through entities, and update title from DisplayNames to support Shibboleth integration.
		for(i = 0; i < this.data.length; i++) {
			if (!this.data[i].title) {
				if (this.data[i].DisplayNames) {
					this.data[i].title = this.data[i].DisplayNames[0].value;
				}
			}
		}

		
		this.readCookie();
		this.readExtensionResponse();
		this.prepareData();
		this.discoReadSetup();
		this.discoSubReadSetup();
		this.searchboxSetup();		
		if (this.parent.Utils.options.get('country', false)) {
			this.filterCountrySetup();
		}

		this.getCountry();
		
	},
	
	"readCookie": function() {
		if (this.parent.Utils.options.get('cookie', false)) {
			var selectedRelID = this.parent.Utils.readCookie();
			
			var entityID = selectedRelID;
			var subID = undefined;
			if (selectedRelID && selectedRelID.match(/^.*#.+?$/)) {
				var matched = /^(.*)#(.+?)$/.exec(selectedRelID);
				entityID = matched[1];
				subID = matched[2];
			}
			
			this.parent.Utils.log('COOKIE read ' + selectedRelID);
			if(selectedRelID) this.setWeight(-100, entityID, subID);
		}
	},
	
	"readExtensionResponse": function() {
	
		if (!this.extensionResponse) return;
		
		if(!!this.extensionResponse.autologin) {
			this.selectProvider(this.extensionResponse.entityID, this.extensionResponse.subID);
		}

		if(this.extensionResponse.selectedRelID) {
			this.setWeight(-100, this.extensionResponse.entityID, this.extensionResponse.subID);
		}
		this.parent.Utils.log('DiscoJuice Extension readExtensionResponse ' + this.extensionResponse.entityID + ' ' + this.extensionResponse.subID);

	},

	
	"discojuiceextension": function() {
		
// 		console.log('Listener activated...');
		
//		this.ui.show();
	
		var selectedRelID = $("meta#discojuiceextension_id").attr('content');
		if (!selectedRelID) return;
		
// 		console.log('Value found: ' + selectedRelID);
		
		var entityID = selectedRelID;
		var subID = undefined;
		if (selectedRelID && selectedRelID.match(/^.*#.+?$/)) {
			var matched = /^(.*)#(.+?)$/.exec(selectedRelID);
			entityID = matched[1];
			subID = matched[2];
		}
		
		this.parent.Utils.log('DiscoJuice Extension read ' + selectedRelID + ' ' + entityID + ' ' + subID);
		
		var autologin = $("meta#discojuice_autologin").attr('content');
		
		this.extensionResponse = {
			selectedRelID: selectedRelID,
			entityID: entityID,
			subID: subID,
			autologin: autologin
		};

		
	},
	
	
	
	/*
	 * Set weight to a specific data entry.
	 */
	"setWeight": function(weight, entityID, subID) {
		for(i = 0; i < this.data.length; i++) {
			if (this.data[i].entityID !== entityID) continue;				
			if (subID && !this.data[i].subID) continue;
			if (subID && subID !== this.data[i].subID) continue;
			if (this.data[i].subID && !subID) continue;

			if (isNaN(this.data[i].weight)) this.data[i].weight = 0;
			this.data[i].weight += weight;
			this.parent.Utils.log('COOKIE Setting weight to ' + this.data[i].weight);
			return;
		}
		this.parent.Utils.log('DiscoJuice setWeight failer (no entries found for) ' + entityID + ' # ' + subID);
	},
	
	"discoResponse": function(sender, entityID, subID) {
		this.parent.Utils.log('DiscoResponse Received from [' + sender  + '] entityID: ' + entityID + ' subID: ' + subID);
		
		var settings = this.parent.Utils.options.get('disco');
		if (settings) {
			var stores = settings.subIDstores;
			if (stores) {
				if (stores[entityID] && !subID) {
					this.parent.Utils.log('Ignoring discoResponse from entityID: ' + entityID + ' because subID was required and not provided');
					return;
				}
			}
		}
		
		this.setWeight(-100, entityID, subID);
		this.prepareData();
	},
	
	"calculateDistance": function() {
		var targets, distances;
		for(var i = 0; i < this.data.length; i++) {
			if (this.data[i].geo) {
				
				targets = [];
				distances = [];
				
				// Support multiple geo coordinates. Make targets be an array of targets.
				if (typeof(this.data[i].geo)=='object' && (this.data[i].geo instanceof Array)) {
					targets = this.data[i].geo;
				} else {
					targets.push(this.data[i].geo);
				}

// 				console.log('targets'); console.log(targets);
				
				
				// Iterate through all targets, and stuff the distances in to 'distances'.
				for(var j = 0; j < targets.length; j++) {
			
// 					console.log(targets[j]);
					distances.push(
						this.parent.Utils.calculateDistance(targets[j].lat, targets[j].lon, this.location[0], this.location[1])
					);
				}
				this.data[i].distance = Math.min.apply( Math, distances);
				
// 				console.log('distances'); console.log(distances);
// 				console.log('distance'); console.log(this.data[i].distance);
			
// 				this.data[i].distance = this.parent.Utils.calculateDistance(
// 					this.data[i].geo.lat, this.data[i].geo.lon, this.location[0], this.location[1]
// 				);
				
				this.data[i].distanceweight = (2 * Math.log(this.data[i].distance + 1)) - 10;
				
//				console.log('object'); console.log(this.data[i]);
			}
		}
// 		for(i = 0; i < this.data.length; i++) {
// 			if (this.data[i].distance) {
// 				console.log('Distance for [' + this.data[i].title + '] ' + this.data[i].distance);
// 			} else {
// 				console.log('Distance for [' + this.data[i].title + '] NA');
// 			}
// 		}
		this.showdistance = true;
		this.prepareData();
	},
	
	"locateMe": function() {
		var that = this;
		this.parent.Utils.log('Locate Me');
		
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition( 
	
				function (position) {  
	
					// Did we get the position correctly?
					// alert (position.coords.latitude);
	
					// To see everything available in the position.coords array:
					// for (key in position.coords) {alert(key)}
	
					//console.log('You are here: lat ' + position.coords.latitude + ' lon ' + position.coords.longitude);
					
					that.ui.setLocationText('You are here: ' + position.coords.latitude + ', ' + position.coords.longitude + '. Nearby providers shown on top.');
					
					that.location = [position.coords.latitude, position.coords.longitude];
					that.calculateDistance();
					
				}, 
				// next function is the error callback
				function (error) {
					switch(error.code) {
						case error.TIMEOUT:
							that.ui.setLocationText('Timeout');
							break;
						case error.POSITION_UNAVAILABLE:
							that.ui.setLocationText('Position unavailable');
							break;
						case error.PERMISSION_DENIED:
							that.ui.setLocationText('Permission denied');
							break;
						case error.UNKNOWN_ERROR:
							that.ui.setLocationText('Unknown error');
							break;
					}
				}
			);
		} else {
			this.parent.Utils.log('Did not find navigator.geolocation');
		}
		
	},
	
	"increase": function() {
		
		this.maxhits += 100;
		this.prepareData();
		
	},
	
	"prepareData": function(showall) {
	
		var showall = (showall ? true : false);
	
		this.parent.Utils.log('DiscoJuice.Control prepareData()');
		
		var hits, i, current, search;
		var someleft = false;

 		var term = this.getTerm();
 		var categories = this.getCategories();

		if (!this.data) return;
		
		/*
		 * Sort data by weight...
		 */
		this.data.sort(function(a, b) {
		
			// Weight
			var xa, xb;		
			xa = (a.weight ? a.weight : 0);
			xb = (b.weight ? b.weight : 0);
			
			if (a.distanceweight) xa += a.distanceweight;
			if (b.distanceweight) xb += b.distanceweight;

			return (xa-xb);
		});
		
		if (term || categories) {
			this.ui.popup.find("p.discojuice_showall").show();
		} else {
			this.ui.popup.find("p.discojuice_showall").hide();
		}

		this.ui.clearItems();
		
		hits = 0;
		for(i = 0; i < this.data.length; i++) {
			current = this.data[i];
			if (!current.weight) current.weight = 0;
			
			if (term) {
				search = this.parent.Utils.searchMatch(current,term);
				if (search === false && current.weight > -50) continue;
			} else {
				search = null;
			}
			
			if (categories && categories.country) {
				if (!current.country) continue;
				if (current.country !== '_all_' && categories.country !== current.country && current.weight > -50) continue;
			}
// 			if (categories && categories.type) {
// 				if (!current.ctype && current.weight > -50) {
// 	//				DiscoJuice.log(current);
// 				continue;
// 				}
// 	//			DiscoJuice.log(current.title + ' category ' + current.ctype);
// 				if (categories.type !== current.ctype && current.weight > -50) continue;
// 			}

			if (++hits > this.maxhits) {
				someleft = true;
				break;
			}
			
	// 		DiscoJuice.log('Accept: ' + current.title);
	
			var countrydef = null;
			if (current.country) {
				var cname = (this.parent.Constants.Countries[current.country] ? this.parent.Constants.Countries[current.country] : current.country);
				if (cname !== '_all_')  {
					var cflag = (this.parent.Constants.Flags[current.country] ? this.parent.Constants.Flags[current.country] : undefined);
					countrydef = {'country': cname, 'flag': cflag};
				}
			}
	
			var descr = current.descr || null;
	
			// addItem(item, {country, flag}, keywordmatch, distance)
			this.ui.addItem(current, countrydef, search, current.distance);

		}
		
		this.ui.refreshData(someleft, this.maxhits, hits);
	},
	
	
	"selectProvider": function(entityID, subID) {
	
		// console.log('entityid: '  + entityID);
	
		var callback;
		var that = this;
		var mustwait = that.discoWrite(entityID, subID);
		
		if (this.parent.Utils.options.get('cookie', false)) {
			var relID = entityID;
			if (subID) relID += '#' + subID;
			
			this.parent.Utils.log('COOKIE write ' + relID);
			this.parent.Utils.createCookie(relID);
		}

		var entity = null;
		for(i = 0; i < this.data.length; i++) {
			if (this.data[i].entityID == entityID) {
				if (!subID || subID == this.data[i].subID) {
					entity = this.data[i];
				}
			}
		}

// 		console.log('Entity Selected');
// 		console.log(entity);
// 		return;

		callback = this.parent.Utils.options.get('callback');	
		if (callback) {
			if (mustwait) {
				$.doTimeout(1000, function(){
					callback(entity);
					// alert('done');
				});
				
			} else {
				callback(entity);
			}
			return;
		}

	},
	
	// Setup an iframe to read discovery cookies from other domains
	"discoReadSetup": function() {
		var settings = this.parent.Utils.options.get('disco');
		
		if (!settings) return;
	
		var html = '';
		var returnurl = settings.url;
		var spentityid = settings.spentityid;
		var stores = settings.stores;
		var i;
		var currentStore;
		
		if (!stores) return;
		
		for(i = 0; i < stores.length; i++) {
			currentStore = stores[i];
			this.parent.Utils.log('Setting up DisoJuice Read from Store [' + currentStore + ']');
			iframeurl = currentStore + '?entityID=' + escape(spentityid) + '&isPassive=true&returnIDParam=entityID&return=' + escape(returnurl);
			html = '<iframe src="' + iframeurl + '" style="display: none"></iframe>';
			this.ui.addContent(html);
		}
	},
	
	// Setup an iframe to read discovery cookies from other domains
	"discoSubReadSetup": function() {
		var settings = this.parent.Utils.options.get('disco');
		
		if (!settings) return;
	
		var html = '';
		var returnurl = settings.url;
		var spentityid = settings.spentityid;
		var stores = settings.subIDstores;
		var i;
		var currentStore;
		
		if (!stores) return;
		
		for(var idp in stores) {
			returnurl = settings.url + 'entityID=' + escape(idp);
			currentStore = stores[idp];
			this.parent.Utils.log('Setting up SubID DisoJuice Read from Store [' + idp + '] =>  [' + currentStore + ']');
			iframeurl = currentStore + '?entityID=' + escape(spentityid) + '&isPassive=true&returnIDParam=subID&return=' + escape(returnurl);
			this.parent.Utils.log('iFrame URL is  [' + iframeurl + ']');
			this.parent.Utils.log('return URL is  [' + returnurl + ']');
			html = '<iframe src="' + iframeurl + '" style="display: none"></iframe>';
			this.ui.addContent(html);
		}
	},


	"discoWrite": function(entityID, subID) {
	
		var settings = this.parent.Utils.options.get('disco');
		if (!settings) return false;
		if (!settings.writableStore) return false;
	
		var html = '';
		var returnurl = settings.url;
		var spentityid = settings.spentityid;
		var writableStore = settings.writableStore;
		
		if (subID) {
			
			if (settings.subIDwritableStores && settings.subIDwritableStores[entityID]) {
			
				writableStore = settings.subIDwritableStores[entityID];
				
				this.parent.Utils.log('DiscoJuice.Control discoWrite(' + entityID + ') with SubID [' + subID + ']');
					
				iframeurl = writableStore + escape(subID);
				this.parent.Utils.log('DiscoJuice.Control discoWrite iframeURL (' + iframeurl + ') ');
					
				html = '<iframe src="' + iframeurl + '" style="display: none"></iframe>';
				this.ui.addContent(html);
				return true;
				
			
			} else {
				return false;
			}
			
		}
		
		this.parent.Utils.log('DiscoJuice.Control discoWrite(' + entityID + ') to ' + writableStore);
			
		iframeurl = writableStore + '?entityID=' + escape(spentityid) + '&IdPentityID=' + 
			escape(entityID) + '&isPassive=true&returnIDParam=bogus&return=' + escape(returnurl);
			
		this.parent.Utils.log('DiscoJuice.Control discoWrite iframeURL (' + iframeurl + ') ');
			
		html = '<iframe src="' + iframeurl + '" style="display: none"></iframe>';
		this.ui.addContent(html);
		return true;
	},

	"searchboxSetup": function() {
		
		var that = this;
		/*
			Initialise the search box.
			*/
			
//		this.parent.Utils.log(this.ui.popup.find("input.discojuice_search"));
		this.ui.popup.find("input.discojuice_search").autocomplete({
			minLength: 0,
			source: function( request, response ) {
				var term = request.term;
				if (term.length === 1) return;
//				that.resetCategories();							
				that.prepareData();
			}
		});
	},

	"filterCountrySetup": function (choice) {
		var that = this;
		var key;

		var preset = this.parent.Utils.options.get('setCountry');
		if (!choice && preset) {
			if (filterOptions[preset]) choice = preset;
		}
	
		var ftext = '<p class="discojuice_filter_country">Show providers in ' +
			'<select class="discojuice_filterCountrySelect" name="filterCountrySelect">';
		
		if (choice) {
			ftext += '<option value="all">all countries</option>';
		} else {
			ftext += '<option value="all" selected="selected">all countries</option>';
		}
		
		for (key in this.parent.Constants.Countries) {
			if (key === choice) {
				ftext += '<option value="' + key + '" selected="selected">' + this.parent.Constants.Countries[key] + '</option>';
			} else {
				ftext += '<option value="' + key + '" >' + this.parent.Constants.Countries[key] + '</option>';
			}
		}
		ftext += '</select>';
		ftext += ' <a class="discojuice_showall textlink" href="">show all countries</a>';
		ftext += '</p>';
		
		this.ui.addFilter(ftext).find("select").change(function(event) {
			event.preventDefault();
			//$("input#ulxSearchField").val('')
			//DiscoJuice.listResults();
			that.resetTerm();
			that.ui.focusSearch();
			if (that.ui.popup.find("select.discojuice_filterCountrySelect").val() !== 'all') {
				that.ui.popup.find("a.discojuice_showall").show();
			} else {
				that.ui.popup.find("a.discojuice_showall").hide();
			}
			that.prepareData();
		});
		this.ui.popup.find("a.discojuice_showall").click(function(event) {
			event.preventDefault();
			that.resetCategories();
			that.resetTerm();
			that.prepareData(true);
			that.ui.focusSearch();
			that.ui.popup.find("a.discojuice_showall").hide();
		});
		
	},
	"setCountry": function(country) {
		if (this.parent.Constants.Countries[country]) {
			this.ui.popup.find('select.discojuice_filterCountrySelect').val(country);
			this.prepareData();		
		}
	},
	"setPosition": function(lat, lon) {
		this.location = [lat, lon];
		this.calculateDistance();
	},
	"getCountry": function() {
		// If countryAPI is set, then lookup by IP.
		var countryapi = this.parent.Utils.options.get('countryAPI', false);
		var that = this;
		
		if (countryapi) {
			
			var countrycache = this.parent.Utils.readCookie('Country2');
			var geocachelat = parseFloat(this.parent.Utils.readCookie('GeoLat'));
			var geocachelon = parseFloat(this.parent.Utils.readCookie('GeoLon'));
		
			if (countrycache) {
				
				this.setCountry(countrycache);
				this.parent.Utils.log('DiscoJuice getCountry() : Found country in cache: ' + countrycache);
				
				if (geocachelat && geocachelon) {
					this.setPosition(geocachelat, geocachelon);
				}
				
			} else {
				
				$.getJSON(countryapi, function(data) {
		//			DiscoJuice.log(data);
					if (data.status == 'ok' && data.country) {
						that.parent.Utils.createCookie(data.country, 'Country2');
						that.setCountry(data.country);
						that.parent.Utils.log('DiscoJuice getCountry() : Country lookup succeeded: ' + data.country);
						
						if (data.geo && data.geo.lat && data.geo.lon) {
							that.setPosition(data.geo.lat, data.geo.lon);
							that.parent.Utils.createCookie(data.geo.lat, 'GeoLat');
							that.parent.Utils.createCookie(data.geo.lon, 'GeoLon');
						}
						
					} else {
						that.parent.Utils.log('DiscoJuice getCountry() : Country lookup failed: ' + (data.error || ''));
					}
				});
			
			}
		}
	},
	

	"resetCategories": function() {
		//this.ui.popup.find("select.discojuice_filterTypeSelect").val()
		this.ui.popup.find("select.discojuice_filterCountrySelect").val('all');
	},
	
		
	"getCategories": function () {
		var filters = {};
		var type, country;
		
		type = this.ui.popup.find("select.discojuice_filterTypeSelect").val();	
		if (type && type !== 'all') {
			filters.type = type;
		}
	
		country = this.ui.popup.find("select.discojuice_filterCountrySelect").val();	
		if (country && country !== 'all') {
			filters.country = country;
		}
	//	DiscoJuice.log('filters is');
//		this.parent.Utils.log(filters);
		
		return filters;
	},
	
	"getTerm": function() {
		return this.ui.popup.find("input.discojuice_search").val();
	},
	"resetTerm": function() {
		//this.ui.popup.find("select.discojuice_filterTypeSelect").val()
		this.ui.popup.find("input.discojuice_search").val('');
	}


};