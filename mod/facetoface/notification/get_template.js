/**
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
 * @author Aaron Barnes <aaron.barnes@shezarlms.com>
 * @author Alastair Munro <alastair.munro@shezarlms.com>
 * @package shezar
 * @subpackage facetoface
 */

M.shezar_f2f_notification_template = M.shezar_f2f_notification_template || {

    Y: null,
    // optional php params and defaults defined here, args passed to init method
    // below will override these values
    config: {},
    // public handler reference for the dialog
    shezarDialog_handler_preRequisite: null,

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

        // check jQuery dependency is available
        if (typeof $ === 'undefined') {
            throw new Error('M.shezar_f2f_notification_template.init()-> jQuery dependency required for this module to function.');
        }

        var templates = M.shezar_f2f_notification_template.config.templates;

        $(function() {

            // Attach event to drop down
            $('select#id_templateid').change(function() {
                var select = $(this);

                // Get current value
                var current = select.val();

                // Overwrite form data.
                if (current !== '0') {
                    $('input#id_title').val(templates[current].title);
                    $('textarea#id_body_editor').val(templates[current].body);

                    var templatecontent = "";
                    if (templates[current].managerprefix) {
                        templatecontent = templates[current].managerprefix;
                    }
                    $('textarea#id_managerprefix_editor').val(templatecontent);

                    if (typeof tinyMCE !== "undefined") {
                        tinyMCE.get('id_body_editor').setContent(templates[current].body);
                        tinyMCE.get('id_managerprefix_editor').setContent(templatecontent);
                    }
                } else {
                    $('input#id_title').val('');
                    $('textarea#id_body_editor').val('');
                    $('textarea#id_managerprefix_editor').val('');
                    if (typeof tinyMCE !== "undefined") {
                        tinyMCE.get('id_body_editor').setContent('');
                        tinyMCE.get('id_managerprefix_editor').setContent('');
                    }
                }
                // Try to update editor
                var bodyeditor = Y.one('#id_body_editor').getData('Editor');
                if(bodyeditor && typeof bodyeditor.updateFromTextArea === "function") {
                    bodyeditor.updateFromTextArea();
                }

                var prefixeditor = Y.one('#id_managerprefix_editor').getData('Editor');
                if(prefixeditor && typeof prefixeditor.updateFromTextArea === "function") {
                    prefixeditor.updateFromTextArea();
                }
            });

        });
    }
}
