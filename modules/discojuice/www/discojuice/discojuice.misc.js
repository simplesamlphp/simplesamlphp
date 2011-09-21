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
		if (item.descr && item.descr.toLowerCase().search(term.toLowerCase()) !== -1) return true;
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
	},
	
	
	"waiter": function (completed, waitSeconds) {
		
		var
		 	my = {},
			parallellActions = [],
			executed = false;
		
		
		function execute () {
			
			if (executed) {
				console.log('Execution cancelled. Already performed.');
				return;
			}
			
			executed = true;
			completed(my);
		}
		
		function runAction (act, tooLate) {
			var
				thisAction = {completed: false};
				
			parallellActions.push(thisAction);
			console.log('Running action ' + parallellActions.length);
			act(function () {
				var i;
				thisAction.completed = true;
				for (i = 0; i < parallellActions.length; i++) {
					if (!parallellActions[i].completed) {
						console.log('Cannot execute because we are waiting for another action to complete.');
						return;
					}
				}
				if (executed) {
					console.log('All actions completed. Too late for executing...');
					tooLate();
					return;
				}
				console.log('All actions completed. Executing!');
				execute();
			});
			
		}
		
		function startTimer() {
			if (parallellActions.length === 0) {
				console.log('Executing because no action is scheduled....');
				if (!executed) execute();
				return;
			}
			
			setTimeout(function() {
				console.log('Action timeout!');
				if (!executed) execute();
			}, waitSeconds);
			
		}
		

		my.startTimer = startTimer;
		my.runAction = runAction;
		return my;
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


