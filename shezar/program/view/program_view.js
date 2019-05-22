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
 * @author Dave Wallace <dave.wallace@kineo.co.nz>
 * @package shezar
 * @subpackage program
 */
M.shezar_programview = M.shezar_programview || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {
        userid:'',
        user_fullname:''
    },

    shezarDialog_extension: null,

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
            throw new Error('M.shezar_programview.init()-> Required config \'id\' not available.');
        }

        // check jQuery dependency and continue with setup
        if (typeof $ === 'undefined') {
            throw new Error('M.shezar_programview.init()-> jQuery dependency required for this module to function.');
        }


        // define the dialog handler
        shezarDialog_extension_handler = function() {};

        shezarDialog_extension_handler.prototype = new shezarDialog_handler();

        shezarDialog_extension_handler.prototype.first_load = function() {
            M.shezar_core.build_datepicker(Y, 'input[name="extensiontime"]', M.util.get_string('datepickerlongyeardisplayformat', 'shezar_core'));
            $('#ui-datepicker-div').css('z-index',1600);
        }

        shezarDialog_extension_handler.prototype.every_load = function() {
            // rebind placeholder for date picker
            $('input[placeholder], textarea[placeholder]').placeholder();
        }

        // Adapt the handler's save function
        shezarDialog_extension_handler.prototype._save = function() {

            var success = false;

            var extensiontime = $('.extensiontime', this._container).val();
            var extensiontimehour = $('.extensiontimehour', this._container).val();
            var extensiontimeminute = $('.extensiontimeminute', this._container).val();
            var extensionreason = $('.extensionreason', this._container).val();

            var dateformat = new RegExp(M.util.get_string('datepickerlongyearregexjs', 'shezar_core'));

            if (dateformat.test(extensiontime) == false) {
                alert(M.util.get_string('pleaseentervaliddate', 'shezar_program', M.util.get_string('datepickerlongyearplaceholder', 'shezar_core')));
            } else if (extensionreason=='') {
                alert(M.util.get_string('pleaseentervalidreason', 'shezar_program'));
            } else {
                success = true;
            }

            if (success) {
                var data = {
                    id: module.config.id,
                    userid: module.config.userid,
                    extrequest: "1",
                    extdate: extensiontime,
                    exthour: extensiontimehour,
                    extminute: extensiontimeminute,
                    extreason: extensionreason,
                    sesskey: M.cfg.sesskey
                };

                $.ajax({
                    type: 'POST',
                    url: M.cfg.wwwroot + '/shezar/program/extension.php',
                    data: data,
                    success: module.shezar_program_extension_result
                });
                this._dialog.hide();
            }
        }

        // Define the extension request dialog
        this.shezarDialog_extension = function() {

            this.url = M.cfg.wwwroot + '/shezar/program/view/set_extension.php?id='+module.config.id+'&amp;userid='+module.config.userid;

            // Setup the handler
            var handler = new shezarDialog_extension_handler();

            // Store reference to this
            var self = this;

            var buttonsObj = {};
            buttonsObj[M.util.get_string('ok', 'shezar_program')] = function() { handler._save(); };
            buttonsObj[M.util.get_string('cancel', 'shezar_program')] = function() { handler._cancel(); };

            // Call the parent dialog object and link us
            shezarDialog.call(
            this,
            'extension-dialog',
            'unused', // buttonid unused
            {
                buttons: buttonsObj,
                title: '<h2>'+M.util.get_string('extensionrequest', 'shezar_program', module.config.user_fullname)+'</h2>'
            },
            this.url,
            handler
            );

            this.old_open = this.open;
            this.open = function() {
            this.old_open();
            this.dialog.height(150);
            }

        }

        shezarDialogs['extension'] = new this.shezarDialog_extension();

        // Bind the extension request dialog to the 'Request an extension' link
        $('a#extrequestlink').click(function() {
            shezarDialogs['extension'].open();
            return false;
        });
    },

    /**
     * Update extension text and notify user of success or failure depending on result.
     */
    shezar_program_extension_result: function(data) {
        if (data.success) {
            // Get existing text.
            var extensiontext = $('a#extrequestlink');

            var new_text = data.message;

            if (extensiontext.size()) {
                // If text found replace.
                extensiontext.replaceWith(new_text);
            }

            $('div#shezar-header-notifications').html(M.shezar_programview.config.notify_html);
        } else {
            var notify_text = data.message;

            var notify_html = '<div class="notifyproblem" style="text-align:center">' + notify_text + '</div>';

            $('div#shezar-header-notifications').html(notify_html);
        }
    }
};
