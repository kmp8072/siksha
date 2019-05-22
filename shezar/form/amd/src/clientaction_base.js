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
 * @author  Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @package shezar_form
 */

/**
 * shezar client action base class.
 *
 * @module  shezar_form/clientaction_base
 * @class   ClientAction
 * @author  Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['shezar_form/form'], function(Form) {

    /**
     * Client action abstract class.
     *
     * @abstract
     * @class
     * @constructor
     * @param {Object} actionconfig Configuration object.
     * @param {Form} shezarform
     */
    function ClientAction(actionconfig, shezarform) {

        /**
         * The form this client action belongs to.
         * @protected
         * @type {Form}
         */
        this.form = shezarform;

        /**
         * The target element of this client action.
         *
         * @protected
         * @type {(Element|Group)}
         */
        this.target = shezarform.getItemById(actionconfig.target);

        /**
         * Array of HTML Id attributes of elements being watched by this action.
         *
         * @type {string[]}
         */
        this.watchedIds = [];

        /**
         * The client action configuration.
         *
         * @protected
         * @type {Object}
         */
        this.actionconfig = actionconfig;

        if (!this.target) {
            Form.debug('Unable to find the target element #' + actionconfig.target, ClientAction, Form.LOGLEVEL.error);
        }
    }
    ClientAction.prototype = {

        /**
         * Checks the state of this client action.
         * This get called by the form when required.
         *
         * @abstract
         */
        checkState: function() {
            Form.debug('A ClientAction does not override the checkState method.', this, Form.LOGLEVEL.error);
        },

        /**
         * Returns the target of this client action.
         *
         * @returns {(Element|Group)}
         */
        getTarget: function() {
            return this.target;
        },

        /**
         * Initialises this client action.
         * @return {void}
         */
        init: function() {
            // Each client action must override this.
        },

        /**
         * Gets the current state, if it is known.
         * @returns {(mixed|boolean|null)} Null if not known.
         */
        getCurrentState: function() {
            return null;
        },

        /**
         * Returns the elements ids being watched by this client action.
         *
         * @returns {string[]}
         */
        getWatchedIds: function() {
            return this.watchedIds;
        }
    };
    return ClientAction;

});