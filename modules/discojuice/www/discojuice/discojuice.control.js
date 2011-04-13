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
		
		this.parent.Utils.log('metadataurl is ' + metadataurl);
		if (!metadataurl) return;
		
		$.getJSON(metadataurl, function(data) {
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
		
		this.ui.show();
	
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
		
		if (term || categories) {
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