//
// This file contains the UI code to setup the services for a business. 
//
function ciniki_services_settings() {
	//
	// Panels
	//
	this.main = null;
	this.add = null;

	this.cb = null;
	this.toggleOptions = {'no':'Off', 'yes':'On'};
	//this.repeatOptions = {'10':'Daily', '20':'Weekly', '30':'Monthly','40':'Yearly'};
	this.repeatOptions = {'30':'Monthly','40':'Yearly'};
	this.repeatIntervals = {'1':'1','2':'2','3':'3','4':'4','5':'5','6':'6','7':'7','8':'8'};
	this.durationButtons = {'-30':'-30', '-15':'-15', '+15':'+15', '+30':'+30', '+2h':'+120'};
	this.typeOptions = {'10':'Taxes'};
	this.dueMonths = {'1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', 
		'7':'7', '8':'8', '9':'9', '10':'10', '11':'11', '12':'12'};

	this.init = function() {
		//
		// The main panel, which lists the options for production
		//
		this.main = new M.panel('Settings',
			'ciniki_services_settings', 'main',
			'mc', 'narrow', 'sectioned', 'ciniki.services.settings.main');
		this.main.sections = {};
		this.main.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				return d.service.name;
			}
		};
		this.main.rowFn = function(s, i, d) {
			return 'M.ciniki_services_settings.showService(\'M.ciniki_services_settings.showMain();\',\'' + d.service.id + '\');';
		};
		this.main.sectionData = function(s) { 
			if( s == '_settings' ) { return this.sections._settings.buttons; }
			return this.data[s]; 
		};
		this.main.noData = function(s) { return 'No services configured'; }
		this.main.addButton('add', 'Add', 'M.ciniki_services_settings.showAdd(\'M.ciniki_services_settings.showMain();\',\'\');');
		this.main.addClose('Back');

		//
		// The add panel for creating a new service
		//
		this.add = new M.panel('Add Service',
			'ciniki_services_settings', 'add',
			'mc', 'medium', 'sectioned', 'ciniki.services.settings.add');
		this.add.default_data = {
			'type':'10',
			'name':'',
			'category':'',
			'status':'10',
			'repeat_type':'0',
			'repeat_interval':'1',
			'due_after_days':'0',
			'due_after_months':'0',
		};
		this.add.data = {};
		this.add.sections = {
			'_info':{'label':'', 'fields':{
				'type':{'label':'Type', 'type':'toggle', 'toggles':this.typeOptions},
				'name':{'label':'Name', 'type':'text'},
				'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
//				'duration':{},
				'repeat_type':{'label':'Repeat', 'type':'multitoggle', 'none':'yes', 'toggles':this.repeatOptions,
					'fn':'M.ciniki_services_settings.add.updateInterval'},
				'repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals, 'hint':' '},
//				'repeat_end':{},
			}},
			'_due':{'label':'Due After', 'fields':{
				'due_after_days':{'label':'Days', 'type':'integer'},
				'due_after_months':{'label':'Months', 'type':'integer'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add', 'fn':'M.ciniki_services_settings.addService();'},
			}},
		};
		this.add.updateInterval = function(i, t, v) {
			if( i == 'repeat_type' && v == 'toggle_on' ) {
				if( t == 'Daily' ) { M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'days'; }
				if( t == 'Monthly' ) { M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'months'; }
				if( t == 'Quarterly' ) { M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'quarters'; }
				if( t == 'Yearly' ) { M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = 'years'; }
			} else if( i == 'repeat_type' && v == 'toggle_off' ) {
				M.gE(this.panelUID + '_repeat_interval_hint').innerHTML = '';
			}
		};
		this.add.liveSearchCb = function(s, i, value) {
			if( i == 'category' ) {
				var rsp = M.api.getJSONBgCb('ciniki.services.serviceSearchCategory', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':5},
					function(rsp) {
						M.ciniki_services_settings.add.liveSearchShow(s, i, M.gE(M.ciniki_services_settings.add.panelUID + '_' + i), rsp.categories);
					});
			}
		};
		this.add.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'category' && d.category != null ) { return d.category.name; }
			return '';
		};
		this.add.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'category' && d.category != null ) {
				return 'M.ciniki_services_settings.add.updateCategory(\'' + s + '\',\'' + escape(d.category.name) + '\');';
			}
		};
		this.add.updateCategory = function(s, category) {
			M.gE(this.panelUID + '_category').value = unescape(category);
			this.removeLiveSearch(s, 'category');
		};
		this.add.sectionData = function(s) { return this.data[s]; }
		this.add.fieldValue = function(s, i, d) { return this.data[i]; }
		this.add.addClose('Cancel');

		//
		// The edit panel will display the form to edit the main details for a service
		//
		this.edit = new M.panel('Edit Service',
			'ciniki_services_settings', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.services.settings.edit');
		this.edit.data = {};
		this.edit.service_id = 0;
		this.edit.sections = {
			'_info':{'label':'', 'fields':{
				'type':{'label':'Type', 'type':'toggle', 'toggles':this.typeOptions},
				'name':{'label':'Name', 'type':'text'},
				'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
//				'duration':{},
				'repeat_type':{'label':'Repeat', 'type':'multitoggle', 'none':'yes', 'toggles':this.repeatOptions,
					'fn':'M.ciniki_services_settings.edit.updateInterval'},
				'repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals, 'hint':' '},
//				'repeat_end':{},
			}},
			'_due':{'label':'Due After', 'fields':{
				'due_after_days':{'label':'Days', 'type':'integer'},
				'due_after_months':{'label':'Months', 'type':'integer'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_services_settings.saveService();'},
			}},
		};
		this.edit.liveSearchCb = function(s, i, value) {
			if( i == 'category' ) {
				var rsp = M.api.getJSONBgCb('ciniki.services.serviceSearchCategory', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':5},
					function(rsp) {
						M.ciniki_services_settings.edit.liveSearchShow(s, i, M.gE(M.ciniki_services_settings.edit.panelUID + '_' + i), rsp.categories);
					});
			}
		};
		this.edit.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'category' && d.category != null ) { return d.category.name; }
			return '';
		};
		this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'category' && d.category != null ) {
				return 'M.ciniki_services_settings.edit.updateCategory(\'' + s + '\',\'' + escape(d.category.name) + '\');';
			}
		};
		this.edit.updateCategory = function(s, category) {
			M.gE(this.panelUID + '_category').value = unescape(category);
			this.removeLiveSearch(s, 'category');
		};
		this.edit.updateInterval = this.add.updateInterval;
		this.edit.sectionData = function(s) { return this.data[s]; }
		this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.services.serviceHistory', 'args':{'business_id':M.curBusinessID, 
				'service_id':this.service_id, 'field':i}};
		};
		this.edit.addClose('Cancel');

		//
		// The service panel will display the details for a service
		//
		this.service = new M.panel('Service',
			'ciniki_services_settings', 'service',
			'mc', 'medium', 'sectioned', 'ciniki.services.settings.service');
		this.service.data = {};
		this.service.sections = {
			'_info':{'label':'', 'list':{
				'type':{'label':'Type'},
				'name':{'label':'Name'},
				'category':{'label':'Category', },
				'repeat_description':{'label':'Repeats', 'visible':'no'},
			}},
			'_tasks':{'label':'Tasks', 'type':'simplegrid', 'num_cols':'2',
				'headerValues':null,
				'cellClasses':null,
				'noData':'No tasks',
				'addTxt':'Add Task',
				'addFn':'M.ciniki_services_settings.showAddTask(\'M.ciniki_services_settings.showService();\',M.ciniki_services_settings.service.service_id);',
			},
			'_buttons':{'label':'', 'buttons':{
				'edit':{'label':'Edit', 'fn':'M.ciniki_services_settings.showEdit(\'M.ciniki_services_settings.showService();\',M.ciniki_services_settings.service.service_id);'},
			}},
		};
		this.service.listLabel = function(s, i, d) {
			if( s == '_info' ) { return d.label; }
		};
		this.service.listValue = function(s, i, d) {
			if( s == '_info' && i == 'type' ) { return M.ciniki_services_settings.typeOptions[this.data[i]]; }
			if( s == '_info' ) { return this.data[i]; }
		};
		this.service.cellValue = function(s, i, j, d) {
			switch (j) {
				case 0: return d.task.step;
				case 1: return d.task.name;
			}
		};
		this.service.rowFn = function(s, i, d) {
			return 'M.ciniki_services_settings.showEditTask(\'M.ciniki_services_settings.showService();\',\'' + d.task.id + '\');';
		};
		this.service.noData = function(s) {
			return this.sections[s].noData;
		};
		this.service.sectionData = function(s) {
			if( s == '_info' ) { return this.sections[s].list; }
			if( s == '_tasks' ) { return this.data.tasks; }
		};
		this.service.addButton('edit', 'Edit', 'M.ciniki_services_settings.showEdit(\'M.ciniki_services_settings.showService();\',M.ciniki_services_settings.service.service_id);');
		this.service.addClose('Close');

		//
		// The addtask panel will display the form to add a new task to a service
		//
		this.addtask = new M.panel('Add Task',
			'ciniki_services_settings', 'addtask',
			'mc', 'medium', 'sectioned', 'ciniki.services.settings.edittask');
		this.addtask.data = {};
		this.addtask.sections = {
			'_info':{'label':'', 'fields':{
				'step':{'label':'Step', 'type':'integer'},
				'name':{'label':'Name', 'type':'text'},
				'duration':{'label':'Duration', 'type':'timeduration', 'min':'0', 'allday':'no', 
					'buttons':this.durationButtons},
			}},
			'_description':{'label':'Description', 'fields':{
				'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
			}},
			'_instructions':{'label':'Instructions', 'fields':{
				'instructions':{'label':'', 'type':'textarea', 'hidelabel':'yes'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add', 'fn':'M.ciniki_services_settings.addTask();'},
			}},
		};
		this.addtask.fieldValue = function(s, i, d) { 
			if( i == 'duration' ) { return 0; }
			return ''; 
		}
		this.addtask.addButton('add', 'Add', 'M.ciniki_services_settings.addTask();');
		this.addtask.addClose('Cancel');

		//
		// The edittask panel will display the form to edit the details of a task
		//
		this.edittask = new M.panel('Edit Task',
			'ciniki_services_settings', 'edittask',
			'mc', 'medium', 'sectioned', 'ciniki.services.settings.edittask');
		this.edittask.task_id = 0;
		this.edittask.data = {};
		this.edittask.sections = {
			'_info':{'label':'', 'fields':{
				'step':{'label':'Step', 'type':'integer'},
				'name':{'label':'Name', 'type':'text'},
				'duration':{'label':'Duration', 'type':'timeduration', 'min':'0', 'allday':'no', 
					'buttons':this.durationButtons},
			}},
			'_description':{'label':'Description', 'fields':{
				'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
			}},
			'_instructions':{'label':'Instructions', 'fields':{
				'instructions':{'label':'', 'type':'textarea', 'hidelabel':'yes'},
			}},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add', 'fn':'M.ciniki_services_settings.addTask();'},
			}},
		};
		this.edittask.fieldValue = function(s, i, d) { 
			return this.data[i];
		}
		this.edittask.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.services.taskHistory', 'args':{'business_id':M.curBusinessID, 'task_id':this.task_id, 'field':i}};
		};
		this.edittask.addButton('save', 'Save', 'M.ciniki_services_settings.saveTask();');
		this.edittask.addClose('Cancel');

		//
		// The settings panel allows the business owner to setup colours for the
		// job status colours
		//
		this.settings = new M.panel('Service Options',
			'ciniki_services_settings', 'settings',
			'mc', 'narrow', 'sectioned', 'ciniki.services.settings.settings');
		this.settings.sections = {
			'_options':{'label':'Options', 'fields':{
				'use-tracking-id':{'label':'Tracking ID', 'type':'toggle', 'toggles':this.toggleOptions},
			}},
			'_ui':{'label':'UI', 'fields':{
				'ui-form-person-category':{'label':'Person Category', 'type':'select', 'options':this.serviceCategories},
				'ui-form-business-category':{'label':'Business Category', 'type':'select', 'options':this.serviceCategories},
			}},
			'_jobs':{'label':'Job Colours', 'fields':{
				'job-status-1-colour':{'label':'Missing', 'type':'colour'},
				'job-status-2-colour':{'label':'Upcoming', 'type':'colour'},
				'job-status-10-colour':{'label':'Entered', 'type':'colour'},
				'job-status-20-colour':{'label':'Started', 'type':'colour'},
				'job-status-30-colour':{'label':'Pending', 'type':'colour'},
//				'job-status-40-colour':{'label':'Working', 'type':'colour'},
				'job-status-50-colour':{'label':'Completed', 'type':'colour'},
				'job-status-60-colour':{'label':'Signed Off', 'type':'colour'},
				'job-status-61-colour':{'label':'Skipped', 'type':'colour'},
			}},
		};
		this.settings.fieldValue = function(s, i, d) { 
			return this.data[i];
		};
		this.settings.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.services.settingsHistory', 'args':{'business_id':M.curBusinessID, 'setting':i}};
		};
		this.settings.addButton('save', 'Save', 'M.ciniki_services_settings.saveSettings();');
		this.settings.addClose('Cancel');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_services_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		this.main.cb = cb;
		this.showMain();
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMain = function() {
		this.main.reset();
		var rsp = M.api.getJSON('ciniki.services.servicesList', {'business_id':M.curBusinessID});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.main.data = {};
		this.main.sections = {};
		if( rsp.categories.length == 0 ) {
			this.main.data['_nodata'] = {};
			this.main.sections['_nodata'] = {'label':'',
				'num_cols':1, 'type':'simplegrid', 'headerValues':null,
				'cellClasses':[''],
				'noData':['No services configured'],
			};
		} else {
			for(i in rsp.categories) {
				this.main.data[rsp.categories[i].category.name + ' '] = rsp.categories[i].category.services;
				this.main.sections[rsp.categories[i].category.name + ' '] = {'label':rsp.categories[i].category.name,
					'num_cols':1, 'type':'simplegrid', 'headerValues':null,
					'cellClasses':[''],
					'noData':['No services configured'],
				};
			}
		}
		this.main.sections['settings'] = {'label':'', 'buttons':{
				'settings':{'label':'Options', 'fn':'M.ciniki_services_settings.showSettings(\'M.ciniki_services_settings.showMain();\');'}
			}};
		this.main.refresh();
		this.main.show();
	}

	this.showAdd = function(cb, cat) {
		this.add.reset();
		this.add.data = this.add.default_data;
		this.add.refresh();
		this.add.show(cb);
	};

	this.showService = function(cb, sid) {
		if( sid != null ) {
			this.service.service_id = sid;
		}
		this.service.data = {};
		var rsp = M.api.getJSON('ciniki.services.serviceGet', {'business_id':M.curBusinessID,
			'service_id':this.service.service_id, 'children':'yes'});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.service.data = rsp.service;
		if( rsp.service.repeat_description != null && rsp.service.repeat_description != '' ) {
			this.service.sections._info.list.repeat_description.visible = 'yes';
		} else {
			this.service.sections._info.list.repeat_description.visible = 'no';
		}

		this.service.refresh();
		this.service.show(cb);
	};

	this.showEdit = function(cb, sid) {
		this.edit.reset();
		if( sid != null ) {
			this.edit.service_id = sid;
		}
		this.edit.data = {};
		var rsp = M.api.getJSON('ciniki.services.serviceGet', {'business_id':M.curBusinessID,
			'service_id':this.edit.service_id, 'children':'no'});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.edit.data = rsp.service;
		
		this.edit.refresh();
		this.edit.show(cb);
		this.edit.updateInterval('repeat_type', this.repeatOptions[rsp.service.repeat_type], 'toggle_on');
	};

	this.addService = function() {
		var c = this.add.serializeForm('yes');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.serviceAdd', {'business_id':M.curBusinessID}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_settings.add.close();
	};

	this.saveService = function() {
		var c = this.edit.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.serviceUpdate', 
				{'business_id':M.curBusinessID, 'service_id':M.ciniki_services_settings.edit.service_id}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_settings.edit.close();
	};

	this.delService = function() {
		// FIXME: Add ability to delete service, can only be done if this service hasn't been used yet.
	};

	this.showAddTask = function(cb, sid) {
		this.addtask.reset();
		this.addtask.service_id = sid;
		this.addtask.refresh();
		this.addtask.show(cb);
	};

	this.showEditTask = function(cb, tid) {
		this.edittask.reset();
		if( tid != null ) {
			this.edittask.task_id = tid;
		}
		this.edittask.data = {};
		var rsp = M.api.getJSON('ciniki.services.taskGet', {'business_id':M.curBusinessID,
			'task_id':this.edittask.task_id});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.edittask.data = rsp.task;
		
		this.edittask.refresh();
		this.edittask.show(cb);
	};

	this.addTask = function() {
		var c = this.addtask.serializeForm('yes');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.taskAdd', {'business_id':M.curBusinessID, 'service_id':this.addtask.service_id}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_settings.addtask.close();
	};

	this.saveTask = function() {
		var c = this.edittask.serializeForm('no');
		if( c != '' ) {
			var rsp = M.api.postJSON('ciniki.services.taskUpdate', 
				{'business_id':M.curBusinessID, 'task_id':this.edittask.task_id}, c);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
		}
		M.ciniki_services_settings.edittask.close();
	};

	this.delTask = function() {
		// FIXME: Add ability to delete task, can only be done if task hasn't been used yet.
	};

	//
	// Grab the current colours for the services module
	//
	this.showSettings = function(cb) {
		var rsp = M.api.getJSON('ciniki.services.serviceSearchCategory', {'business_id':M.curBusinessID, 'start_needle':''});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.serviceCategories = {};
		this.serviceCategories[''] = 'All';
		for(i in rsp.categories) {
			this.serviceCategories[rsp.categories[i].category.name] = rsp.categories[i].category.name;
		}
		if( rsp.categories.length > 0 ) {
			this.settings.sections._ui.fields['ui-form-person-category'].active = 'yes';
			this.settings.sections._ui.fields['ui-form-business-category'].active = 'yes';
			this.settings.sections._ui.fields['ui-form-person-category'].options = this.serviceCategories;
			this.settings.sections._ui.fields['ui-form-business-category'].options = this.serviceCategories;
		} else {
			this.settings.sections._ui.fields['ui-form-person-category'].active = 'no';
			this.settings.sections._ui.fields['ui-form-business-category'].active = 'no';
		}
		var rsp = M.api.getJSON('ciniki.services.settingsGet', {'business_id':M.curBusinessID});
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		this.settings.data = rsp.settings;
		this.settings.refresh();
		this.settings.show(cb);
	};

	this.saveSettings = function() {
		var content = this.settings.serializeForm('no');
		if( content != '' ) {
			var rsp = M.api.postJSON('ciniki.services.settingsUpdate', 
				{'business_id':M.curBusinessID}, content);
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			} 
		}
		M.ciniki_services_settings.settings.close();
	};
}
