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

use shezar_form\form\element\checkboxes,
    shezar_form\model,
    shezar_form\test\test_definition,
    shezar_form\test\test_form;

/**
 * Test for \shezar_form\form\element\checkboxes class.
 */
class shezar_form_element_checkboxes_testcase extends advanced_testcase {
    protected function setUp() {
        parent::setUp();
        require_once(__DIR__ . '/fixtures/test_form.php');
        test_form::phpunit_reset();
        $this->resetAfterTest();
    }

    protected function tearDown() {
        test_form::phpunit_reset();
        parent::tearDown();
    }

    public function test_no_post() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                /** @var checkboxes $checkboxes2 */
                $checkboxes2 = $model->add(new checkboxes('somecheckboxes2', 'Some checkboxes 2', $options));
                /** @var checkboxes $checkboxes3 */
                $checkboxes3 = $model->add(new checkboxes('somecheckboxes3', 'Some checkboxes 3', $options));
                $checkboxes3->set_frozen(true);
                /** @var checkboxes $checkboxes4 */
                $checkboxes4 = $model->add(new checkboxes('somecheckboxes4', 'Some checkboxes 4', $options));
                $checkboxes4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('r'), $checkboxes1->get_field_value());
                $testcase->assertSame(array(), $checkboxes2->get_field_value());
                $testcase->assertSame(array('g', 'b'), $checkboxes3->get_field_value());
                $testcase->assertSame(array(), $checkboxes4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'somecheckboxes1' => array('r'),
            'somecheckboxes3' => array('g', 'b'),
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                /** @var checkboxes $checkboxes2 */
                $checkboxes2 = $model->add(new checkboxes('somecheckboxes2', 'Some checkboxes 2', $options));
                /** @var checkboxes $checkboxes3 */
                $checkboxes3 = $model->add(new checkboxes('somecheckboxes3', 'Some checkboxes 3', $options));
                $checkboxes3->set_frozen(true);
                /** @var checkboxes $checkboxes4 */
                $checkboxes4 = $model->add(new checkboxes('somecheckboxes4', 'Some checkboxes 4', $options));
                $checkboxes4->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array('r'), $checkboxes1->get_field_value());
                $testcase->assertSame(array('b'), $checkboxes2->get_field_value());
                $testcase->assertSame(array('g', 'b'), $checkboxes3->get_field_value());
                $testcase->assertSame(array(), $checkboxes4->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes2' => array('b'),
            'somecheckboxes3' => array(),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckboxes1' => array('r'),
            'somecheckboxes3' => array('g', 'b'),
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckboxes1' => array('r'),
            'somecheckboxes2' => array('b'),
            'somecheckboxes3' => array('g', 'b'),
            'somecheckboxes4' => null,
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));

                // Test the form field values.
                $testcase->assertSame(array('x'), $checkboxes1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array('x'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                $checkboxes1->set_attribute('required', true);

                // Test the form field values.
                $testcase->assertSame(array(), $checkboxes1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array(),
        );
        $currentdata = array(
            'somecheckboxes1' => array('r'),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                $checkboxes1->set_attribute('required', true);
                $checkboxes1->set_frozen(true);

                // Test the form field values.
                $testcase->assertSame(array(), $checkboxes1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array(),
        );
        $currentdata = array(
            'somecheckboxes1' => array(),
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckboxes1' => array(),
        );
        $this->assertSame($expected, $data);
    }

    public function test_incorrect_current() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('r' => 'Red', 'g' => 'Green', 'b' => 'Blue');

                $testcase->assertDebuggingNotCalled();
                /** @var checkboxes $checkboxes1 */
                $checkboxes1 = $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', $options));
                $testcase->assertDebuggingCalled();

                $testcase->assertDebuggingNotCalled();
                /** @var checkboxes $checkboxes2 */
                $checkboxes2 = $model->add(new checkboxes('somecheckboxes2', 'Some checkboxes 2', $options));
                $testcase->assertDebuggingCalled();

                $testcase->assertDebuggingNotCalled();
                /** @var checkboxes $checkboxes3 */
                $checkboxes3 = $model->add(new checkboxes('somecheckboxes3', 'Some checkboxes 3', $options));
                $checkboxes3->set_frozen(true);
                $testcase->assertDebuggingCalled();

                // Test the form field values.
                $testcase->assertSame(array('b'), $checkboxes1->get_field_value());
                $testcase->assertSame(array('g'), $checkboxes2->get_field_value());
                $testcase->assertSame(array('b'), $checkboxes3->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somecheckboxes1' => array('b'),
            'somecheckboxes3' => array('b'),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somecheckboxes1' => array('x', 'r'),
            'somecheckboxes2' => array('x', 'g'),
            'somecheckboxes3' => array('y', 'b'),
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'somecheckboxes1' => array('b'),
            'somecheckboxes2' => array('g'),
            'somecheckboxes3' => array('b'),
        );
        $this->assertSame($expected, $data);
    }

    public function test_extra_params() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $testcase->assertDebuggingNotCalled();
                $model->add(new checkboxes('somecheckboxes1', 'Some checkboxes 1', array('a' => 'b', 'c' => 'd'), 'xx'));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $form = new test_form();
    }
}
