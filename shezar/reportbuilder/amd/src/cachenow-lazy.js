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
 * @author Valerii Kuznetsov <valerii.kuznetsov@shezarlms.com>
 * @author Brian Barnes <brian.barnes@shezarlearning.com>
 * @package shezar
 * @subpackage shezar_reportbuilder
 */
define(['jquery', 'core/config', 'core/str'], function($, mdlconfig, mdlstrings) {
    var dialoghandler = function() {
        var requiredstrings = [];
        requiredstrings.push({key: 'ok', component: 'moodle'});
        requiredstrings.push({key: 'cachenow_title', component: 'shezar_reportbuilder'});
        mdlstrings.get_strings(requiredstrings).done(function(strings) {
            var tstr = [];
            for (var i = 0; i < requiredstrings.length; i++) {
                tstr[requiredstrings[i].key] = strings[i];
            }

            var handler = new shezarDialog_handler();
            var buttonObj = {};
            $('.show-cachenow-dialog').css('display','inline');

            $('.show-cachenow-dialog').each(function(ind, inst) {
                var id = $(inst).data('id');
                var url = mdlconfig.wwwroot + '/shezar/reportbuilder/ajax/cachenow.php?reportid=' + id;
                var name = 'cachenow';

                buttonObj[tstr.ok] = function() {
                    handler._cancel();
                };

                shezarDialogs[name] = new shezarDialog(
                    name,
                    $(inst).attr('id'),
                    {
                        buttons: buttonObj,
                        title: '<h2>' + tstr.cachenow_title + '</h2>',
                        width: '500',
                        height: '200',
                        dialogClass: 'shezar-dialog notifynotice'
                    },
                    url,
                    handler
                );
                $('#show-cachenow-dialog-'+id).bind('click', function() {$('#cachenotice_'+id).css('display', 'none');});
            });
        });
    };

    return {
        /**
         * module initialisation method called by php js_init_call()
         *
         * @param object    YUI instance
         * @param string    args supplied in JSON format
         */
        init: function() {
            // Print and PDF dialog boxes.
            if (window.dialogsInited) {
                dialoghandler();
            } else {
                // Queue it up.
                if (!$.isArray(window.dialoginits)) {
                    window.dialoginits = [];
                }
                window.dialoginits.push(dialoghandler);
            }
        }
    };
});