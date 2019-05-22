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
 * @subpackage core/assign
 */
define(['jquery', 'core/str', 'core/config', 'shezar_core/jquery.dataTables-lazy'], function($, mdlstrings, mdlcfg) {
    var datatable = {
        init: function (module, suffix, itemid) {
            var requiredstrings = [];
            requiredstrings.push({key: 'datatable:sEmptyTable', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sInfo', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sInfoEmpty', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sInfoFiltered', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sInfoPostFix', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sInfoThousands', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sLengthMenu', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sLoadingRecords', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sProcessing', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sSearch', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:sZeroRecords', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:oPaginate:sFirst', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:oPaginate:sLast', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:oPaginate:sNext', component: 'shezar_core'});
            requiredstrings.push({key: 'datatable:oPaginate:sPrevious', component: 'shezar_core'});

            mdlstrings.get_strings(requiredstrings).done(function(strings) {
                var tstr = [];
                for (var i = 0; i < requiredstrings.length; i++) {
                    tstr[requiredstrings[i].key] = strings[i];
                }
                $(document).ready(function() {
                    var oTable = $("#datatable").dataTable({
                        "searchDelay": 500,
                        "bProcessing": true,
                        "bServerSide": true,
                        "sPaginationType": "full_numbers",
                        "sAjaxSource": mdlcfg.wwwroot + "/shezar/" + module + "/lib/assign/ajax.php",
                        "fnServerParams": function ( aoData ) {
                            aoData.push( { "name": "module", "value": module } );
                            aoData.push( { "name": "suffix", "value": suffix } );
                            aoData.push( { "name": "itemid", "value": itemid } );
                            aoData.push( { "name": "sesskey", "value": mdlcfg.sesskey } );
                        },
                        "oLanguage" : {
                            "sEmptyTable":     tstr['datatable:sEmptyTable'],
                            "sInfo":           tstr['datatable:sInfo'],
                            "sInfoEmpty":      tstr['datatable:sInfoEmpty'],
                            "sInfoFiltered":   tstr['datatable:sInfoFiltered'],
                            "sInfoPostFix":    tstr['datatable:sInfoPostFix'],
                            "sInfoThousands":  tstr['datatable:sInfoThousands'],
                            "sLengthMenu":     tstr['datatable:sLengthMenu'],
                            "sLoadingRecords": tstr['datatable:sLoadingRecords'],
                            "sProcessing":     tstr['datatable:sProcessing'],
                            "sSearch":         tstr['datatable:sSearch'],
                            "sZeroRecords":    tstr['datatable:sZeroRecords'],
                            "oPaginate": {
                                "sFirst":    tstr['datatable:oPaginate:sFirst'],
                                "sLast":     tstr['datatable:oPaginate:sLast'],
                                "sNext":     tstr['datatable:oPaginate:sNext'],
                                "sPrevious": tstr['datatable:oPaginate:sPrevious']
                            }
                        }
                    });
                });
            });
        }
    }
    return datatable;
});