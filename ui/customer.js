//
// This file contains the UI to add and modify service subscriptions for customers.
//
function ciniki_services_customer() {

	//
	// Options for this module
	//
	this.serviceList = {};

	//
	// Initialize panels
	//
	this.init = function() {
		//
		// The main panel, which shows the menu for the services and reports available for an individual customer
		//
		this.main = new M.panel('Services',
			'ciniki_services_customer', 'main',
			'mc', 'narrow', 'sectioned', 'ciniki.services.customer.main');
		this.main.sections = {};
		this.main.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				return d.service.name;
			}
		};
		this.main.rowFn = function(s, i, d) {
			return 'M.ciniki_services_customer.showService(\'M.ciniki_services_customer.showMain();\',\'' + d.service.id + '\');';
		};
		this.main.sectionData = function(s) { return this.data[s]; }
		this.main.noData = function(s) { return 'No services configured'; }
		this.main.addClose('Back');

		//
		// The add panel for adding a new repeating service to a customer
		//
		this.add = new M.panel('Add Service',
			'ciniki_services_customer', 'add',
			'mc', 'medium', 'sectioned', 'ciniki.services.customer.add');
		this.add.customer_id = 0;
		this.add.data = {};
		this.add.sections = {
			'_info':{'label':'', 'fields':{
				'service_id':{'label':'Service', 'type':'select', 'options':this.serviceList},
				'date_started':{'label':'Started', 'type':'date'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add', 'fn':'M.ciniki_services_customer.addService();'},
			}},
		};
		this.add.sectionData = function(s) { return this.data[s]; }
		this.add.fieldValue = function(s, i, d) { return ''; }
		this.add.addClose('Cancel');

		//
		// The edit panel will display the form to edit the main details for a service
		//
		this.edit = new M.panel('Edit Service',
			'ciniki_services_customer', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.services.customer.edit');
		this.edit.data = {};
		this.edit.subscription_id = 0;
		this.edit.sections = {
			'_info':{'label':'', 'fields':{
				'service_name':{'label':'Service', 'type':'noedit', 'options':this.serviceList, 'history':'no'},
				'date_started':{'label':'Started', 'type':'date'},
				'date_ended':{'label':'Stop', 'type':'date'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_services_customer.saveService();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_services_customer.deleteSubscription();'},
			}},
		};
		this.edit.sectionData = function(s) { return this.data[s]; }
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.services.subscriptionHistory', 'args':{'business_id':M.curBusinessID, 
				'subscription_id':this.subscription_id, 'field':i}};
		}
		this.edit.addClose('Cancel');

		//
		// This panel will display the list of services for a customer, and all jobs associated with that service
		//
		this.subscription = new M.panel('Service',
			'ciniki_services_customer', 'subscription',
			'mc', 'medium', 'sectioned', 'ciniki.services.customer.subscription');
		this.subscription.subscription_id = 0;
		this.subscription.sections = {
			'_info':{'label':'', 'list':{
				'name':{'label':'Service'},
				'date_started':{'label':'Started'},
				'date_ended':{'label':'Ended', 'visible':'no'},
			}},
			'_jobs':{'label':'Jobs', 'type':'simplegrid', 'num_cols':'6', 'sortable':'yes',
				'headerValues':['Name','Start', 'End', 'Due', 'Completed','Signed Off'],
				'cellClasses':['multiline','multiline','multiline','multiline','multiline','multiline'],
				'sortTypes':['text','date','date','date'],
				'noData':'No jobs',
			},
		};
		this.subscription.sectionData = function(s) {
			if( s == '_info' ) { return this.sections._info.list; }
			if( s == '_jobs' ) { return this.data.jobs; }
		};
		this.subscription.listLabel = function(s, i, d) {
			if( s == '_info' ) { return d.label; }
		};
		this.subscription.listValue = function(s, i, d) {
			if( s == '_info' ) { return this.data[i]; }
		};
		this.subscription.cellValue = function(s, i, j, d) {
			switch (j) {
				case 0: return '<span class="maintext">' + d.job.name + '</span><span class="subtext">' + d.job.status_text + '</span>' ;
				case 1: return d.job.pstart_date.replace(/(...\s[0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
				case 2: return d.job.pend_date.replace(/(...\s[0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
				case 3: return d.job.date_due.replace(/(...\s[0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
				case 4: return d.job.date_completed.replace(/(...\s[0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
				case 5: return d.job.date_signedoff.replace(/(...\s[0-9]+),\s([0-9][0-9][0-9][0-9])/, "<span class='maintext'>$1<\/span><span class='subtext'>$2<\/span>");
			}
		};
		this.subscription.rowStyle = function(s, i, d) {
			if( s == '_jobs' && M.curBusiness.services.settings != null && M.curBusiness.services.settings['job-status-'+d.job.status+'-colour']) {
				return 'background:' + M.curBusiness.services.settings['job-status-'+d.job.status+'-colour'] +';';
			}
		};
		this.subscription.rowFn = function(s, i, d) {
			if( s == '_jobs' ) {
				if( d.job.id == 0 ) {
					return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_customer.showSubscription();\',\'mc\',{\'subscription_id\':M.ciniki_services_customer.subscription.data.id,\'service_id\':M.ciniki_services_customer.subscription.data.service_id,\'customer_id\':M.ciniki_services_customer.subscription.customer_id,\'name\':\'' + d.job.name + '\',\'pstart\':\'' + d.job.pstart_date + '\',\'pend\':\'' + d.job.pend_date + '\',\'date_due\':\'' + d.job.date_due + '\'});'
				} else {
					return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_customer.showSubscription();\',\'mc\',{\'job_id\':\'' + d.job.id + '\'});';
				}
			}
			return '';
		};
		this.subscription.addButton('edit', 'Edit', 'M.ciniki_services_customer.showEdit(\'M.ciniki_services_customer.showSubscription();\',M.ciniki_services_customer.subscription.subscription_id);');
		this.subscription.addClose('Close');

	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_services_customer', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Load services list
		//
		var rsp = M.api.getJSONCb('ciniki.services.servicesRepeating', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			M.ciniki_services_customer.serviceList = {};
			for(i in rsp.services ) {
				M.ciniki_services_customer.serviceList[rsp.services[i].service.id] = rsp.services[i].service.name;
			}

			if( args.subscription_id != null && args.subscription_id > 0
				&& args.customer_id != null && args.customer_id > 0 ) {
				M.ciniki_services_customer.showSubscription(cb, args.customer_id, args.subscription_id);
			} else if( args.customer_id != null && args.customer_id > 0 ) {
				M.ciniki_services_customer.showAddSubscription(cb, args.customer_id);
			} else {
				M.ciniki_services_customer.showMain(cb);
			}
		});
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMain = function(cb) {
		this.main.reset();
		var rsp = M.api.getJSONCb('ciniki.services.customerSubscriptions', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_services_customer.main;
			p.data = {};
			p.sections = {};
			if( rsp.categories.length == 0 ) {
				p.data._nodata = {};
				p.sections._nodata = {'label':'',
					'num_cols':1, 'type':'simplegrid', 'headerValues':null,
					'cellClasses':[''],
					'noData':['No services configured'],
				};
			} else {
				for(i in rsp.categories) {
					p.data[rsp.categories[i].category.name + ' '] = rsp.categories[i].category.services;
					p.sections[rsp.categories[i].category.name + ' '] = {'label':rsp.categories[i].category.name,
						'num_cols':1, 'type':'simplegrid', 'headerValues':null,
						'cellClasses':[''],
						'noData':['No services configured'],
					};
				}
			}
			p.refresh();
			p.show(cb);
		});
	}

	this.showAddSubscription = function(cb, cid) {
		this.add.reset();
		this.add.data = {};
		this.add.customer_id = cid;
		this.add.data = this.add.default_data;
		this.add.sections._info.fields.service_id.options = this.serviceList;
		this.add.refresh();
		this.add.show(cb);
	};

	this.showEdit = function(cb, sid) {
		this.edit.reset();
		if( sid != null ) {
			this.edit.subscription_id = sid;
		}
		this.edit.data = {};
		var rsp = M.api.getJSONCb('ciniki.services.subscriptionGet', {'business_id':M.curBusinessID,
			'subscription_id':this.edit.subscription_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_services_customer.edit.data = rsp.subscription;
				M.ciniki_services_customer.edit.refresh();
				M.ciniki_services_customer.edit.show(cb);
			});
	};

	this.addService = function() {
		var c = this.add.serializeForm('yes');
		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.services.subscriptionAdd', {'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_services_customer.add.customer_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_services_customer.add.close();
				});
		} else {
			this.add.close();
		}
	};

	this.saveService = function() {
		var c = this.edit.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.services.subscriptionUpdate', 
				{'business_id':M.curBusinessID, 'subscription_id':M.ciniki_services_customer.edit.subscription_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_services_customer.edit.close();
				});
		} else {
			M.ciniki_services_customer.edit.close();
		}
	};

	this.showSubscription = function(cb, cid, sid) {
		if( cid != null ) {
			this.subscription.customer_id = cid;
		}
		if( sid != null ) {
			this.subscription.subscription_id = sid;
		}
		this.subscription.data = {};
		var rsp = M.api.getJSONCb('ciniki.services.customerSubscriptions', {'business_id':M.curBusinessID,
			'jobs':'yes', 'jobsort':'DESC', 'projections':'P1Y', 
			'customer_id':this.subscription.customer_id, 'subscription_id':this.subscription.subscription_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_services_customer.subscription;
				p.data = rsp.subscriptions[0].subscription;
				if( rsp.subscriptions[0].subscription.date_ended != null && rsp.subscriptions[0].subscription.date_ended != '' ) {
					p.sections._info.list.date_ended.visible = 'yes';
				} else {
					p.sections._info.list.date_ended.visible = 'no';
				}

				p.refresh();
				p.show(cb);
			});
	};

	this.deleteSubscription = function() {
		if( confirm("Are you sure you want to remove this subscription?") ) {
			var rsp = M.api.getJSON('ciniki.services.subscriptionDelete', 
				{'business_id':M.curBusinessID, 'subscription_id':this.edit.subscription_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_services_customer.subscription.close();
				});
		}	
	};
}
