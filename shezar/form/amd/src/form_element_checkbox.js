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
 * @module  shezar_form/form_element_checkbox
 * @class   CheckboxElement
 * @author  Sam Hemelryk <sam.hemelryk@shezarlms.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'shezar_form/form'], function($, Form) {

    var ERROR_CONTAINER_CLASS = 'shezar_form-error-container',
        ERROR_CONTAINER_SELECTOR = '.'+ERROR_CONTAINER_CLASS;

    /**
     * Checkbox element
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
    function CheckboxElement(parent, type, id, node) {
        if (!(this instanceof CheckboxElement)) {
            return new CheckboxElement(parent, type, id, node);
        }

        Form.Element.apply(this, arguments);

        this.input = null;
    }

    CheckboxElement.prototype = Object.create(Form.Element.prototype);
    CheckboxElement.prototype.constructor = CheckboxElement;

    /**
     * Returns a string describing this object.
     * @returns {string}
     */
    CheckboxElement.prototype.toString = function() {
        return '[object CheckboxElement]';
    };

    /**
     * Initialises a new instance of this element.
     * @param {Function} done
     */
    CheckboxElement.prototype.init = function(done) {
        var input = $('#' + this.id),
            self = this,
            deferred = $.Deferred(),
            submitselector = 'input[type="submit"]:not([formnovalidate])';

        this.input = input;
        // Call the changed method when this element is changed.
        this.input.change($.proxy(this.changed, this));

        // Only do this if we need to.
        if (input.attr('required')) {
            require(['shezar_form/modernizr'], function (Mod) {
                if (!Mod.input.required) {
                    input.change($.proxy(self.polyFillValidate, self));
                    input.closest('form').find(submitselector).click($.proxy(self.polyFillValidate, self));
                }
                deferred.resolve();
            });
        } else {
            deferred.resolve();
        }

        deferred.done(done);
    };

    /**
     * Returns the value of the checkbox given its selected state
     * @returns {string}
     */
    CheckboxElement.prototype.getValue = function() {
        if (this.input.is(':checked')) {
            return this.input.val();
        }
        return this.input.data('value-unchecked');
    };

    /**
     * Performs any polyfil validation.
     * @param {Event} e
     */
    CheckboxElement.prototype.polyFillValidate = function(e) {
        var input = this.input;
        if (!input.prop('checked')) {
            e.preventDefault();
            if (input.closest('.tf_element').find(ERROR_CONTAINER_SELECTOR).length === 0) {
                require(['core/templates', 'core/str', 'core/config'], function (templates, mdlstrings, mdlconfig) {
                    mdlstrings.get_string('required', 'core').done(function (requiredstring) {
                        var context = {
                            errors_has_items: true,
                            errors: [{message: requiredstring}]
                        };
                        templates.render('shezar_form/validation_errors', context, mdlconfig.theme).done(function (template) {
                            input.parent().prepend(template);
                        });
                    });
                });
            }
        } else if (input.val().trim() !== '') {
            input.closest('.tf_element').find(ERROR_CONTAINER_SELECTOR).remove();
        }
    };

    return CheckboxElement;

});