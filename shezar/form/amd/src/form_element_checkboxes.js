/**
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
 * @module shezar_form/form_element_checkboxes
 * @class CheckboxesElement
 * @author Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'shezar_form/form'], function($, Form) {

    var ERROR_CONTAINER_CLASS = 'shezar_form-error-container',
        ERROR_CONTAINER_SELECTOR = '.'+ERROR_CONTAINER_CLASS;

    /**
     * Checkboxes element
     *
     * @class
     * @constructor
     * @augments Form.Element
     *
     * @param {(Form|Group)} parent
     * @param {string} type
     * @param {string} id
     * @param {HTMLElement} node
     */
    function CheckboxesElement(parent, type, id, node) {

        if (!(this instanceof CheckboxesElement)) {
            return new CheckboxesElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.container = null;
        this.inputs = null;

    }

    CheckboxesElement.prototype = Object.create(Form.Element.prototype);
    CheckboxesElement.prototype.constructor = CheckboxesElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    CheckboxesElement.prototype.toString = function() {
        return '[object CheckboxesElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    CheckboxesElement.prototype.init = function(done) {
        var inputs = $('#' + this.id + ' input[type=checkbox]'),
            input = $(inputs[0]),
            deferred = $.Deferred();

        this.container = $('#' + this.id);
        this.inputs = inputs;
        // Call the changed method when this element is changed.
        this.inputs.change($.proxy(this.changed, this));

        // Only do this if we need to.
        if (input.attr('required')) {
            Form.debug('Required checkboxes are only processed on the server.', this, Form.LOGLEVEL.warn);
            deferred.resolve();

            /**
             * TODO TL-9414: we can't use the required polyfill here as we have multiple inputs and 1..n of them need to be checked.
             * require(['shezar_form/modernizr'], function (Mod) {
             *     if (!Mod.input.required) {
             *         var submitselector = 'input[type="submit"]:not([formnovalidate])';
             *         input.change($.proxy(self.polyFillValidate, self));
             *         input.closest('form').find(submitselector).click($.proxy(self.polyFillValidate, self));
             *     }
             *     deferred.resolve();
             * });
             */
        } else {
            deferred.resolve();
        }

        deferred.done(done);
    };

    /**
     * Returns the value of the checked checkbox
     * @returns {string}
     */
    CheckboxesElement.prototype.getValue = function() {
        // Check each checkbox and see if its selected.
        var returnarr = [];
        for (var i = 0; i < this.inputs.length; i++) {
            var input = $(this.inputs[i]);
            if (input.is(':checked')) {
                returnarr.push(input.val());
            }
        }
        if (returnarr.length === 0) {
            return false;
        }
        return returnarr;
    };

    /**
     * Performs any polyfil validation.
     * @param {Event} e
     */
    CheckboxesElement.prototype.polyFillValidate = function(e) {
        var valid = false,
            container = this.container;
        this.inputs.each(function(index, checkbox) {
            if ($(checkbox).prop('checked')) {
                valid = true;
            }
        });
        if (valid) {
            this.validationerroradded = false;
            container.closest('.tf_element').find(ERROR_CONTAINER_SELECTOR).remove();
        } else {
            e.preventDefault();
            if (!this.validationerroradded) {
                this.validationerroradded = true;
                require(['core/templates', 'core/str', 'core/config'], function (templates, mdlstrings, mdlconfig) {
                    mdlstrings.get_string('required','core').done(function (requiredstring) {
                        var context = {
                            errors_has_items: true,
                            errors: [{message: requiredstring}]
                        };
                        templates.render('shezar_form/validation_errors', context, mdlconfig.theme).done(function (template) {
                            container.prepend(template);
                        });
                    });
                });
            }
        }
    };

    return CheckboxesElement;

});