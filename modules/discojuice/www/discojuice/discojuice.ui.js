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
		
// 		console.log('Dump item');
// 		console.log(item);
// 		console.log(countrydef);
// 		console.log(search);
// 		console.log(distance);
		

		
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
		
		

		if (countrydef || distance) {
				
			textLink += '<span class="location">';
			if (countrydef) {
				textLink += '<span class="country">';
				if (countrydef.flag) textLink += '<img src="' + flagpath + countrydef.flag + '" alt="' + escape(countrydef.country) + '" /> ';
				textLink += countrydef.country + '</span>';
			}
	
			
			if (distance) {
				textLink += '<span class="distance">' +  Math.round(distance) + ' km' + '</span>';
			}
			textLink += '</span>';
// 			clear = true;
		}
		
		if (debugweight) {
			textLink += '<div class="debug">';
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
		textLink = '<a href="" class="' + classes + '" rel="' + escape(item.entityid) + '" title="' + escape(item.title) + '">' + 
			textLink + '</a>';


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
		var imgpath = this.parent.Utils.options.get('discoPath', '') + 'images/';
	
		var html = 	'<div style="display: none" class="discojuice">' +
			'<div class="top">' +
				'<a href="#" class="discojuice_close">&nbsp;</a>' +
				'<p class="discojuice_maintitle">' + this.parent.Utils.options.get('title', 'Title')  +  '</p>' +
				'<p class="discojuice_subtitle">' + this.parent.Utils.options.get('subtitle', 'Subtitle') + '</p>' +
			'</div>' +
			
			'<div id="content" style="">' +
				'<p class="moretext"></p>' +
				'<div class="scroller">' +
					'<div class="loadingData" ><img src="' + imgpath + 'spinning.gif" /> Loading list of providers...</div>' +
				'</div>' +
			'</div>' +
	
			'<div id="search" class="" >' +
				'<p><input type="search" class="discojuice_search" results=5 autosave="discojuice" name="searchfield" placeholder="or search for a provider, in example Univerity of Oslo" value="" /></p>' +
				'<div class="discojuice_whatisthis" style="margin-top: 15px; font-size: 11px;">' +
					'<a  href="#" class="textlink discojuice_what">Help me, I cannot find my provider</a>' +
//					'<p class="discojuice_whattext">If your institusion is not connected to Foodle, you may either select to login one of the commercial providers such as Facebook or Google, or you may create a new account using any of the Guest providers, such as Feide OpenIdP.</p>' +
					'<p class="discojuice_whattext">If your institusion is not connected to Foodle, you may create a new account using any of the Guest providers, such as <strong>OpenIdP (Guest users)</strong>.</p>' +
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
			
			'<div class="filters bottom">' +
// 				'<p id="filterCountry"></p>' +
// 				'<p id="filterType"></p>' +
				'<p class="discojuice_showall" ><a class="discojuice_showall textlink" href="">Show all providers</a></p>' +
				'<p style="margin 0px; text-align: right; color: #ccc; font-size: x-small">DiscoJuice &copy; 2011, UNINETT</p>' +
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
			$("#locateme").click(function(event) {
				var imgpath = that.parent.Utils.options.get('discoPath', '') + 'images/';
				event.preventDefault();
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

