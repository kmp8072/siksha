/*
 * This file is part of shezar LMS
 *
 * Copyright (C) 2016 onwards shezar Learning Solutions LTD
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
 * @author    Brian Barnes <brian.barnes@shezarlearning.com>
 * @copyright 2016 onwards shezar Learning Solutions Ltd {@link http://www.shezarlms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   core
 */

define(['jquery', 'core/config', 'core/localstorage', 'core/ajax'], function ($, config, storage, ajax) {
    var initstarted = false;
    var iconsdata = false;
    var templatesDeferred = [];

    /**
     * Loads the icons cache
     *
     * @method init
     * @private
     */
    var init = function () {
        var STORAGEKEY = 'core_flex_icon/' + config.theme + '/cache';
        initstarted = true;

        var cachesrc = storage.get(STORAGEKEY);
        if (cachesrc) {
            iconsdata = JSON.parse(cachesrc);
        } else {
            var promises = ajax.call([{
                methodname: 'core_output_get_flex_icons',
                args: {
                    themename: config.theme
                }
            }], true, false);

            promises[0].done(function (iconsCacheSource) {
                storage.set(STORAGEKEY, JSON.stringify(iconsCacheSource));
                iconsdata = iconsCacheSource;
                for (var index = 0; index < templatesDeferred.length; index++) {
                    templatesDeferred[index].resolve();
                }
            });
        }
    };

    var cache = /** @alias module:core/flex_icon */{
        loadingflex: [],
        loadingtranslation: [],

        /**
         * Gets the template and data for the given flex icon
         *
         * @method getFlexTemplateData
         * @public
         * @param {String} flexIdentifier The requested flex icon
         * @return {Promise} Resolves with an object that contains the
         *                   template name and base data for the template
         */
        getFlexTemplateData: function (identifier) {
            var templatepromise = $.Deferred();

            if (this.loadingflex.length > 0) {
                // Detect and prevent duplicate requests.
                for (var i in this.loadingflex) {
                    if (this.loadingflex[i].theme === config.theme && this.loadingflex[i].identifier === identifier) {
                        return this.loadingflex[i].promise;
                    }
                }
            } else {
                this.loadingflex.push({theme: config.theme, identifier: identifier, promise: templatepromise.promise()});
            }

            /**
             * Resolve template data once the cache has been loaded.
             *
             * @method resolvetemplate
             * @private
             */
            var resolvetemplate = function () {
                if (typeof iconsdata.icons[identifier] === 'undefined') {
                    templatepromise.reject();
                    return;
                }

                var iconindexs = iconsdata.icons[identifier];
                var icondata = {
                    data: iconsdata.datas[iconindexs[1]],
                    template: iconsdata.templates[iconindexs[0]]
                };
                icondata.data.identifier = identifier;

                templatepromise.resolve(icondata);
            };

            if (iconsdata) {
                // Cache is loaded - resolve immediately
                resolvetemplate();
            } else {
                // Cache hasn't been loaded, resolve once it has been.
                var response = $.Deferred();

                response.done(function () {
                    resolvetemplate();
                });
                templatesDeferred.push(response);
            }

            return templatepromise.promise();
        },
    };

    if (!initstarted) {
        init();
    }
    return cache;
});