/*
 * IdP Discovery Service
 *
 * An implementation of the IdP Discovery Protocol in Javascript
 * 
 * Author: Andreas Åkre Solberg, UNINETT, andreas.solberg@uninett.no
 * Licence: LGPLv2
 */

var IdPDiscovery = function() {

	var acl = true;
	var returnURLs = [];
	var serviceNames = {
		'http://dev.andreas.feide.no/simplesaml/module.php/saml/sp/metadata.php/default-sp': 'Andreas Developer SP',
		'https://beta.foodl.org/simplesaml/module.php/saml/sp/metadata.php/saml': 'Foodle Beta',
		'https://foodl.org/simplesaml/module.php/saml/sp/metadata.php/saml': 'Foodle',
		'https://ow.feide.no/simplesaml/module.php/saml/sp/metadata.php/default-sp': 'Feide OpenWiki',
		'https://openwiki.feide.no/simplesaml/module.php/saml/sp/metadata.php/default-sp': 'Feide OpenWiki Administration',
		'https://rnd.feide.no/simplesaml/module.php/saml/sp/metadata.php/saml': 'Feide Rnd',
		'http://ulx.foodl.org/simplesaml/module.php/saml/sp/metadata.php/saml': 'Foodle ULX Demo'
	};
	
	var query = {};
	(function () {
		var e,
			a = /\+/g,  // Regex for replacing addition symbol with a space
			r = /([^&;=]+)=?([^&;]*)/g,
			d = function (s) { return decodeURIComponent(s.replace(a, " ")); },
			q = window.location.search.substring(1);

		while (e = r.exec(q))
		   query[d(e[1])] = d(e[2]);
	})();
	
	return {
		
		"nameOf": function(entityid) {
			if (serviceNames[entityid]) return serviceNames[entityid];
			return entityid;
		},
		"getSP": function() {
			return (query.entityID || null);
		},
		"getName": function() {
			return this.nameOf(this.getSP());
		},
		
		// This function takes an url as input and returns the hostname.
		"getHostname" : function(str) {
			var re = new RegExp('^(?:f|ht)tp(?:s)?\://([^/]+)', 'im');
			return str.match(re)[1].toString();
		},
		
		"returnTo": function(e) {
			
			var returnTo = query['return'] || null;
			var returnIDParam = query.returnIDParam || 'entityID';
			if(!returnTo) {
				DiscoJuice.Utils.log('Missing required parameter [return]');
				return;
			}
			if (acl) {
				var allowed = false;
				
				var returnToHost = this.getHostname(returnTo);
				
				for (var i = 0; i < returnURLs.length; i++) {
					if (returnURLs[i] == returnToHost) allowed = true;
				}
				
				if (!allowed) {
					DiscoJuice.Utils.log('Access denied for return parameter [' + returnToHost + ']');
					return;
				}
			}
			
			if (e && e.auth) {
				returnTo += '&auth=' + e.auth;
			}
			
			if (!e.entityid) {
				window.location = returnTo;
			} else {
				window.location = returnTo + '&' + returnIDParam + '=' + escape(e.entityid);
			}
			
			

		},
		
		"receive": function() {
		
			var entityID = this.getSP();

			if(!entityID) {
				DiscoJuice.Utils.log('Missing required parameter [entityID]');
				return;
			}
			
			var preferredIdP = DiscoJuice.Utils.readCookie() || null;
			
			if (query.IdPentityID) {
				DiscoJuice.Utils.createCookie(query.IdPentityID);
				preferredIdP = query.IdPentityID;
			}
			
			var isPassive = query.isPassive || 'false';
			
			if (isPassive === 'true') {
				this.returnTo(preferredIdP);
			}
		},
		

		
		"setup": function(options, rurls) {

			var that = this;
				
			this.returnURLs = rurls;
			
			$(document).ready(function() {
				var overthere = that;
				var name = overthere.getName();
				if (!name) name = 'unknown service';
				
				options.callback = function(e) {
					overthere.returnTo(e); 
				};
				
				$("a.signin").DiscoJuice(options);
				$("div.noscript").hide();
			});
			
		}
		
	};
}();

