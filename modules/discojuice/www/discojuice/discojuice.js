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
		'US': 'us.png',
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
	"addItem": function(current, substring, flag) {
		var textLink = '';
		var classes = '';
		if (current.weight < -50) classes += 'hothit';

		var iconpath = this.parent.Utils.options.get('discoPath', '') + 'logos/';
		var flagpath = this.parent.Utils.options.get('discoPath', '') + 'flags/';
		
		var flagtext = '';
		
		if (flag) {
			flagtext = '<img src="' + flagpath + flag + '" alt="' + escape(substring) + '" /> ';
		}

		if (current.icon) {
			if (!substring) {
				textLink += '<a href="" class="' + classes + '" rel="' + escape(current.entityid) + '" title="' + current.title + '">' + 
					'<img class="logo" src="' + iconpath + current.icon + '" />' +
					'<span class="title">' + current.title + '</span><hr style="clear: both; height: 0px; visibility:hidden" /></a>';
			} else {
				textLink += '<a href="" class="' + classes + '" rel="' + escape(current.entityid) + '" title="' + current.title + '">' + 
					'<img class="logo" src="' + iconpath +  current.icon + '" />' +
					'<span class="title">' + current.title + '</span>' + 
					'<span class="substring">' + flagtext + substring + '</span>' +
					'<hr style="clear: both; height: 0px; visibility:hidden" /></a>';
						}
		} else {
			if (!substring) {
				textLink += '<a href="" class="' + classes + '" rel="' + escape(current.entityid) + '"><span class="title">' + current.title + '</span></a>';		
			} else {
				textLink += '<a href="" class="' + classes + '" rel="' + escape(current.entityid) + '"><span class="title">' + current.title + '</span><span class="substring">' + flagtext + substring + '</span></a>';					
			}
	
		}
		this.resulthtml += textLink;
	},
	"refreshData": function() {
		var that = this;
		
		this.parent.Utils.log('DiscoJuice.UI refreshData()');
		
		this.popup.find("div.scroller").empty().append(this.resulthtml);
		this.popup.find("div.scroller a").each(function() {
			var overthere = that;	// Overthere is a reference to the UI object
			$(this).click(function(event) {
				event.preventDefault();
				overthere.hide();
				var entityid = unescape($(this).attr('rel'));
				overthere.control.selectProvider(entityid);
			});
		});
	},

	"enable": function(control) {
		var html = 	'<div style="display: none" class="discojuice">' +
			'<div class="top">' +
				'<a href="#" class="discojuice_close">&nbsp;</a>' +
				'<p class="discojuice_maintitle">' + this.parent.Utils.options.get('title', 'Title')  +  '</p>' +
				'<p class="discojuice_subtitle">' + this.parent.Utils.options.get('subtitle', 'Subtitle') + '</p>' +
			'</div>' +
			'<div id="content" style="">' +
				'<p class="moretext"></p>' +
				'<div class="scroller"></div>' +
			'</div>' +
	
			'<div id="search" class="" >' +
				'<p><input type="search" class="discojuice_search" results=5 autosave="discojuice" name="searchfield" placeholder="or search for a provider, in example Univerity of Oslo" value="" /></p>' +
				'<div class="discojuice_whatisthis" style="margin-top: 15px; font-size: 11px;">' +
					'<a  href="#" class="textlink discojuice_what">Help me, I cannot find my provider</a>' +
//					'<p class="discojuice_whattext">If your institusion is not connected to Foodle, you may either select to login one of the commercial providers such as Facebook or Google, or you may create a new account using any of the Guest providers, such as Feide OpenIdP.</p>' +
					'<p class="discojuice_whattext">If your institusion is not connected to Foodle, you may create a new account using any of the Guest providers, such as <strong>OpenIdP (Guest users)</strong>.</p>' +
				'</div>' +
			'</div>' +
			
			'<div class="filters bottom">' +
				'<p id="filterCountry"></p>' +
				'<p id="filterType"></p>' +
				'<p class="discojuice_showall" ><a class="discojuice_showall textlink" href="">Show all providers</a></p>' +
				'<p style="margin 0px; text-align: right; color: #ccc; font-size: x-small">DiscoJuice &copy; 2011, UNINETT</p>' +
			'</div>' +
	
// 			'<dd id="locatemediv">' +
// 				'<img style="float: left; margin-right: 5px" src="ulx/images/target.png" alt="locate me..." />' +
// 				'<p style="margin-top: 10px"><a id="locateme" href="">' +
// 					'Locate me</a> to show providers nearby' +
// 				'</p>' +
// 				'<p style="color: #999" id="locatemeinfo"></p>' +
// 				'<div style="clear: both" >' +
// 				'</div>' +
// 			'</dd>' +
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


		// Add listeners to the close button.
		this.popup.find(".discojuice_close").click(function() {
			that.hide();
		});

 		// Add toogle for what is this text.
		this.popup.find(".discojuice_what").click(function() {
			that.popup.find(".discojuice_whatisthis").toggleClass("show");
		});


// 	
// 		
// 		// Add listener to show all providers button.
// 		$("p#showall a").click(function(event){
// 			event.preventDefault();
// 			$("select#filterCountrySelect").val('all');	
// 			DiscoJuice.listResults(true);
// 			$("p#showall").hide();
// 		});
// 		$("p#showall").hide();
// 		
// 		//locateMe();
// 	
// 		// Setup filter by type.
// 		if (DiscoJuice.options.get('location', false) && navigator.geolocation) {
// 			$("#locateme").click(function(event) {
// 				event.preventDefault();
// 				DiscoJuice.locateMe();
// 			});
// 		} else {
// 			$("dd#locatemediv").hide();
// 		}	
// 	
// 	
// 		// Setup filter by type.
// 		if (DiscoJuice.options.get('type', false)) {
// 			DiscoJuice.filterTypeSetup();
// 		}
// 	
// 	
// 		// Setup filter by country.
// 		if (DiscoJuice.options.get('country', false)) {
// 			DiscoJuice.filterCountrySetup();
// 		}
// 		
// 		

// 		
// 			
// 		if (DiscoJuice.options.get('location', false)) {
// 			$("#locateme").click(function(event) {
// 				event.preventDefault();
// 				DiscoJuice.locateMe();
// 			});
// 		} else {
// 			$("dd#locatemediv").hide();
// 		}	
// 		
// 		/*
// 			Initialise the search box.
// 			*/
// 		$("input#ulxSearchField").autocomplete({
// 			minLength: 2,
// 			source: function( request, response ) {
// 				var term = request.term;
// 				var result;
// 				
// 				$("select#filterCountrySelect").val('all');
// 							
// 	//			$("dd#content img.spinning").show();
// 				DiscoJuice.listResults();
// 	//			$("dd#content img.spinning").hide();
// 			}
// 		});
// 	
// 		// List the initial results...
// 		// DiscoJuice.listResults();

	
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
	
	/*
	 * Fetching JSON Metadata using AJAX.
	 * Callback postLoad is called when data is returned.
	 */
	"load": function() {
		var that = this;		
		if (this.data) return;
		var metadataurl = this.parent.Utils.options.get('metadata');
		
		this.parent.Utils.log('metadataurl is ' + metadataurl);
		if (!metadataurl) return;
		
		$.getJSON(metadataurl, function(data) {
			that.data = data;
			that.parent.Utils.log('Successfully loaded metadata');
			that.postLoad();
		});
	},
	
	"postLoad": function() {
		if (!this.data) return;
		this.readCookie();
		this.prepareData();
		this.discoReadSetup();
		this.showallSetup();
		this.searchboxSetup();		
		this.filterCountrySetup();
		this.getCountry();
		
	},
	
	"readCookie": function() {
		if (this.parent.Utils.options.get('cookie', false)) {
			var selected = this.parent.Utils.readCookie();
			this.parent.Utils.log('COOKIE read ' + selected);
			if(selected) this.setWeight(selected, -100);			
		}
	},
	
	
	
	/*
	 * Set weight to a specific data entry.
	 */
	"setWeight": function(entityid, weight) {
		for(i = 0; i < this.data.length; i++) {
			if (this.data[i].entityid == entityid) {
				if (isNaN(this.data[i].weight)) this.data[i].weight = 0;
				this.data[i].weight += weight;
				this.parent.Utils.log('COOKIE Setting weight to ' + this.data[i].weight);
			}
		}
	},
	
	"discoResponse": function(entityid) {
		this.setWeight(entityid, -100);
		this.prepareData();
	},
	
	
	"prepareData": function(showall) {
	
		var showall = (showall ? true : false);
	
		this.parent.Utils.log('DiscoJuice.Control prepareData()');
		
		var hits, i, current, search;
 		var maxhits = 10;
// 		
 		var term = this.getTerm();
 		var categories = this.getCategories();
// 	
// 		var textIcon = '';
		
		if (!this.data) return;
		
		/*
		 * Sort data by weight...
		 */
		this.data.sort(function(a, b) {
			var xa, xb;		
			xa = (a.weight ? a.weight : 0);
			xb = (b.weight ? b.weight : 0);
			return (xa-xb);
		});
		
		if (term || categories) {
			this.ui.popup.find("p.discojuice_showall").show();
		} else {
			this.ui.popup.find("p.discojuice_showall").hide();
		}
		if (categories) {
			maxhits = 25;
		}
		if (showall) {
			maxhits = 200;
		}
// 		if (term) {
// 			maxhits = 10;
// 		}
	
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

			if (++hits > maxhits) { //  && showall !== true) {
				this.ui.popup.find("p.discojuice_showall").show();
				break;
			}
			
	// 		DiscoJuice.log('Accept: ' + current.title);
	
			if (search === true) {
				if (current.descr) {
					this.ui.addItem(current, current.descr);
				} else if (current.country) {
					var cname = (this.parent.Constants.Countries[current.country] ? this.parent.Constants.Countries[current.country] : current.country);
					if (cname === '_all_') cname = '';
					var cflag = (this.parent.Constants.Flags[current.country] ? this.parent.Constants.Flags[current.country] : undefined);
					this.ui.addItem(current, cname, cflag);
				} else {
					this.ui.addItem(current);
				}

			} else if (search === null) {
//				this.ui.addItem(current);

				var cname = (this.parent.Constants.Countries[current.country] ? this.parent.Constants.Countries[current.country] : current.country);
				if (cname === '_all_') cname = '';
				var cflag = (this.parent.Constants.Flags[current.country] ? this.parent.Constants.Flags[current.country] : undefined);


				if (current.descr) {
					this.ui.addItem(current, current.descr, cflag);
				} else if (!categories.country && current.country) {
					this.ui.addItem(current, cname, cflag);
				} else {
					this.ui.addItem(current);
				}

			} else {
				this.ui.addItem(current, search);
			}

		}
		if (hits < maxhits) { //  && showall !== true) {
//			this.ui.popup.find("p.discojuice_showall").hide();
		}
		
		this.ui.refreshData();
		
		//log('Loaded ' + DiscoJuice.data.length + ' accounts to select from');
	},
	
	"discoWrite": function(entityid) {
		
	},
	
	"selectProvider": function(entityid) {			
		var callback;
		var that = this;
		var mustwait = that.discoWrite(entityid);
		
		if (this.parent.Utils.options.get('cookie', false)) {
			this.parent.Utils.log('COOKIE write ' + entityid);
			this.parent.Utils.createCookie(entityid);		
		}

		var entity = null;
		for(i = 0; i < this.data.length; i++) {
			if (this.data[i].entityid == entityid) {
				entity = this.data[i];
			}
		}

		console.log(entity);

		callback = this.parent.Utils.options.get('callback');	
		if (callback) {
			if (mustwait) {
				$.doTimeout(1000, function(){
					callback(entity);
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
			
			iframeurl = currentStore + '?entityID=' + escape(spentityid) + '&isPassive=true&returnIDParam=entityID&return=' + escape(returnurl);
			
			html = '<iframe src="' + iframeurl + '" style="display: none"></iframe>';
			this.ui.addContent(html);
		}
	},


	"discoWrite": function(e) {
	
		var settings = this.parent.Utils.options.get('disco');
		if (!settings) return false;
		if (!settings.writableStore) return false;
	
		var html = '';
		var returnurl = settings.url;
		var spentityid = settings.spentityid;
		var writableStore = settings.writableStore;
		
		this.parent.Utils.log('DiscoJuice.Control discoWrite(' + e + ') to ' + writableStore);
			
		iframeurl = writableStore + '?entityID=' + escape(spentityid) + '&IdPentityID=' + 
			escape(e) + '&isPassive=true&returnIDParam=bogus&return=' + escape(returnurl);
			
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
		ftext += '</select></p>';
		
		this.ui.addFilter(ftext).find("select").change(function(event) {
			event.preventDefault();
			//$("input#ulxSearchField").val('')
			//DiscoJuice.listResults();
			that.resetTerm();
			that.ui.focusSearch();
			that.prepareData();
		});
	},
	"setCountry": function(country) {
		if (this.parent.Constants.Countries[country]) {
			this.ui.popup.find('select.discojuice_filterCountrySelect').val(country);
			this.prepareData();		
		}
	},
	"getCountry": function() {
		// If countryAPI is set, then lookup by IP.
		var countryapi = this.parent.Utils.options.get('countryAPI', false);
		var that = this;
		
		if (countryapi) {
			
			var countrycache = this.parent.Utils.readCookie('Country');
		
			if (countrycache) {
				
				this.setCountry(countrycache);
				this.parent.Utils.log('DiscoJuice getCountry() : Found country in cache: ' + countrycache);
				
			} else {
				
				$.getJSON(countryapi, function(data) {
		//			DiscoJuice.log(data);
					if (data.status == 'ok' && data.country) {
						that.parent.Utils.createCookie(data.country, 'Country');
						that.setCountry(data.country);
						that.parent.Utils.log('DiscoJuice getCountry() : Country lookup succeeded: ' + data.country);
					} else {
						that.parent.Utils.log('DiscoJuice getCountry() : Country lookup failed: ' + (data.error || ''));
					}
				});
			
			}
		}
	},
	
	"showallSetup": function() {
		var that = this;
		this.ui.popup.find("a.discojuice_showall").click(function(event) {
			event.preventDefault();
			that.resetCategories();
			that.resetTerm();
			that.prepareData(true);
			that.ui.focusSearch();
		});
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
	},


};