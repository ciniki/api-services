//
// This file contains the UI to add and modify service subscriptions for jobs.
//
function ciniki_services_job() {

	//
	// Options for this module
	//
	this.serviceList = {};
	this.jobStatusOptions = {'10':'Entered', '20':'Started', '30':'Pending', '50':'Completed', '60':'Signed off', '61':'Skipped'};
//	this.statusOptions = {'10':'Active', '60':'Deleted'};

//	this.toggleOptions = {'off':'Off', 'on':'On'};
//	this.repeatOptions = {'10':'Daily', '20':'Weekly', '30':'Monthly','40':'Yearly'};
//	this.repeatIntervals = {'1':'1','2':'2','3':'3','4':'4','5':'5','6':'6','7':'7','8':'8'};
//	this.durationButtons = {'-30':'-30', '-15':'-15', '+15':'+15', '+30':'+30', '+2h':'+120'};
//	this.typeOptions = {'10':'Taxes'};
//	this.dueMonths = {'1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', 
//		'7':'7', '8':'8', '9':'9', '10':'10', '11':'11', '12':'12'};

	//
	// Initialize panels
	//
	this.init = function() {
		//
		// This panel will display the edit form for changing details about a job.
		//
		this.main = new M.panel('Job',
			'ciniki_services_job', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.services.job.edit');
		this.main.job_id = 0;
		this.main.sections = {
			'_info':{'label':'', 'fields':{
				'service_name':{'label':'Service', 'type':'noedit', 'history':'no'},
				'tracking_id':{'label':'Tracking ID', 'active':'no', 'type':'text'},
				'name':{'label':'Name', 'type':'text'},
				'status':{'label':'Status', 'type':'toggle', 'toggles':this.jobStatusOptions},
				'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees, 'history':'no'},
				}},
			'_dates':{'label':'', 'fields':{
				'pstart_date':{'label':'Period Start', 'type':'date'},
				'pend_date':{'label':'Period End', 'type':'date'},
//				'service_date':{'label':'Service Date', 'type':'date'},
				'date_scheduled':{'label':'Scheduled', 'type':'date'},
				'date_started':{'label':'Started', 'type':'date'},
				'date_due':{'label':'Due', 'type':'date'},
				'date_completed':{'label':'Completed', 'type':'date'},
				'date_signedoff':{'label':'Signed off', 'type':'date'},
				'efile_number':{'label':'eFile Number', 'type':'text'},
				}},
			'tasks':{'label':'Tasks', 'type':'simplegrid', 'num_cols':3,
				'headerValues':null,
				'cellClasses':['','',''],
				'noData':'No tasks assigned for this job',
				},
			'billing':{'label':'Billing', 'fields':{
				'invoice_amount':{'label':'Amount', 'type':'text', 'size':'small'},
				'tax1_amount':{'label':'HST', 'type':'text', 'size':'small'},
				}},
			'notes':{'label':'Notes', 'type':'simplethread', 'visible':'yes'},
			'_note':{'label':'Add note', 'fields':{
				'note':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'history':'no', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_services_job.saveJob();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_services_job.deleteJob();'},
				}},
		};
		this.main.sectionData = function(s) { 
			return this.data[s]; 
		};
		this.main.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.task.step;
				case 1: return d.task.name;
				case 2: return d.task.status_text;
			}
		};
//		this.main.threadSubject = function(s) { return null; return 'Notes'; }
		this.main.threadFollowupUser = function(s, i, d) { return d.note.user_display_name; }
		this.main.threadFollowupAge = function(s, i, d) { return d.note.age; }
		this.main.threadFollowupDateTime = function(s, i, d) { return d.note.date_added; }
		this.main.threadFollowupContent = function(s, i, d) { return d.note.content; }
		this.main.fieldValue = function(s, i, d) { return this.data[i]; }
		this.main.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.services.jobHistory','args':{'business_id':M.curBusinessID, 'job_id':this.job_id, 'field':i}};
		}
		this.main.addButton('save', 'Save', 'M.ciniki_services_job.saveJob();');
		this.main.addClose('Cancel');
		
		//
		// This panel will display the add job panel
		//
		this.add = new M.panel('Add Job',
			'ciniki_services_job', 'add',
			'mc', 'medium', 'sectioned', 'ciniki.services.job.edit');
		this.add.service_id = 0;
		this.add.customer_id = 0;
		this.add.default_data = {
			'name':'',
			'status':'10',
			'date_scheduled':'',
			'date_started':'',
			'date_due':'',
			'date_completed':'',
		};
		this.add.data = {};
		this.add.sections = {
			'_info':{'label':'', 'fields':{
				'tracking_id':{'label':'Tracking ID', 'active':'no', 'type':'text'},
				'name':{'label':'Name', 'type':'text'},
				'status':{'label':'Status', 'type':'toggle', 'toggles':this.jobStatusOptions},
				'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees, 'history':'no'},
				}},
			'_dates':{'label':'', 'fields':{
				'pstart_date':{'label':'Period Start', 'type':'date'},
				'pend_date':{'label':'Period End', 'type':'date'},
//				'service_date':{'label':'Service Date', 'type':'date'},
				'date_scheduled':{'label':'Scheduled', 'type':'date'},
				'date_started':{'label':'Started', 'type':'date'},
				'date_due':{'label':'Due', 'type':'date'},
				'date_completed':{'label':'Completed', 'type':'date'},
				'date_signedoff':{'label':'Signed off', 'type':'date'},
				'efile_number':{'label':'eFile Number', 'type':'text'},
			}},
			'billing':{'label':'Billing', 'fields':{
				'invoice_amount':{'label':'Amount', 'type':'text', 'size':'small'},
				'tax1_amount':{'label':'HST', 'type':'text', 'size':'small'},
				}},
			'_note':{'label':'Add note', 'fields':{
				'note':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'history':'no', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add', 'fn':'M.ciniki_services_job.addJob();'},
			}},
		};
		this.add.sectionData = function(s) { return this.data; }
		this.add.fieldValue = function(s, i, d) { return this.data[i]; }
		this.add.addClose('Cancel');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_services_job', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Setup forms based on business settings
		//
		if( M.curBusiness.services.settings['use-tracking-id'] == 'yes' ) {
			this.add.sections._info.fields.tracking_id.active = 'yes';
			this.main.sections._info.fields.tracking_id.active = 'yes';
		} else {
			this.add.sections._info.fields.tracking_id.active = 'no';
			this.main.sections._info.fields.tracking_id.active = 'no';
		}

		if( args.job_id != null && args.job_id > 0 ) {
			this.showJob(cb, args.job_id);
		} else if( args.service_id != null && args.service_id > 0 
			&& args.customer_id != null && args.customer_id > 0 ) {
			this.showAdd(cb, args.subscription_id, args.service_id, args.customer_id, args.name, args.pstart, args.pend, args.date_due);
		}
	}

	this.showJob = function(cb, jid) {
		this.main.data = {};
		if( jid != null ) { 
			this.main.job_id = jid;
		}
		var rsp = M.api.getJSON('ciniki.services.jobGet', {'business_id':M.curBusinessID, 'job_id':this.main.job_id, 'children':'yes'});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.main.data = rsp.job;
		if( rsp.job.notes.length > 0 ) {
			this.main.sections.notes.visible = 'yes';
		} else {
			this.main.sections.notes.visible = 'no';
		}
		if( rsp.job.tasks.length > 0 ) {
			this.main.sections.tasks.visible = 'yes';
		} else {
			this.main.sections.tasks.visible = 'no';
		}
		this.main.refresh();
		this.main.show(cb);
	};

	this.showAdd = function(cb, subid, sid, cid, name, pstart, pend, ddate) {
		this.add.reset();
		this.add.data = this.add.default_data;
		this.add.data.name = name;
		this.add.data.pstart_date = pstart;
		this.add.data.pend_date = pend;
		this.add.data.date_due = ddate;
		this.add.subscription_id = subid;
		this.add.service_id = sid;
		this.add.customer_id = cid;
		this.add.refresh();
		this.add.show(cb);
	};

	this.addJob = function() {
		var c = this.add.serializeForm('yes');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.jobAdd', {'business_id':M.curBusinessID, 
				'subscription_id':M.ciniki_services_job.add.subscription_id,
				'service_id':M.ciniki_services_job.add.service_id,
				'customer_id':M.ciniki_services_job.add.customer_id}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_job.add.close();
	};

	this.saveJob = function() {
		var c = this.main.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.jobUpdate', {'business_id':M.curBusinessID, 'job_id':this.main.job_id}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_job.main.close();
	};

	this.deleteJob = function() {
		if( confirm("Are you sure you want to remove this job?") ) {
			var rsp = M.api.getJSON('ciniki.services.jobDelete', 
				{'business_id':M.curBusinessID, 'job_id':this.main.job_id});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			this.main.close();
		}	
	};
}
