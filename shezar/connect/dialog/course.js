/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2015 onwards shezar Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_connect
 */

/**
 * This file contains the Javascript for the dialog that lets you
 * add courses to a shezar Connect clients.
 */

M.shezar_connect_course = M.shezar_connect_course || {
    Y: null,
    selected: null,
    instanceid: null,

    /**
     * Module initialisation method called by php js_init_call()
     *
     * @param Y          object YUI instance
     * @param selected   string selected
     * @param instanceid int    instanceid
     */
    init: function(Y, selected, instanceid) {
        // Check jQuery dependency is available.
        if (typeof $ === 'undefined') {
            throw new Error('M.shezar_connect_course.init()-> jQuery dependency required for this module.');
        }

        this.Y = Y;
        this.selected = selected;
        this.instanceid = instanceid;

        this.init_dialogs();
    },


    init_dialogs: function() {

        // Init the dialogs.
        var url = M.cfg.wwwroot + '/shezar/connect/dialog/';

        // Enrolled courses.
        var ehandler = new shezarDialog_handler_connectcourses();
        ehandler.baseurl = url;

        var dbuttons = {};
        dbuttons[M.util.get_string('ok', 'moodle')] = function() { ehandler._update() };
        dbuttons[M.util.get_string('cancel', 'moodle')] = function() { ehandler._cancel() };
        shezarDialogs['id_coursesadd'] = new shezarDialog(
            'shezar-connect-courses-dialog',
            'id_coursesadd',
            {
                buttons: dbuttons,
                title: '<h2>' + M.util.get_string('courses', 'shezar_connect') + '</h2>'
            },
            url + 'course.php?selected=' + this.selected
                + '&instanceid=' + this.instanceid
                + '&sesskey=' + M.cfg.sesskey,
            ehandler
        );
    }
};


/**
 * Create handler for the dialog
 */
shezarDialog_handler_connectcourses = function() {
    // Base url.
    this.baseurl = '';
    this.course_items = $('input:hidden[name="courses"]').val();
    this.course_items = (this.course_items && this.course_items.length > 0) ? this.course_items.split(',') : [];
    this.course_table = $('#shezar-connect-courses-table');

    this.add_course_delete_event_handlers();

    this.check_table_hidden_status();
};

shezarDialog_handler_connectcourses.prototype = new shezarDialog_handler_treeview_multiselect();

/**
 * Add a row to a table on the calling page
 * Also hides the dialog and any no item notice
 *
 * @return void
 */
shezarDialog_handler_connectcourses.prototype._update = function() {

    var self = this;
    var elements = $('.selected > div > span', this._container);
    var selected_str = this._get_ids(elements).join(',');
    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + selected_str + params;

    var newids = new Array();

    // Loop through the selected elements.
    elements.each(function() {

        // Get id
        var itemid = $(this).attr('id').split('_');
        itemid = itemid[itemid.length-1];  // The last item is the actual id.
        itemid = parseInt(itemid);

        if (!self.course_item_exists(itemid)) {
            newids.push(itemid);
            self.add_course_item(itemid);
        }
    });

    if (newids.length > 0) {
        this._dialog.showLoading();

        var ajax_url = M.cfg.wwwroot + '/shezar/connect/dialog/course_item.php?itemid=' + newids.join(',') + params;
        $.getJSON(ajax_url, function(data) {
            if (data.error) {
                self._dialog.hide();
                alert(data.error);
                return;
            }
            $.each(data['rows'], function(index, html) {
                self.create_item(html);
            });

            self._dialog.hide();
        })
    } else {
        this._dialog.hide();
    }
};

/**
 * Checks if the item id exists in this category
 */
shezarDialog_handler_connectcourses.prototype.course_item_exists = function(itemid) {
    for (x in this.course_items) {
        if (this.course_items[x] == itemid) {
            return true;
        }
    }
    return false;
};

shezarDialog_handler_connectcourses.prototype.check_table_hidden_status = function() {

    if (this.course_items.length == 0) {
        $(this.course_table).hide();
    } else {
        $(this.course_table).show();
    }
};

shezarDialog_handler_connectcourses.prototype.add_course_delete_event_handlers = function() {
    // Remove previous click event handlers.
    $('.connectcoursedeletelink', this.course_table).unbind('click');

    // Add fresh event handlers.
    var self = this;
    this.course_table.on('click', '.connectcoursedeletelink', function(event) {
        event.preventDefault();
        self.remove_course_item(this);
    });
};

/**
 * Adds an item
 */
shezarDialog_handler_connectcourses.prototype.add_course_item = function(itemid) {
    this.course_items.push(itemid);

    $('input:hidden[name="courses"]').val(this.course_items.join(','));

    this.check_table_hidden_status();
};

/**
 * Creates an element and then adds it
 */
shezarDialog_handler_connectcourses.prototype.create_item = function(html) {
    var element = $(html);

    // Add the item element to the table.
    this.course_table.append(element);
};

shezarDialog_handler_connectcourses.prototype.remove_course_item = function(item) {
    var itemid = $(item).closest('div').attr('id').match(/[\d]+$/);  // Get the id part from e.g 'course-item-1'.
    var row = $(item).closest('tr');

    // Remove the item from the array of items.
    this.course_items = $.grep(this.course_items, function (element, x) {
        return (element == itemid);
    }, true);

    // Remove item from interface.
    row.remove();

    this.check_table_hidden_status();

    $('input:hidden[name="courses"]').val(this.course_items.join(','));

    var url = this._dialog.default_url.split("selected=");
    var params = url[1].slice(url[1].indexOf('&'));
    this._dialog.default_url = url[0] + 'selected=' + this.course_items.join(',') + params;
};
