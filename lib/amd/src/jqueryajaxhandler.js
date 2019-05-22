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
 * @author Brian Barnes <brian.barnes@shezarlms.com>
 * @package shezar
 * @subpackage shezar_core
 */

define(['core/str'], function (mdlstring) {
    var ajaxhandler = {
        success: function(response) {
            if (response.responseJSON && response.responseJSON.error) {
                YUI().use('moodle-core-notification-ajaxexception', function () {
                    return new M.core.ajaxException(response.responseJSON).show();
                });
            }
        },

        error: function (response, options) {
            // return if it is manually aborted via javascript
            if (response.statusText === 'abort') {return;}

            var requiredstrings = [];
            requiredstrings.push({key: 'accessdenied', component: 'admin'});
            requiredstrings.push({key: 'resourcenotfound', component: 'admin'});
            requiredstrings.push({key: 'unknownerror', component: 'core'});

            mdlstring.get_strings(requiredstrings).done(function (strings) {
                var tstr = [];
                for (var i = 0; i < requiredstrings.length; i++) {
                    tstr[requiredstrings[i].key] = strings[i];
                }

                var error = {
                    debugginginfo: '',
                    error: '',
                    reproductionlink: '',
                    stacktrace: '',
                };
                if (response.hasOwnProperty('status') && response.status === 403) {
                    error.error = tstr.accessdenied;
                } else if (response.hasOwnProperty('status') && response.status === 404) {
                    error.error = tstr.resourcenotfound;
                } else {
                    error.error = tstr.unknownerror;
                }

                if (options.hasOwnProperty('url') && typeof(options.url) === 'string') {
                    error.reproductionlink = options.url;
                }

                if (response.hasOwnProperty('stacktrace') && typeof(response.stacktrace) === 'string') {
                    error.stacktrace = options.stacktrace;
                }

                YUI().use('moodle-core-notification-ajaxexception', function () {
                    return new M.core.ajaxException(error).show();
                });
            });
        }
    };

    return ajaxhandler;
});