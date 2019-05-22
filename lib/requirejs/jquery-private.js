// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This module depends on the real jquery - and returns the non-global version of it.
 *
 * @module     jquery-private
 * @package    core
 * @copyright  2015 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function ($) {
    // Do shezar specific code here
    // Ajax error handlers.
    // These need to be kept in sync with those in lib/yui/src/jquery/jqueryajaxhandler/js/jqueryajaxhandler.js.
    $(document).bind('ajaxSuccess', function (event, response, options) {
        require(['core/jqueryajaxhandler'], function (handler) {
            handler.success(response);
        });
    });

    $(document).bind('ajaxError', function(event, response, options, error) {
        require(['core/jqueryajaxhandler'], function (handler) {
            handler.error(response, options);
        });
    });

    // Add some handers if behat is running.
    if (M.cfg.behatrunning === true) {
        /**
         * Generates a unique shezar ID.
         * @function
         */
        var shezar_generate_id = (function(){
            // We are operating within a lambda style function here to establish
            // a scope for the purpose of this function.
            // This way idcount can't be influenced from the outside and we will always get a unique id.
            var idcount = 0;
            return function() {
                return 'shezar-amd-genid-'+(idcount++);
            }
        }());

        // Moodle watches all XHRHttpRequests that come through YUI.
        // This is used in behat, and potentially will be used elsewhere.
        // We want to be sure that jQuery events get monitored as well seeing as we rely on jQuery.
        if (M.util.js_pending !== 'undefined') {
            $(document).bind("ajaxSend", function (ev, jqxhr, options) {
                // This is a little nasty - hopefully it stays unique.
                jqxhr.shezarxhrid = shezar_generate_id();
                M.util.js_pending(jqxhr.shezarxhrid);
            }).bind("ajaxComplete", function (ev, jqxhr, options) {
                M.util.js_complete(jqxhr.shezarxhrid);
            });
        }
    }

    // This noConflict call tells JQuery to remove the variable from the global scope - so
    // the only remaining instance will be the sandboxed one.
    return $.noConflict( true );
});
