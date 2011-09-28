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
		'AF': 'Afghanistan',
		'AX': 'Åland Islands',
		'AL': 'Albania',
		'DZ': 'Algeria',
		'AS': 'American Samoa',
		'AD': 'Andorra',
		'AO': 'Angola',
		'AI': 'Anguilla',
		'AQ': 'Antarctica',
		'AG': 'Antigua and Barbuda',
		'AR': 'Argentina',
		'AM': 'Armenia',
		'AW': 'Aruba',
		'AC': 'Ascension Island',
		'AU': 'Australia',
		'AT': 'Austria',
		'AZ': 'Azerbaijan',
		'BS': 'Bahamas', //The Bahamas
		'BH': 'Bahrain',
		'BD': 'Bangladesh',
		'BB': 'Barbados',
		'BY': 'Belarus',
		'BE': 'Belgium',
		'BZ': 'Belize',
		'BJ': 'Benin',
		'BM': 'Bermuda',
		'BT': 'Bhutan',
		'BO': 'Bolivia',
		'BQ': 'Bonaire, Sint Eustatius and Saba', //Caribbean Netherlands
		'BA': 'Bosnia and Herzegovina',
		'BW': 'Botswana',
		'BV': 'Bouvet Island',
		'BR': 'Brazil',
		'IO': 'British Indian Ocean Territory',
		'VG': 'British Virgin Islands', //Virgin Islands, British
		'BN': 'Brunei Darussalam', // Brunei
		'BG': 'Bulgaria',
		'BF': 'Burkina Faso',
		'MM': 'Burma', //Myanmar
		'BI': 'Burundi',
		'KH': 'Cambodia',
		'CM': 'Cameroon',
		'CA': 'Canada',
		'CV': 'Cape Verde',
		'KY': 'Cayman Islands',
		'CF': 'Central African Republic',
		'TD': 'Chad',
		'CL': 'Chile',
		'CN': 'China', //People's Republic of China
		'CX': 'Christmas Island',
		'CC': 'Cocos (Keeling) Islands',
		'CO': 'Colombia',
		'KM': 'Comoros',
		'CD': 'Congo, Democratic Republic of the', //Democratic Republic of the Congo
		'CG': 'Congo, Republic of the', //Republic of the Congo|Congo
		'CK': 'Cook Islands',
		'CR': 'Costa Rica',
		'CI': "Côte d'Ivoire",
		'HR': 'Croatia',
		'CU': 'Cuba',
		'CW': 'Curaçao',
		'CY': 'Cyprus',
		'CZ': 'Czech Republic',
 		'DK': 'Denmark',
		'DJ': 'Djibouti',
		'DM': 'Dominica',
		'DO': 'Dominican Republic',
		'EC': 'Ecuador',
		'EG': 'Egypt',
		'SV': 'El Salvador',
		'GQ': 'Equatorial Guinea',
		'ER': 'Eritrea',
		'EE': 'Estonia',
		'ET': 'Ethiopia',
		'FK': 'Falkland Islands', //|Falkland Islands (Malvinas)
		'FO': 'Faroe Islands',
		'FJ': 'Fiji',
 		'FI': 'Finland',
 		'FR': 'France',
		'GF': 'French Guiana',
		'PF': 'French Polynesia',
		'TF': 'French Southern and Antarctic Lands', //French Southern Territories
		'GA': 'Gabon',
		'GM': 'Gambia', //The Gambia
		'GE': 'Georgia',
 		'DE': 'Germany',
		'GH': 'Ghana',
		'GI': 'Gibraltar',
 		'GR': 'Greece',
		'GL': 'Greenland',
		'GD': 'Grenada',
		'GP': 'Guadeloupe',
		'GU': 'Guam',
		'GT': 'Guatemala',
		'GG': 'Guernsey',
		'GN': 'Guinea',
		'GW': 'Guinea-Bissau',
		'GY': 'Guyana',
		'HT': 'Haiti',
		'HM': 'Heard Island and McDonald Islands',
		'HN': 'Honduras',
		'HK': 'Hong Kong',
		'HU': 'Hungary',
		'IS': 'Iceland',
		'IN': 'India',
		'ID': 'Indonesia',
		'IR': 'Iran', //Iran, Islamic Republic of
		'IQ': 'Iraq',
		'IE': 'Ireland', //Republic of Ireland
		'IM': 'Isle of Man',
		'IL': 'Israel',
 		'IT': 'Italy',
		'JM': 'Jamaica',
 		'JP': 'Japan',
		'JE': 'Jersey',
		'JO': 'Jordan',
		'KZ': 'Kazakhstan',
		'KE': 'Kenya',
		'KI': 'Kiribati',
		'KP': 'North Korea', //Korea, Democratic People's Republic of
		'KR': 'South Korea', //Korea, Republic of
		'KW': 'Kuwait',
		'KG': 'Kyrgyzstan',
		'LA': 'Laos', //Lao People's Democratic Republic
		'LV': 'Latvia',
		'LB': 'Lebanon',
		'LS': 'Lesotho',
		'LR': 'Liberia',
		'LY': 'Libya', //Libyan Arab Jamahiriya
		'LI': 'Liechtenstein',
		'LT': 'Lithuania',
 		'LU': 'Luxembourg',
		'MO': 'Macau', //Macao|Macao Special Administrative Region of the People's Republic of China
		'MK': 'Macedonia', //Republic of Macedonia|FYR Macedonia|Macedonia, the former Yugoslav Republic of
		'MG': 'Madagascar',
		'MW': 'Malawi',
		'MY': 'Malaysia',
		'MV': 'Maldives',
		'ML': 'Mali',
		'MT': 'Malta',
		'MH': 'Marshall Islands',
		'MQ': 'Martinique',
		'MR': 'Mauritania',
		'MU': 'Mauritius',
		'YT': 'Mayotte',
		'MX': 'Mexico',
		'FM': 'Micronesia, Federated States of', //Federated States of Micronesia
		'MD': 'Moldova', //Moldova, Republic of
		'MC': 'Monaco',
		'MN': 'Mongolia',
		'ME': 'Montenegro',
		'MS': 'Montserrat',
		'MA': 'Morocco',
		'MZ': 'Mozambique',
		'NA': 'Namibia',
		'NR': 'Nauru',
		'NP': 'Nepal',
 		'NL': 'Netherlands',
		'NC': 'New Caledonia',
		'NZ': 'New Zealand',
		'NI': 'Nicaragua',
		'NE': 'Niger',
		'NG': 'Nigeria',
		'NU': 'Niue',
		'NF': 'Norfolk Island',
		'MP': 'Northern Mariana Islands',
 		'NO': 'Norway',
		'OM': 'Oman',
		'PK': 'Pakistan',
		'PW': 'Palau',
		'PS': 'Palestine', //State of Palestine|Palestinian territories|Palestinian Territory, Occupied
		'PA': 'Panama',
		'PG': 'Papua New Guinea',
		'PY': 'Paraguay',
		'PE': 'Peru',
		'PH': 'Philippines',
		'PN': 'Pitcairn Islands', //Pitcairn
 		'PL': 'Poland',
 		'PT': 'Portugal',
		'PR': 'Puerto Rico',
		'QA': 'Qatar',
		'RE': 'Réunion',
		'RO': 'Romania',
		'RU': 'Russia', //Russian Federation
		'RW': 'Rwanda',
		'BL': 'Saint Barthélemy',
		'SH': 'Saint Helena, Ascension and Tristan da Cunha',
		'KN': 'Saint Kitts and Nevis',
		'LC': 'Saint Lucia',
		'MF': 'Saint Martin', //Collectivity of Saint Martin|Saint Martin (French part)
		'PM': 'Saint Pierre and Miquelon',
		'VC': 'Saint Vincent and the Grenadines',
		'WS': 'Samoa',
		'SM': 'San Marino',
		'ST': 'São Tomé and Príncipe',
		'SA': 'Saudi Arabia',
		'SN': 'Senegal',
		'RS': 'Serbia',
		'SC': 'Seychelles',
		'SL': 'Sierra Leone',
		'SG': 'Singapore',
		'SX': 'Sint Maarten', //Sint Maarten (Dutch part)
		'SK': 'Slovakia',
 		'SI': 'Slovenia',
		'SB': 'Solomon Islands',
		'SO': 'Somalia',
		'ZA': 'South Africa',
		'GS': 'South Georgia and the South Sandwich Islands',
 		'ES': 'Spain',
		'LK': 'Sri Lanka',
		'SD': 'Sudan',
		'SR': 'Suriname',
		'SJ': 'Svalbard and Jan Mayen',
		'SZ': 'Swaziland',
 		'SE': 'Sweden',
 		'CH': 'Switzerland',
		'SY': 'Syria', //Syrian Arab Republic
		'TW': 'Taiwan',	//Taiwan, Province of China
		'TJ': 'Tajikistan',
		'TZ': 'Tanzania', //Tanzania, United Republic of
		'TH': 'Thailand',
		'TL': 'Timor-Leste', //East Timor
		'TG': 'Togo',
		'TK': 'Tokelau',
		'TO': 'Tonga',
		'TT': 'Trinidad and Tobago',
		'TN': 'Tunisia',
 		'TR': 'Turkey',
		'TM': 'Turkmenistan',
		'TC': 'Turks and Caicos Islands',
		'TV': 'Tuvalu',
		'UG': 'Uganda',
		'UA': 'Ukraine',
		'GB': 'UK', //United Kingdom|United Kingdom of Great Britian and Northern Ireland|Great Britian
		'AE': 'United Arab Emirates',
		'UM': 'United States Minor Outlying Islands',
		'UY': 'Uruguay',
		'US': 'USA', //United States of America|United States
		'UZ': 'Uzbekistan',
		'VU': 'Vanuatu',
		'VA': 'Vatican City', //Holy See (Vatican City State)
		'VE': 'Venezuela', //Venezuela, Bolivarian Republic of
		'VN': 'Viet Nam', //Vietnam,
		'VI': 'Virgin Islands, U.S.', //United States Virgin Islands,
		'WF': 'Wallis and Futuna',
		'EH': 'Western Sahara',
		'YE': 'Yemen',
		'ZM': 'Zambia',
		'ZW': 'Zimbabwe',
 		'XX': 'Experimental'
	},
	"Flags": {
		'AD': 'ad.png',
		'AE': 'ae.png',
		'AF': 'af.png',
		'AG': 'ag.png',
		'AI': 'ai.png',
		'AL': 'al.png',
		'AM': 'am.png',
		'AN': 'an.png',
		'AO': 'ao.png',
		'AR': 'ar.png',
		'AS': 'as.png',
		'AT': 'at.png',
		'AU': 'au.png',
		'AW': 'aw.png',
		'AX': 'ax.png',
		'AZ': 'az.png',
		'BA': 'ba.png',
		'BB': 'bb.png',
		'BD': 'bd.png',
		'BE': 'be.png',
		'BF': 'bf.png',
		'BG': 'bg.png',
		'BH': 'bh.png',
		'BI': 'bi.png',
		'BJ': 'bj.png',
		'BM': 'bm.png',
		'BN': 'bn.png',
		'BO': 'bo.png',
		'BR': 'br.png',
		'BS': 'bs.png',
		'BT': 'bt.png',
		'BV': 'bv.png',
		'BW': 'bw.png',
		'BY': 'by.png',
		'BZ': 'bz.png',
		'CA': 'ca.png',
		'CC': 'cc.png',
		'CD': 'cd.png',
		'CF': 'cf.png',
		'CG': 'cg.png',
		'CH': 'ch.png',
		'CI': 'ci.png',
		'CK': 'ck.png',
		'CL': 'cl.png',
		'CM': 'cm.png',
		'CN': 'cn.png',
		'CO': 'co.png',
		'CR': 'cr.png',
		'CS': 'cs.png',
		'CU': 'cu.png',
		'CV': 'cv.png',
		'CX': 'cx.png',
		'CY': 'cy.png',
 		'CZ': 'cz.png',
		'DE': 'de.png',
		'DJ': 'dj.png',
 		'DK': 'dk.png',
		'DM': 'dm.png',
		'DO': 'do.png',
		'DZ': 'dz.png',
		'EC': 'ec.png',
		'EE': 'ee.png',
		'EG': 'eg.png',
		'EH': 'eh.png',
		'ER': 'er.png',
		'ES': 'es.png',
		'ET': 'et.png',
 		'FI': 'fi.png',
		'FJ': 'fj.png',
		'FK': 'fk.png',
		'FM': 'fm.png',
		'FO': 'fo.png',
 		'FR': 'fr.png',
		'GA': 'ga.png',
		'GB': 'gb.png',
		'GD': 'gd.png',
		'GE': 'ge.png',
		'GF': 'gf.png',
		'GH': 'gh.png',
		'GI': 'gi.png',
		'GL': 'gl.png',
		'GM': 'gm.png',
		'GN': 'gn.png',
		'GP': 'gp.png',
		'GQ': 'gq.png',
 		'GR': 'gr.png',
		'GS': 'gs.png',
		'GT': 'gt.png',
		'GU': 'gu.png',
		'GW': 'gw.png',
		'GY': 'gy.png',
		'HK': 'hk.png',
		'HM': 'hm.png',
		'HN': 'hn.png',
 		'HR': 'hr.png',
		'HT': 'ht.png',
		'HU': 'hu.png',
		'ID': 'id.png',
 		'IE': 'ie.png',
		'IL': 'il.png',
		'IN': 'in.png',
		'IO': 'io.png',
		'IQ': 'iq.png',
		'IR': 'ir.png',
		'IS': 'is.png',
 		'IT': 'it.png',
		'JM': 'jm.png',
		'JO': 'jo.png',
 		'JP': 'jp.png',
		'KE': 'ke.png',
		'KG': 'kg.png',
		'KH': 'kh.png',
		'KI': 'ki.png',
		'KM': 'km.png',
		'KN': 'kn.png',
		'KP': 'kp.png',
		'KR': 'kr.png',
		'KW': 'kw.png',
		'KY': 'ky.png',
		'KZ': 'kz.png',
		'LA': 'la.png',
		'LB': 'lb.png',
		'LC': 'lc.png',
		'LI': 'li.png',
		'LK': 'lk.png',
		'LR': 'lr.png',
		'LS': 'ls.png',
		'LT': 'lt.png',
 		'LU': 'lu.png',
		'LV': 'lv.png',
		'LY': 'ly.png',
		'MA': 'ma.png',
		'MC': 'mc.png',
		'MD': 'md.png',
		'ME': 'me.png',
		'MG': 'mg.png',
		'MH': 'mh.png',
		'MK': 'mk.png',
		'ML': 'ml.png',
		'MM': 'mm.png',
		'MN': 'mn.png',
		'MO': 'mo.png',
		'MP': 'mp.png',
		'MQ': 'mq.png',
		'MR': 'mr.png',
		'MS': 'ms.png',
		'MT': 'mt.png',
		'MU': 'mu.png',
		'MV': 'mv.png',
		'MW': 'mw.png',
		'MX': 'mx.png',
		'MY': 'my.png',
		'MZ': 'mz.png',
		'NA': 'na.png',
		'NC': 'nc.png',
		'NE': 'ne.png',
		'NF': 'nf.png',
		'NG': 'ng.png',
		'NI': 'ni.png',
 		'NL': 'nl.png',
 		'NO': 'no.png',
		'NP': 'np.png',
		'NR': 'nr.png',
		'NU': 'nu.png',
		'NZ': 'nz.png',
		'OM': 'om.png',
		'PA': 'pa.png',
		'PE': 'pe.png',
		'PF': 'pf.png',
		'PG': 'pg.png',
		'PH': 'ph.png',
		'PK': 'pk.png',
 		'PL': 'pl.png',
		'PM': 'pm.png',
		'PN': 'pn.png',
		'PR': 'pr.png',
		'PS': 'ps.png',
 		'PT': 'pt.png',
		'PW': 'pw.png',
		'PY': 'py.png',
		'QA': 'qa.png',
		'RE': 're.png',
		'RO': 'ro.png',
		'RS': 'rs.png',
		'RU': 'ru.png',
		'RW': 'rw.png',
		'SA': 'sa.png',
		'SB': 'sb.png',
		'SC': 'sc.png',
		'SD': 'sd.png',
		'SE': 'se.png',
		'SG': 'sg.png',
		'SH': 'sh.png',
 		'SI': 'si.png',
		'SJ': 'sj.png',
		'SK': 'sk.png',
		'SL': 'sl.png',
		'SM': 'sm.png',
		'SN': 'sn.png',
		'SO': 'so.png',
		'SR': 'sr.png',
		'ST': 'st.png',
		'SV': 'sv.png',
		'SY': 'sy.png',
		'SZ': 'sz.png',
		'TC': 'tc.png',
		'TD': 'td.png',
		'TF': 'tf.png',
		'TG': 'tg.png',
		'TH': 'th.png',
		'TJ': 'tj.png',
		'TK': 'tk.png',
		'TL': 'tl.png',
		'TM': 'tm.png',
		'TN': 'tn.png',
		'TO': 'to.png',
 		'TR': 'tr.png',
		'TT': 'tt.png',
		'TV': 'tv.png',
		'TW': 'tw.png',
		'TZ': 'tz.png',
		'UA': 'ua.png',
		'UG': 'ug.png',
		'UM': 'um.png',
		'US': 'us.png',
		'UY': 'uy.png',
		'UZ': 'uz.png',
		'VA': 'va.png',
		'VC': 'vc.png',
		'VE': 've.png',
		'VG': 'vg.png',
		'VI': 'vi.png',
		'VN': 'vn.png',
		'VU': 'vu.png',
		'WF': 'wf.png',
		'WS': 'ws.png',
		'YE': 'ye.png',
		'YT': 'yt.png',
		'ZA': 'za.png',
		'ZM': 'zm.png',
		'ZW': 'zw.png'
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
					if (my.allowMultiple) {
						console.log('Slow response; but executing anyway!!');
						execute();
					} else if (typeof tooLate === 'function') {
						console.log('All actions completed. Too late for executing...');
						tooLate();
					}
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

		my.allowMultiple = false;

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


