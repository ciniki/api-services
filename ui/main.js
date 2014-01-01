//
// This file contains the reports for the services module
//
function ciniki_services_main() {

	//
	// Options for this module
	//
	this.serviceList = {};
	this.months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
//	this.jobStatusOptions = {'10':'Scheduled', '20':'Started', '30':'Pending', '40':'Working', '60':'Completed', '61':'Skipped'};
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
		// The menu panel displays the main menu for the services module
		//
		this.menu = new M.panel('Services',
			'ciniki_services_main', 'menu',
			'mc', 'narrow', 'sectioned', 'ciniki.services.main.menu');
		this.menu.jobtype = 'pastdue';
		this.menu.sections = {
			'_menu':{'label':'', 'list':{
				'schedule':{'label':'Schedule', 'fn':'M.ciniki_services_main.showStats(\'M.ciniki_services_main.showMenu();\');'},
			}},
			'_reports':{'label':'Reports', 'list':{
				'missing':{'label':'Missing', 'fn':'M.ciniki_services_main.showJobs(\'M.ciniki_services_main.showMenu();\',\'missing\');'},
				'pastdue':{'label':'Past Due', 'fn':'M.ciniki_services_main.showJobs(\'M.ciniki_services_main.showMenu();\',\'pastdue\');'},
				'entered':{'label':'Entered', 'fn':'M.ciniki_services_main.showJobs(\'M.ciniki_services_main.showMenu();\',\'entered\');'},
				'inprogress':{'label':'In Progress', 'fn':'M.ciniki_services_main.showJobs(\'M.ciniki_services_main.showMenu();\',\'inprogress\');'},
				'signoff':{'label':'Require Sign Off', 'fn':'M.ciniki_services_main.showJobs(\'M.ciniki_services_main.showMenu();\',\'needsignoff\');'},
				'assigned':{'label':'Assignments', 'fn':'M.ciniki_services_main.showAssignments(\'M.ciniki_services_main.showMenu();\',\'' + M.userID + '\');'},
				'tracking':{'label':'Tracking ID', 'fn':'M.ciniki_services_main.showTracking(\'M.ciniki_services_main.showMenu();\',\'2012\');'},
			}},
		};
		this.menu.addButton('add', 'Add', 'M.startApp(\'ciniki.services.quickadd\',null,\'M.ciniki_services_main.showMenu();\');');
		this.menu.addClose('Close');

		//
		// The stats panel, shows the status for the upcoming 12 months
		//
		this.stats = new M.panel('Stats',
			'ciniki_services_main', 'stats',
			'mc', 'mediumflex', 'sectioned', 'ciniki.services.main.stats');
		this.stats.service_id = null;
		this.stats.year = null;
		this.stats.month = null;
		this.stats.sections = {
			'_stats':{'label':'', 'type':'simplegrid', 'num_cols':13,
				'headerValues':['year','mon','mon','mon','mon','mon','mon','mon','mon','mon','mon','mon','mon'],
				'cellClasses':['','','','','','','','','','','','',''],
				'noData':'No services defined',
				},
			'_jobs':{'label':'Jobs', 'visible':'no', 'type':'simplegrid', 'num_cols':4, 'class':'simplegrid services border', 
				'headerValues':['Customer', 'Name', 'Period', 'Due'],
				'cellClasses':['multiline', 'multiline jobs', 'multiline', ''],
				'noData':'No jobs found',
				},
		};
		this.stats.cellClass = function(s, i, j, d) {
			if( s == '_stats' ) {
				if( j == 0 ) { return 'border'; }
				if( j > 0 ) { return 'aligncenter border'; }
			}
			return this.sections[s].cellClasses[j];
		};
		this.stats.cellValue = function(s, i, j, d) {
			if( s == '_stats' ) {
				if( j == 0 ) { return d.service.name; }
				var m = d.service.months[(j-1)].month;
				if( m.total_jobs == 0 ) { return ''; }
				var c = 0;
				if( m.jobs_completed != null ) { c += parseInt(m.jobs_completed); }
				if( m.jobs_completed != null ) { c += parseInt(m.jobs_skipped); }
				//return c + '/' + m.total_jobs;
				// return '<span class="maintext">' + m.total_jobs + '</span> <span class="subtext">(' + c + ')</span>';
				// return '<span class="subdue">(' + c + ')</span>' + m.total_jobs;
				return m.total_jobs;
			}
			else if( s == '_jobs' ) {
				if( this.sections[s].num_cols-j == 1 ) { return d.job.date_due; }
				if( this.sections[s].num_cols-j <= 2 ) { return '<span class="maintext">' + d.job.pstart_date + '</span><span class="subtext">' + d.job.pend_date + '</span>'; }
				if( this.sections[s].num_cols-j <= 3 ) { 
					return '<span class="maintext">' + d.job.name + '</span><span class="subtext">' + d.job.status_text + '</span>'; 
				}
				if( this.sections[s].num_cols-j <= 4 ) { 
					return '<span class="maintext">' + d.job.customer_name + '</span><span class="subtext">' + d.job.subscription_date_started + '</span>'; 
				}
				if( this.sections[s].num_cols-j <= 5 ) {
					return '<span class="maintext">' + d.job.tracking_id + '</span><span class="subtext">' + d.job.assigned_names + '</span>';
				}
				if( (j == 0 && this.sections[s].num_cols == 4) || (j == 1 && this.sections[s].num_cols == 5) ) {
					return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_services_main.showJobs();\',\'mc\',{\'customer_id\':\'' + d.job.customer_id + '\'});';
				}
			}
		};
		this.stats.rowStyle = function(s, i, d) {
			if( s == '_jobs' && M.curBusiness.services.settings != null && M.curBusiness.services.settings['job-status-'+d.job.status+'-colour']) {
				return 'background:' + M.curBusiness.services.settings['job-status-'+d.job.status+'-colour'] +';';
			}
		};
		this.stats.cellFn = function(s, i, j, d) {
			if( s == '_stats' ) {
				if( j > 0 && d.service.months[(j-1)].month.total_jobs > 0 ) {
					return 'event.stopPropagation();M.ciniki_services_main.showMonthJobs(null,\'' + d.service.id + '\',\'' + d.service.months[(j-1)].month.year + '\',\'' + d.service.months[(j-1)].month.name + '\');';
				}
			}
			if( s == '_jobs' ) {
				if( (j == 0 && this.sections[s].num_cols == 4) || (j == 1 && this.sections[s].num_cols == 5) ) {
					return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_services_main.showStats();\',\'mc\',{\'customer_id\':\'' + d.job.customer_id + '\'});';
				}
				if( j > 1 || (j == 1 && this.sections[s].num_cols == 4) || (j == 0 && this.sections[s].num_cols == 5) ) {
					if( d.job.id == 0 ) {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showStats();\',\'mc\',{\'subscription_id\':\'' + d.job.subscription_id + '\',\'service_id\':\'' + d.job.service_id + '\',\'customer_id\':\'' + d.job.customer_id + '\',\'name\':\'' + d.job.name + '\',\'pstart\':\'' + d.job.pstart_date + '\',\'pend\':\'' + d.job.pend_date + '\',\'date_due\':\'' + d.job.date_due + '\'});'
					} else {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showStats();\',\'mc\',{\'job_id\':\'' + d.job.id + '\'});';
					}
				}				
			}
			return null;
		};
		this.stats.sectionData = function(s) { 
			if( s == '_stats' ) { return this.data; }
			if( s == '_jobs' ) { return this.jobs; }
		};
		this.stats.noData = function(s) { return 'No services configured'; }
		this.stats.addClose('Back');

		//
		// The jobs panel will list jobs based on search criteria
		//
		this.jobs = new M.panel('Jobs',
			'ciniki_services_main', 'jobs',
			'mc', 'medium', 'sectioned', 'ciniki.services.main.jobs');
		this.jobs.sections = {
			'tabs':{'label':'', 'type':'paneltabs', 'selected':this.jobs.jobtype, 'tabs':{
				'missing':{'label':'Missing', 'fn':'M.ciniki_services_main.showJobs(null,\'missing\');'},
				'pastdue':{'label':'Past due', 'fn':'M.ciniki_services_main.showJobs(null,\'pastdue\');'},
				'entered':{'label':'Entered', 'fn':'M.ciniki_services_main.showJobs(null,\'entered\');'},
				'inprogress':{'label':'In Progress', 'fn':'M.ciniki_services_main.showJobs(null,\'inprogress\');'},
				'needsignoff':{'label':'Need Sign Off', 'fn':'M.ciniki_services_main.showJobs(null,\'needsignoff\');'},
			}},
			'_jobs':{'label':'', 'type':'simplegrid', 'num_cols':4, 'class':'simplegrid services border', 'sortable':'yes',
				'headerValues':['Customer', 'Name', 'Period', 'Due'],
				'cellClasses':['multiline', 'multiline jobs', 'multiline', ''],
				'sortTypes':['text','text','date','date'],
				'noData':'No jobs found',
				},
		};
		this.jobs.cellClass = function(s, i, j, d) {
			return this.sections[s].cellClasses[j];
		};
		this.jobs.rowStyle = function(s, i, d) {
			if( s == '_jobs' && M.curBusiness.services.settings != null && M.curBusiness.services.settings['job-status-'+d.job.status+'-colour']) {
				return 'background:' + M.curBusiness.services.settings['job-status-'+d.job.status+'-colour'] +';';
			}
		};
		this.jobs.cellValue = function(s, i, j, d) {
			if( s == '_jobs' ) {
				if( this.sections[s].num_cols-j == 1 ) { return d.job.date_due; }
				if( this.sections[s].num_cols-j <= 2 ) { return '<span class="maintext">' + d.job.pstart_date + '</span><span class="subtext">' + d.job.pend_date + '</span>'; }
				if( this.sections[s].num_cols-j <= 3 ) { 
					return '<span class="maintext">' + d.job.name + '</span><span class="subtext">' + d.job.status_text + '</span>'; 
				}
				if( this.sections[s].num_cols-j <= 4 ) { 
					return '<span class="maintext">' + d.job.customer_name + '</span><span class="subtext">' + d.job.service_name + '</span>'; 
				}
				if( this.sections[s].num_cols-j <= 5 ) {
					// return d.job.tracking_id;
					if( d.job.assigned_names != null && d.job.tracking_id != null ) {
						return '<span class="maintext">' + d.job.tracking_id + '</span><span class="subtext">' + d.job.assigned_names + '</span>';
					} else {
						return '<span class="maintext"></span><span class="subtext"></span>';
					}
				}
			}
		};
		this.jobs.cellFn = function(s, i, j, d) {
			if( s == '_jobs' ) {
				if( (j == 0 && this.sections[s].num_cols == 4) || (j == 1 && this.sections[s].num_cols == 5) ) {
					return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_services_main.showJobs();\',\'mc\',{\'customer_id\':\'' + d.job.customer_id + '\'});';
				}
				if( j > 1 || (j == 1 && this.sections[s].num_cols == 4) || (j == 0 && this.sections[s].num_cols == 5) ) {
					if( d.job.id == 0 ) {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showJobs();\',\'mc\',{\'subscription_id\':\'' + d.job.subscription_id + '\',\'service_id\':\'' + d.job.service_id + '\',\'customer_id\':\'' + d.job.customer_id + '\',\'name\':\'' + d.job.name + '\',\'pstart\':\'' + d.job.pstart_date + '\',\'pend\':\'' + d.job.pend_date + '\',\'date_due\':\'' + d.job.date_due + '\'});'
					} else {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showJobs();\',\'mc\',{\'job_id\':\'' + d.job.id + '\'});';
					}
				}				
			}
			return null;
		};
		this.jobs.sectionData = function(s) { 
			if( s == '_jobs' ) { return this.data; }
		};
		this.jobs.noData = function(s) { return 'No jobs found'; }
		this.jobs.addClose('Back');

		//
		// The jobs panel will list jobs based on search criteria
		//
		this.assignments = new M.panel('Assignments',
			'ciniki_services_main', 'assignments',
			'mc', 'medium', 'sectioned', 'ciniki.services.main.assignments');
		this.assignments.sections = {
			'tabs':{'label':'', 'type':'paneltabs', 'selected':this.assignments.user_id, 'tabs':{}},
			'_jobs':{'label':'', 'type':'simplegrid', 'num_cols':4, 'class':'simplegrid services border', 'sortable':'yes',
				'headerValues':['Customer', 'Name', 'Period', 'Due'],
				'cellClasses':['multiline', 'multiline jobs', 'multiline', ''],
				'sortTypes':['text','text','date','date'],
				'noData':'No jobs found',
				},
		};
		this.assignments.cellClass = this.jobs.cellClass;
		this.assignments.rowStyle = this.jobs.rowStyle;
		this.assignments.cellValue = this.jobs.cellValue;
		this.assignments.cellFn = function(s, i, j, d) {
			if( s == '_jobs' ) {
				if( (j == 0 && this.sections[s].num_cols == 4) || (j == 1 && this.sections[s].num_cols == 5) ) {
					return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_services_main.showAssignments();\',\'mc\',{\'customer_id\':\'' + d.job.customer_id + '\'});';
				}
				if( j > 1 || (j == 1 && this.sections[s].num_cols == 4) || (j == 0 && this.sections[s].num_cols == 5) ) {
					if( d.job.id == 0 ) {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showAssignments();\',\'mc\',{\'subscription_id\':\'' + d.job.subscription_id + '\',\'service_id\':\'' + d.job.service_id + '\',\'customer_id\':\'' + d.job.customer_id + '\',\'name\':\'' + d.job.name + '\',\'pstart\':\'' + d.job.pstart_date + '\',\'pend\':\'' + d.job.pend_date + '\',\'date_due\':\'' + d.job.date_due + '\'});'
					} else {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showAssignments();\',\'mc\',{\'job_id\':\'' + d.job.id + '\'});';
					}
				}				
			}
			return null;
		};
		this.assignments.sectionData = this.jobs.sectionData;
		this.assignments.noData = function(s) { return 'No jobs found'; }
		this.assignments.addClose('Back');

		//
		// The jobs listed by tracking ID
		//
		this.tracking = new M.panel('Tracking',
			'ciniki_services_main', 'tracking',
			'mc', 'medium', 'sectioned', 'ciniki.services.main.assignments');
		this.tracking.sections = {
//			'tabs':{'label':'', 'type':'paneltabs', 'selected':this.assignments.tracking_id, 'tabs':{
//				'2012':{'label':'2012', 'fn':'M.ciniki_services_main.showTracking(null,\'2012\');'},
//				}},
			'_jobs':{'label':'', 'type':'simplegrid', 'num_cols':4, 'class':'simplegrid services border', 'sortable':'yes',
				'headerValues':['Customer', 'Name', 'Period', 'Due'],
				'cellClasses':['multiline', 'multiline jobs', 'multiline', ''],
				'sortTypes':['text','text','date','date'],
				'noData':'No jobs found',
				},
		};
		this.tracking.cellClass = this.jobs.cellClass;
		this.tracking.rowStyle = this.jobs.rowStyle;
		this.tracking.cellValue = this.jobs.cellValue;
		this.tracking.cellFn = function(s, i, j, d) {
			if( s == '_jobs' ) {
				if( (j == 0 && this.sections[s].num_cols == 4) || (j == 1 && this.sections[s].num_cols == 5) ) {
					return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_services_main.showTracking();\',\'mc\',{\'customer_id\':\'' + d.job.customer_id + '\'});';
				}
				if( j > 1 || (j == 1 && this.sections[s].num_cols == 4) || (j == 0 && this.sections[s].num_cols == 5) ) {
					if( d.job.id == 0 ) {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showTracking();\',\'mc\',{\'subscription_id\':\'' + d.job.subscription_id + '\',\'service_id\':\'' + d.job.service_id + '\',\'customer_id\':\'' + d.job.customer_id + '\',\'name\':\'' + d.job.name + '\',\'pstart\':\'' + d.job.pstart_date + '\',\'pend\':\'' + d.job.pend_date + '\',\'date_due\':\'' + d.job.date_due + '\'});'
					} else {
						return 'M.startApp(\'ciniki.services.job\',null,\'M.ciniki_services_main.showTracking();\',\'mc\',{\'job_id\':\'' + d.job.id + '\'});';
					}
				}				
			}
			return null;
		};
		this.tracking.sectionData = this.jobs.sectionData;
		this.tracking.noData = function(s) { return 'No jobs found'; }
		this.tracking.addClose('Back');

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
		var appContainer = M.createContainer(appPrefix, 'ciniki_services_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.stats.start_date = null;
		this.stats.end_date = null;

		if( M.curBusiness.services.settings != null && M.curBusiness.services.settings['use-tracking-id'] != null 
			&& M.curBusiness.services.settings['use-tracking-id'] == 'yes') {
			this.stats.sections._jobs.num_cols = 5;
			this.stats.sections._jobs.headerValues = ['Tracking', 'Customer', 'Name', 'Period', 'Due'];
			this.stats.sections._jobs.cellClasses = ['multiline', 'multiline', 'multiline jobs', 'multiline', ''];
			this.stats.sections._jobs.sortTypes = ['text', 'text','text','date','date'];
			this.jobs.sections._jobs.num_cols = 5;
			this.jobs.sections._jobs.headerValues = ['Tracking', 'Customer', 'Name', 'Period', 'Due'];
			this.jobs.sections._jobs.cellClasses = ['multiline', 'multiline', 'multiline jobs', 'multiline', ''];
			this.jobs.sections._jobs.sortTypes = ['text', 'text','text','date','date'];
			this.assignments.sections._jobs = this.jobs.sections._jobs;
			this.tracking.sections._jobs = this.jobs.sections._jobs;
		} else {
			this.stats.sections._jobs.num_cols = 4;
			this.stats.sections._jobs.headerValues = ['Customer', 'Name', 'Period', 'Due'];
			this.stats.sections._jobs.cellClasses = ['multiline', 'multiline jobs', 'multiline', ''];
			this.stats.sections._jobs.sortTypes = ['text','text','date','date'];
			this.jobs.sections._jobs.num_cols = 4;
			this.jobs.sections._jobs.headerValues = ['Customer', 'Name', 'Period', 'Due'];
			this.jobs.sections._jobs.cellClasses = ['multiline', 'multiline jobs', 'multiline', ''];
			this.jobs.sections._jobs.sortTypes = ['text','text','date','date'];
			this.assignments.sections._jobs = this.jobs.sections._jobs;
			this.tracking.sections._jobs = this.jobs.sections._jobs;
		}

		//
		// Setup assignments tabs for company employees
		//
		this.assignments.sections.tabs.tabs = {};
		for(i in M.curBusiness.employees) {
			this.assignments.sections.tabs.tabs[i] = {'label':M.curBusiness.employees[i], 'fn':'M.ciniki_services_main.showAssignments(null,\'' + i + '\');'};
		}

		this.showMenu(cb);
//		this.showStats(cb);
	}

	this.showMenu = function(cb) {
		this.menu.refresh();
		this.menu.show(cb);
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showStats = function(cb) {
		this.stats.reset();

		if( this.stats.start_date == null ) {
			var sdate = new Date();
			sdate.setDate(1);
			var edate = new Date(sdate.getFullYear()+1, sdate.getMonth(), sdate.getDate(), 0, 0, 0, 0);
			edate.setTime(edate.getTime()-86400);
			this.stats.start_date = M.dateFormat(sdate);
			this.stats.end_date = M.dateFormat(edate);
		}
		var rsp = M.api.getJSON('ciniki.services.serviceStats', {'business_id':M.curBusinessID, 
			'start_date':this.stats.start_date, 'end_date':this.stats.end_date});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.stats.sections._stats.num_cols = 1;
		if( rsp.services != null && rsp.services[0] != null ) {
			this.stats.sections._stats.headerValues[0] = rsp.services[0].service.months[0].month.year;
			for(i in rsp.services[0].service.months) {
				this.stats.sections._stats.headerValues[(parseInt(i)+1)] = rsp.services[0].service.months[i].month.name;
				this.stats.sections._stats.num_cols++;
			}
		}
		this.stats.data = rsp.services;
		this.showMonthJobs(cb);
	}

	this.showMonthJobs = function(cb, sid, year, month) {
		if( sid != null && year != null && month != null ) {
			this.stats.service_id = sid;
			this.stats.jobs_year = year;
			this.stats.jobs_month = M.ciniki_services_main.months.indexOf(month) + 1;
		}
		if( this.stats.service_id != null && this.stats.service_id > 0 ) {
			this.stats.jobs = {};
			var rsp = M.api.getJSON('ciniki.services.serviceJobs', {'business_id':M.curBusinessID, 
				'service_id':this.stats.service_id, 'year':this.stats.jobs_year, 'month':this.stats.jobs_month});
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			this.stats.jobs = rsp.jobs;
			this.stats.sections._jobs.visible = 'yes';
			// Setup the section name
			this.stats.sections._jobs.label = 'Jobs';
			for(i in this.stats.data) {
				if( this.stats.data[i].service.id == this.stats.service_id ) {
					this.stats.sections._jobs.label = this.stats.data[i].service.name + ' - ' + M.ciniki_services_main.months[this.stats.jobs_month-1] + ' ' + this.stats.jobs_year;
					break;
				}
			}
		} else {
			this.stats.sections._jobs.visible = 'no';
		}
		this.stats.refresh();
		this.stats.show(cb);
	};

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
		var rsp = M.api.getJSON('ciniki.services.subscriptionGet', {'business_id':M.curBusinessID,
			'subscription_id':this.edit.subscription_id});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.edit.data = rsp.subscription;
		
		this.edit.refresh();
		this.edit.show(cb);
	};

	this.addService = function() {
		var c = this.add.serializeForm('yes');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.subscriptionAdd', {'business_id':M.curBusinessID, 
				'customer_id':M.ciniki_services_customer.add.customer_id}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_customer.add.close();
	};

	this.saveService = function() {
		var c = this.edit.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.subscriptionUpdate', 
				{'business_id':M.curBusinessID, 'subscription_id':M.ciniki_services_customer.edit.subscription_id}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_customer.edit.close();
	};

	this.showSubscription = function(cb, cid, sid) {
		if( cid != null ) {
			this.subscription.customer_id = cid;
		}
		if( sid != null ) {
			this.subscription.subscription_id = sid;
		}
		this.subscription.data = {};
		var rsp = M.api.getJSON('ciniki.services.customerSubscriptions', {'business_id':M.curBusinessID,
			'jobs':'yes', 'jobsort':'DESC', 'projections':'P1Y', 
			'customer_id':this.subscription.customer_id, 'subscription_id':this.subscription.subscription_id});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.subscription.data = rsp.subscriptions[0].service;
		if( rsp.subscriptions[0].service.date_ended != null && rsp.subscriptions[0].service.date_ended != '' ) {
			this.subscription.sections._info.list.date_ended.visible = 'yes';
		} else {
			this.subscription.sections._info.list.date_ended.visible = 'no';
		}

		this.subscription.refresh();
		this.subscription.show(cb);
	};

	this.showJobs = function(cb, jobtype) {
		this.jobs.data = {};
		if( jobtype != null ) {
			this.jobs.jobtype = jobtype;
			this.jobs.sections.tabs.selected = jobtype;
		}
		if( this.jobs.jobtype == null ) { alert('Unspecified job type'); return false; }
		if( this.jobs.jobtype == 'missing' ) {
			var rsp = M.api.getJSON('ciniki.services.jobsMissing', {'business_id':M.curBusinessID});
		} else if( this.jobs.jobtype == 'pastdue' ) {
			var rsp = M.api.getJSON('ciniki.services.jobsList', {'business_id':M.curBusinessID, 'type':'pastdue'});
		} else if( this.jobs.jobtype == 'entered' ) {
			var rsp = M.api.getJSON('ciniki.services.jobsList', {'business_id':M.curBusinessID, 'status_list':'10'});
		} else if( this.jobs.jobtype == 'inprogress' ) {
			var rsp = M.api.getJSON('ciniki.services.jobsList', {'business_id':M.curBusinessID, 'status_list':'20,30,40'});
		} else if( this.jobs.jobtype == 'needsignoff' ) {
			var rsp = M.api.getJSON('ciniki.services.jobsList', {'business_id':M.curBusinessID, 'status_list':'50'});
		} else {
			alert('Invalid request');
			return false;
		}
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.jobs.data = rsp.jobs;

		this.jobs.refresh();
		this.jobs.show(cb);
	};

	this.showAssignments = function(cb, uid) {
		this.assignments.data = {};
		if( uid != null ) {
			this.assignments.user_id = uid;
			this.assignments.sections.tabs.selected = uid;
		}
		if( this.assignments.user_id == null ) { alert('Unspecified job type'); return false; }
		var rsp = M.api.getJSON('ciniki.services.jobsList', {'business_id':M.curBusinessID, 'assigned_id':this.assignments.user_id});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.assignments.data = rsp.jobs;

		this.assignments.refresh();
		this.assignments.show(cb);
	};

	this.showTracking = function(cb, tracking_id_start) {
		this.tracking.data = {};
		if( tracking_id_start != null ) {
			this.tracking.tracking_id_start = tracking_id_start;
//			this.tracking.sections.tabs.selected = tracking_id_start;
		}
		if( this.tracking.tracking_id_start == null ) { alert('Unspecified job type'); return false; }
		var rsp = M.api.getJSON('ciniki.services.jobsList', {'business_id':M.curBusinessID, 'tracking_id_start':this.tracking.tracking_id_start});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.tracking.data = rsp.jobs;

		this.tracking.refresh();
		this.tracking.show(cb);
	};

}
