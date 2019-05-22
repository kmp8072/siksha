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
 * @author Brian Barnes <brian.barnes@shezarlms.com>
 * @package shezar_form
 */

define(['jquery'], function ($) {

    var ERROR_CONTAINER_CLASS = 'shezar_form-error-container',
        ERROR_CONTAINER_SELECTOR = '.'+ERROR_CONTAINER_CLASS;

    return {
        init: function (id) {
            (function (id) {
                // use closure as otherwise it would only validate the last element.
                var element = $("#" + id);

                function validate(e) {
                    if (element.val().trim() === '') {
                        e.preventDefault();
                        if (!validate.added) {
                            validate.added = true;
                            require(['core/templates', 'core/str', 'core/config'], function (templates, mdlstrings, mdlconfig) {
                                mdlstrings.get_string('required','core').done(function (requiredstring) {
                                    var context = {errors_has_items: true, errors: [{message: requiredstring}]};
                                    templates.render('shezar_form/validation_errors', context, mdlconfig.theme).done(function (template) {
                                        element.parent().prepend(template);
                                    });
                                });
                            });
                        }
                    } else if (element.val().trim() !== '') {
                        validate.added = false;
                        element.closest('.tf_element').find(ERROR_CONTAINER_SELECTOR).remove();
                    }
                }

                validate.added = false;
                element.blur(validate);
                element.closest('form').find('input[type="submit"]:not([formnovalidate])').click(validate);

            })(id);
        }
    };
});