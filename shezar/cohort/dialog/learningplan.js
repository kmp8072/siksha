/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2010 onwards shezar Learning Solutions LTD
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
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage cohort
 */
M.shezar_cohortplans = M.shezar_cohortplans || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},

    /**
     * module initialisation method called by php js_init_call()
     *
     * @param object    YUI instance
     * @param string    args supplied in JSON format
     */
    init: function(Y, args){

        var module = this;

        // save a reference to the Y instance (all of its dependencies included)
        this.Y = Y;

        // if defined, parse args into this module's config object
        if (args) {
            var jargs = Y.JSON.parse(args);
            for (var a in jargs) {
                if (Y.Object.owns(jargs, a)) {
                    this.config[a] = jargs[a];
                }
            }
        }

        // check if required param id is available
        if (!this.config.id) {
            throw new Error('M.shezar_cohortplans.init()-> Required config \'id\' not available.');
        }

        // check jQuery dependency and continue with setup
        if (typeof $ === 'undefined') {
            throw new Error('M.shezar_cohortplans.init()-> jQuery dependency required for this module to function.');
        }

        // The save changes confirmation dialog
        var shezarDialog_createplans = function() {

            // Setup the handler
            var handler = new shezarDialog_handler();

            // Store reference to this
            var self = this;
            var buttonsObj = {};
            buttonsObj[M.util.get_string('save', 'shezar_core')] = function() { self.save(); };
            buttonsObj[M.util.get_string('cancel','moodle')] = function() { handler._cancel(); };

            // Call the parent dialog object and link us
            shezarDialog.call(
                this,
                'createplans-dialog',
                'unused', // buttonid unused
                {
                    buttons: buttonsObj,
                    title: '<h2>'+M.util.get_string('confirmcreateplans', 'shezar_plan')+'</h2>'
                },
                'unused', // default_url unused
                handler
            );

            this.old_open = this.open;
            this.open = function(html, table, rows) {
                // Do the default open first to get everything ready
                this.old_open();

                this.dialog.height(270);

                // Now load the custom html content
                this.dialog.html(html);

                this.table = table;
                this.rows = rows;
            };

            // Don't load anything
            this.load = function(url, method) {
            }
        };


        /**
         * Event bindings, Dialog instantiation and setup
         */
        // Add a function to launch the save changes dialog
        $('input[name="submitbutton"]').click(function(event) {
            return module.handleCreatePlans(event);
        });

        shezarDialogs['createplans'] = new shezarDialog_createplans();
    },


    /**
     *
     */
    handleCreatePlans: function(event){

        var dialog = shezarDialogs['createplans'];

        if (dialog.savechanges === true) {
            window.onbeforeunload = null;
            return true;
        }

        var cohortid = M.shezar_cohortplans.config.id;
        var plantemplate = $('#id_plantemplateid').find(':selected').val();
        var manual = $('#id_excludecreatedmanual').is(':checked');
        var auto = $('#id_excludecreatedauto').is(':checked');
        var complete = $('#id_excludecompleted').is(':checked');
        var autocreatenew = $('#id_autocreatenew').is(':checked');

        var url = M.cfg.wwwroot + '/shezar/cohort/dialog/learningplanusers.php' +
                    '?id=' + cohortid +
                    '&plantemplate=' + plantemplate +
                    '&manual=' + manual +
                    '&auto=' + auto +
                    '&complete=' + complete +
                    '&autocreatenew=' + autocreatenew;

        var html = '';

        $.getJSON(url, function(data) {
            if (data !== 'error') {
                html += data['html'];

                $("button:contains(" + M.util.get_string('save', 'shezar_core') + ")").show();
                shezarDialogs['createplans'].open(html);
                shezarDialogs['createplans'].save = function() {
                    shezarDialogs['createplans'].savechanges = true;
                    this.hide();
                    $('input[name="submitbutton"]').trigger('click');
                };
            }
        });

        event.preventDefault();
    }
};
