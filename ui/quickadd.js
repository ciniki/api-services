//
// This file contains the reports for the services module
//
function ciniki_services_quickadd() {

	this.addressFlags = {'1':{'name':'Shipping'}, '2':{'name':'Billing'}, '3':{'name':'Mailing'}};
	this.emailFlags = {'1':{'name':'Web Login'}};
	//
	// Initialize panels
	//
	this.init = function() {
		//
		// The add panel display a form to allow the selection of an existing
		// customer or the addition of a new customer.
		//
		this.add = new M.panel('Add Customer Service',
			'ciniki_services_quickadd', 'add',
			'mc', 'medium', 'sectioned', 'ciniki.services.quickadd.add');
		this.add.customer_id = 0;
		this.add.data = {};
		this.add.forms = {};
		this.add.formtab = null;
		this.add.services = {};
		this.add.resetForm = function() {
			this.forms.person = {
				'name':{'label':'Name', 'fields':{
					'cid':{'label':'Customer ID', 'type':'text', 'active':'no', 'size':'medium'},
					'prefix':{'label':'Title', 'type':'text', 'size':'medium', 'hint':'Mr., Ms., Dr., ...'},
					'first':{'label':'First', 'type':'text', 'livesearch':'yes'},
					'middle':{'label':'Middle', 'type':'text'},
					'last':{'label':'Last', 'type':'text', 'livesearch':'yes'},
					'suffix':{'label':'Degrees', 'type':'text', 'size':'medium', 'hint':'Ph.D, M.D., Jr., ...'},
					'birthdate':{'label':'Birthday', 'active':'no', 'type':'date'},
					}},
				'business':{'label':'Business', 'fields':{
					'company':{'label':'Company', 'type':'text', 'livesearch':'yes'},
					'department':{'label':'Department', 'type':'text'},
					'title':{'label':'Title', 'type':'text'},
					}},
				'emails':{'label':'Email', 'multi':'yes', 'multiinsert':'first', 'fields':{
					'address':{'label':'Address', 'type':'text'},
					'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':M.ciniki_services_quickadd.emailFlags},
					}},
				'addresses':{'label':'Address', 'multi':'yes', 'multiinsert':'first', 'fields':{
					'address1':{'label':'Street', 'type':'text', 'hint':''},
					'address2':{'label':'', 'type':'text'},
					'city':{'label':'City', 'type':'text', 'size':'small', 'livesearch':'yes'},
					'province':{'label':'Province/State', 'type':'text', 'size':'small'},
					'postal':{'label':'Postal/Zip', 'type':'text', 'hint':'', 'size':'small'},
					'country':{'label':'Country', 'type':'text', 'hint':'', 'size':'small'},
					'address_flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':M.ciniki_services_quickadd.addressFlags},
					}},
				'phones':{'label':'Phone Numbers', 'fields':{
					'phone_home':{'label':'Home', 'type':'text', 'size':'medium'},
					'phone_work':{'label':'Work', 'type':'text', 'size':'medium'},
					'phone_cell':{'label':'Cell', 'type':'text', 'size':'medium'},
					'phone_fax':{'label':'Fax', 'type':'text', 'size':'medium'},
					}},
				'services':{'label':'Services', 'multi':'yes', 'multiinsert':'first', 'fields':{
					'service_id':{'label':'Service', 'type':'select', 'options':this.services},
					'pstart_date':{'label':'Start Date', 'type':'date'},
					'tracking_id':{'label':'Tracking ID', 'type':'text', 'size':'medium'},
					}},
				'_notes':{'label':'Notes', 'fields':{
					'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
					}},
				'_save':{'label':'', 'buttons':{
					'save':{'label':'Save customer', 'fn':'M.ciniki_services_quickadd.save();'},
					}},
			};
			if( this.person_services != null ) {
				this.forms.person.services.fields.service_id.options = this.person_services;
			} else {
				this.forms.person.services.fields.service_id.options = this.services;
			}
			this.forms.business = {
				'business':{'label':'Business', 'fields':{
					'cid':{'label':'Customer ID', 'type':'text', 'active':'no'},
					'company':{'label':'Name', 'type':'text', 'livesearch':'yes'},
					}},
				'addresses':{'label':'Address', 'multi':'yes', 'multiinsert':'first', 'fields':{
					'address1':{'label':'Street', 'type':'text', 'hint':''},
					'address2':{'label':'', 'type':'text'},
					'city':{'label':'City', 'type':'text', 'size':'small', 'livesearch':'yes'},
					'province':{'label':'Province/State', 'type':'text', 'size':'small'},
					'postal':{'label':'Postal/Zip', 'type':'text', 'hint':'', 'size':'small'},
					'country':{'label':'Country', 'type':'text', 'hint':'', 'size':'small'},
					'address_flags':{'label':'Options', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':M.ciniki_services_quickadd.addressFlags},
					}},
				'name':{'label':'Contact', 'fields':{
					'prefix':{'label':'Title', 'type':'text', 'size':'medium', 'hint':'Mr., Ms., Dr., ...'},
					'first':{'label':'First', 'type':'text', 'livesearch':'yes'},
					'middle':{'label':'Middle', 'type':'text'},
					'last':{'label':'Last', 'type':'text', 'livesearch':'yes'},
					'suffix':{'label':'Degrees', 'type':'text', 'size':'medium', 'hint':'Ph.D, M.D., Jr., ...'},
					}},
				'emails':{'label':'Email', 'multi':'yes', 'multiinsert':'first','fields':{
					'address':{'label':'Address', 'type':'text'},
					'flags':{'label':'Options', 'active':'no', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':M.ciniki_services_quickadd.emailFlags},
					}},
				'phones':{'label':'Phone Numbers', 'fields':{
					'phone_home':{'label':'Home', 'type':'text', 'size':'medium'},
					'phone_work':{'label':'Work', 'type':'text', 'size':'medium'},
					'phone_cell':{'label':'Cell', 'type':'text', 'size':'medium'},
					'phone_fax':{'label':'Fax', 'type':'text', 'size':'medium'},
					}},
				'services':{'label':'Services', 'multi':'yes', 'multiinsert':'first', 'fields':{
					'service_id':{'label':'Service', 'type':'select', 'options':this.services},
					'pstart_date':{'label':'Start Date', 'type':'date'},
					'tracking_id':{'label':'Tracking ID', 'type':'text', 'size':'medium'},
					}},
				'_notes':{'label':'Notes', 'fields':{
					'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
					}},
				'_save':{'label':'', 'buttons':{
					'save':{'label':'Save', 'fn':'M.ciniki_services_quickadd.save();'},
					}},
			};
			if( this.business_services != null ) {	
				this.forms.business.services.fields.service_id.options = this.business_services;
			} else {
				this.forms.business.services.fields.service_id.options = this.services;
			}
		}
		this.add.sectionCount = function(s) { 
			if( this.data[s] == null || this.data[s].length == null ) { return 1; }
			return this.data[s].length;
			}
		this.add.fieldValue = function(s, i, d, j) { 
			if( j != null ) {
				if( i == 'address' || i == 'flags' ) { 
					if( this.data['emails'] != null && this.data['emails'][j] != null ) { return this.data['emails'][j]['email'][i]; }
					return '';
				}
				if( i == 'address1' || i == 'address2' || i == 'city' || i == 'province' || i == 'postal' || i == 'country' ) { 
					if( this.data['addresses'] != null && this.data['addresses'][j] != null ) { return this.data['addresses'][j]['address'][i]; }
					return '';
				}
				if( i == 'address_flags' ) {
					if( this.data['addresses'] != null && this.data['addresses'][j] != null ) { return this.data['addresses'][j]['address']['flags']; }
					return 7;
				}
				if( i == 'pstart_date' || i == 'tracking_id' ) {
					if( this.data['services'] != null && this.data['services'][j] != null ) { return this.data['services'][j]['service'][i]; }
					return '';
				}
			}
			return this.data[i]; 
			}
		this.add.storeFieldValue = function(s, i, n, j) {
			if( s == 'emails' || s == 'addresses' || s == 'services' ) {
				if( this.data[s] == null ) {
					this.data[s] = [];
				}
				if( this.data[s][j] == null ) {
					this.data[s][j] = {};
				}
				if( i == 'address' || i == 'flags' ) {
					if( this.data[s][j]['email'] == null ) { this.data[s][j]['email'] = {}; }	
					this.data[s][j]['email'][i] = n;
				}
				if( i == 'address1' || i == 'address2' || i == 'city' || i == 'province' || i == 'postal' || i == 'country' ) { 
					if( this.data[s][j]['address'] == null ) { this.data[s][j]['address'] = {}; }	
					this.data[s][j]['address'][i] = n;
				}
				if( i == 'address_flags' ) { 
					if( this.data[s][j]['address'] == null ) { this.data[s][j]['address'] = {}; }	
					this.data[s][j]['address']['flags'] = n;
				}
				if( i == 'service_id' || i == 'pstart_date' || i == 'tracking_id' ) { 
					if( this.data[s][j]['service'] == null ) { this.data[s][j]['service'] = {}; }	
					this.data[s][j]['service'][i] = n;
				}
			}
		};
		this.add.setDefaultFieldValue = function(s, i, j) {
			if( i == 'flags' ) {
				this.storeFieldValue(s, i, 0, j);
			} else if( i == 'address_flags' ) {
				this.storeFieldValue(s, i, 7, j);
			} else {
				this.storeFieldValue(s, i, '', j);
			}
		};
		this.add.listValue = function(s, i, d) { return d['label']; };
		this.add.liveSearchCb = function(s, i, value) {
			if( i == 'first' || i == 'last' || i == 'company' ) {
				var rsp = M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
					function(rsp) { 
						M.ciniki_services_quickadd.add.liveSearchShow(s, i, M.gE(M.ciniki_services_quickadd.add.panelUID + '_' + i), rsp.customers); 
					});
			}
			if( i.match(/^city_/) ) {
				var rsp = M.api.getJSONBgCb('ciniki.customers.addressSearchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
					function(rsp) { 
						M.ciniki_services_quickadd.add.liveSearchShow(s, i, M.gE(M.ciniki_services_quickadd.add.panelUID + '_' + i), rsp.cities); 
					});
			}
		};
		this.add.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'first' || f == 'last' || f == 'company' ) { return d.customer.name; }
			if( f.match(/^city_/) ) { return d.city.name + ',' + d.city.province; }
			return '';
		};
		this.add.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'first' || f == 'last' || f == 'company' ) {
				return 'M.ciniki_services_quickadd.add.updateCustomer(\'' + s + '\',\'' + d.customer.id + '\');';
			}
			if( f.match(/^city_/) ) {
				var x = f.replace(/^city_/, '');
				return 'M.ciniki_services_quickadd.add.updateCity(\'' + s + '\',\'' + x + '\',\'' + escape(d.city.name) + '\',\'' + escape(d.city.province) + '\',\'' + escape(d.city.country) + '\');';
			}
		};
		this.add.updateCustomer = function(s, cid) {
			// Load customer information
			this.customer_id = cid;
			this.formtab = null;
			this.formtab_field_id = null;
			this.resetForm();
			var rsp = M.api.getJSON('ciniki.customers.getFull', {'business_id':M.curBusinessID, 'customer_id':cid});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			this.data = rsp.customer;
			if( this.data.emails == null || this.data.emails.length == 0 ) {
				this.data.emails = [];
				this.data.emails[0] = {'email':{'address':'', 'flags':0}};
			} 
			if( this.data.addresses == null || this.data.addresses.length == 0 ) {
				this.data.addresses = [];
				this.data.addresses[0] = {'address':{'address1':'','address2':'','city':'', 'province':'','postal':'','country':'','flags':7,}};
			}
			this.data.services = [];
			this.data.services[0] = {'service':{'service_id':'', 'pstart_date':'','tracking_id':''}};
			if( rsp.customer.type == 0 ) {
				this.formtab = 'person';
			}

//			FIXME: Don't need to load customerSubscriptions.
//			var rsp = M.api.getJSON('ciniki.services.customerSubscriptions', {'business_id':M.curBusinessID, 'customer_id':cid});
//			if( rsp.stat != 'ok' ) {
//				M.api.err(rsp);
//				return false;
//			}
//			for(i in rsp.subscriptions) {
//				this.data.
//			}
			

			this.refresh();
			this.show();

			// FIXME: Change form back on type of customer returned

			// Load the customer services
		};
		this.add.updateCity = function(s, x, city, province, country) {
			M.gE(this.panelUID + '_city_' + x).value = city;
			M.gE(this.panelUID + '_province_' + x).value = province;
			M.gE(this.panelUID + '_country_' + x).value = country;
			this.removeLiveSearch(s, 'city_' + x);
		};
		this.add.addButton('save', 'Save', 'M.ciniki_services_quickadd.save();');
		this.add.addClose('Cancel');
	}

	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_services_quickadd', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		
		//
		// Load list of services, and setup forms
		//
		this.add.services = [];
		this.add.person_services = [];
		this.add.business_services = [];
		var rsp = M.api.getJSON('ciniki.services.servicesRepeating', {'business_id':M.curBusinessID});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		var p = 0;
		var b = 0;
		for(i in rsp.services) {
			if( M.curBusiness.services != null && M.curBusiness.services.settings['ui-form-person-cateogry'] != '' 
				&& M.curBusiness.services.settings['ui-form-person-category'] == rsp.services[i].service.category ) {
				this.add.person_services[rsp.services[i].service.id] = rsp.services[i].service.name;
			} else if( M.curBusiness.services != null && M.curBusiness.services.settings['ui-form-business-cateogry'] != '' 
				&& M.curBusiness.services.settings['ui-form-business-category'] == rsp.services[i].service.category ) {
				this.add.business_services[rsp.services[i].service.id] = rsp.services[i].service.name;
			}
			this.add.services[rsp.services[i].service.id] = rsp.services[i].service.name;
		}

		this.add.resetForm();
		this.add.reset();
		this.add.data = {'emails':[], 'addresses':[], 'services':[]};
		this.add.data.emails[0] = {'email':{'address':'', 'flags':0}};
		this.add.data.addresses[0] = {'address':{'address1':'','address2':'','city':'', 'province':'','postal':'','country':'','flags':7,}};
		this.add.data.services[0] = {'service':{'service_id':'', 'pstart_date':'','tracking_id':''}};
		this.add.formtabs = {};
		this.add.customer_id = 0;
		if( M.curBusiness.customers != null && M.curBusiness.customers.settings != null ) {
			this.add.formtabs = {'label':'', 'field':'type', 'tabs':{}};
			var count=0;
			for(i=1;i<9;i++) {
				// Setup the form tabs for the customers add/edit forms
				if( M.curBusiness.customers.settings['types-'+i+'-label'] != null && M.curBusiness.customers.settings["types-"+i+"-label"] != '' ) {
					count++;
					this.add.formtabs.tabs[i] = {'label':M.curBusiness.customers.settings['types-'+i+'-label'], 'field_id':i, 'form':'person'};
					if( M.curBusiness.customers.settings['types-'+i+'-form'] != null && M.curBusiness.customers.settings['types-'+i+'-form'] != '' ) {
						this.add.formtabs.tabs[i].form = M.curBusiness.customers.settings['types-'+i+'-form'];
					}
				}
			}
			if( count == 0 ) {
				this.add.formtabs = null
				this.add.sections = this.add.forms.person;
			}
			if( M.curBusiness.customers.settings['use-birthdate'] == 'yes' ) {
				this.add.forms.person.name.fields.birthdate.active = 'yes';
			} else {
				this.add.forms.person.name.fields.birthdate.active = 'no';
			}
			if( M.curBusiness.customers.settings['use-cid'] == 'yes' ) {
				this.add.forms.person.name.fields.cid.active = 'yes';
				this.add.forms.business.business.fields.cid.active = 'yes';
			} else {
				this.add.forms.person.name.fields.cid.active = 'no';
				this.add.forms.business.business.fields.cid.active = 'no';
			}
		} else {
			this.add.formtabs = null;
			this.add.sections = this.add.forms.person;
			this.add.forms.person.info.fields.cid.active = 'no';
			this.add.forms.business.info.fields.cid.active = 'no';
		}

		this.showAdd(cb);
	}

	this.showAdd = function(cb) {
		this.add.refresh();
		this.add.show(cb);
	}

	this.save = function() {
		if( this.add.customer_id > 0 ) {
			// Update customer details
			var c = this.add.serializeFormSection('no', 'name')
				+ this.add.serializeFormSection('no', 'business')
				+ this.add.serializeFormSection('no', 'phones')
				+ this.add.serializeFormSection('no', '_notes');
			if( this.add.formtab_field_id != this.add.data.type ) {
				c += this.add.formtabs.field + '=' + encodeURIComponent(this.add.formtab_field_id) + '&';
			}
			if( c != '' ) {
				var rsp = M.api.postJSON('ciniki.customers.update', {'business_id':M.curBusinessID, 'customer_id':this.add.customer_id}, c);
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				} 
			}
		} else {
			// Add customer
			var c = this.add.serializeFormSection('yes', 'name')
				+ this.add.serializeFormSection('yes', 'business')
				+ this.add.serializeFormSection('yes', 'phones')
				+ this.add.serializeFormSection('yes', '_notes');
			if( this.add.formtab_field_id != this.add.data.type ) {
				c += this.add.formtabs.field + '=' + encodeURIComponent(this.add.formtab_field_id) + '&';
			}
			var rsp = M.api.postJSON('ciniki.customers.add', 
				{'business_id':M.curBusinessID}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			} 
			this.add.customer_id = rsp.id;
		}

		// Check for added/updated/deleted emails
		var sc = this.add.sectionCount('emails');
		for(i=0;i<sc;i++) {
			if( this.add.data['emails'][i]['email']['id'] == null || this.add.data['emails'][i]['email']['id'] == 0 ) {
				if( this.add.formFieldValue(this.add.sections.emails.fields.address, 'address_' + i) != '' ) {
					// Add email
					var c = this.add.serializeFormSection('yes', 'emails', i);
					var rsp = M.api.postJSON('ciniki.customers.emailAdd', 
						{'business_id':M.curBusinessID, 'customer_id':this.add.customer_id}, c);
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
				}
			} else {
				// Update email
				if( this.add.formFieldValue(this.add.sections.emails.fields.address, 'address_' + i) == '' ) {
					// Delete email if blank
					var rsp = M.api.postJSON('ciniki.customers.emailDelete', 
						{'business_id':M.curBusinessID, 'customer_id':this.add.customer_id, 'email_id':this.add.data['emails'][i]['email']['id']});
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
				} else {
					var c = this.add.serializeFormSection('no', 'emails', i);
					if( c != '' ) {
						var rsp = M.api.postJSON('ciniki.customers.emailUpdate', 
							{'business_id':M.curBusinessID, 'customer_id':this.add.customer_id, 'email_id':this.add.data['emails'][i]['email']['id']}, c);
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					}
				}
			}
		}

		// Check for added/updated/deleted addresses
		var sc = this.add.sectionCount('addresses');
		for(i=0;i<sc;i++) {
			if( this.add.data['addresses'][i]['address']['id'] == null || this.add.data['addresses'][i]['address']['id'] == 0 ) {
				if( this.add.formFieldValue(this.add.sections.addresses.fields.address1, 'address1_' + i) != '' 
					|| this.add.formFieldValue(this.add.sections.addresses.fields.address2, 'address2_' + i) != ''
					|| this.add.formFieldValue(this.add.sections.addresses.fields.city, 'city_' + i) != '' 
					|| this.add.formFieldValue(this.add.sections.addresses.fields.province, 'province_' + i) != ''
					|| this.add.formFieldValue(this.add.sections.addresses.fields.postal, 'postal_' + i) != ''
					|| this.add.formFieldValue(this.add.sections.addresses.fields.country, 'country_' + i) != '' ) {
					// Add address
					var c = this.add.serializeFormSection('yes', 'addresses', i);
					var rsp = M.api.postJSON('ciniki.customers.addressAdd', 
						{'business_id':M.curBusinessID, 'customer_id':this.add.customer_id}, c);
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
				}
			} else {
				// Update address
				if( this.add.formFieldValue(this.add.sections.addresses.fields.address1, 'address1_' + i) == '' 
					&& this.add.formFieldValue(this.add.sections.addresses.fields.address2, 'address2_' + i) == '' 
					&& this.add.formFieldValue(this.add.sections.addresses.fields.city, 'city_' + i) == '' 
					&& this.add.formFieldValue(this.add.sections.addresses.fields.province, 'province_' + i) == '' 
					&& this.add.formFieldValue(this.add.sections.addresses.fields.postal, 'postal_' + i) == '' ) {
					// Delete address if all fields except country are blank
					var rsp = M.api.postJSON('ciniki.customers.addressDelete', 
						{'business_id':M.curBusinessID, 'customer_id':this.add.customer_id, 'address_id':this.add.data['addresses'][i]['address']['id']});
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					} 
				} else {
					var c = this.add.serializeFormSection('no', 'addresses', i);
					if( c != '' ) {
						var rsp = M.api.postJSON('ciniki.customers.addressUpdate', 
							{'business_id':M.curBusinessID, 'customer_id':this.add.customer_id, 'address_id':this.add.data['addresses'][i]['address']['id']}, c);
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						} 
					}
				}
			}
		}

		// Check for added/updated/deleted addresses
		var sc = this.add.sectionCount('services');
		for(i=0;i<sc;i++) {
			if( this.add.formFieldValue(this.add.sections.services.fields.pstart_date, 'pstart_date_' + i) != '' ) {
				// Add service job
				var c = this.add.serializeFormSection('yes', 'services', i);
				var rsp = M.api.postJSON('ciniki.services.jobAdd', 
					{'business_id':M.curBusinessID, 'customer_id':this.add.customer_id, 'create_subscription':'yes'}, c);
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				} 
			}
		}

		// Everything works
		this.add.close();
	}
}
