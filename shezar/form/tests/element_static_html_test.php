<?php
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
 * @author Petr Skoda <petr.skoda@shezarlms.com>
 * @package shezar_form
 */

use shezar_form\form\element\static_html,
    shezar_form\model,
    shezar_form\test\test_definition,
    shezar_form\test\test_form;

/**
 * Test for \shezar_form\form\element\static_html class.
 */
class shezar_form_element_static_html_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();
        require_once(__DIR__  . '/fixtures/test_form.php');
        test_form::phpunit_reset();
        $this->resetAfterTest();
    }

    protected function tearDown() {
        test_form::phpunit_reset();
        parent::tearDown();
    }

    public function test_nothing_returned() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var static_html $static_html1 */
                $static_html1 = $model->add(new static_html('somestatic_html1', 'label', 'html'));

                // Test the form field values.
                $testcase->assertSame(null, $static_html1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somestatic_html1' => 'Entered static_html 1',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somestatic_html1' => 'Current static_html 1',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $this->assertSame(array(), $data);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somestatic_html1' => 'Current static_html 1',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
