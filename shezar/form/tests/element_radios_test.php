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

use shezar_form\form\element\radios,
    shezar_form\model,
    shezar_form\test\test_definition,
    shezar_form\test\test_form;

/**
 * Test for \shezar_form\form\element\radios class.
 */
class shezar_form_element_radios_testcase extends advanced_testcase {
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
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                /** @var radios $radios3 */
                $radios3 = $model->add(new radios('someradios3', 'Some radios 3', $options));
                $radios3->set_frozen(true);
                /** @var radios $radios4 */
                $radios4 = $model->add(new radios('someradios4', 'Some radios 4', $options));
                $radios4->set_frozen(true);
                /** @var radios $radios5 */
                $radios5 = $model->add(new radios('someradios5', 'Some radios 5', $options));

                // Test the form field values.
                $testcase->assertSame('n', $radios1->get_field_value());
                $testcase->assertSame('n', $radios2->get_field_value());
                $testcase->assertSame('n', $radios3->get_field_value());
                $testcase->assertSame('y', $radios4->get_field_value());
                $testcase->assertSame(null, $radios5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(null);
        $currentdata = array(
            'someradios1' => 'n',
            'someradios2' => 'n',
            'someradios3' => 'n',
            'someradios4' => 'y',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_submission() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                /** @var radios $radios3 */
                $radios3 = $model->add(new radios('someradios3', 'Some radios 3', $options));
                $radios3->set_frozen(true);
                /** @var radios $radios4 */
                $radios4 = $model->add(new radios('someradios4', 'Some radios 4', $options));
                $radios4->set_frozen(true);
                /** @var radios $radios5 */
                $radios5 = $model->add(new radios('someradios5', 'Some radios 5', $options));

                // Test the form field values.
                $testcase->assertSame('n', $radios1->get_field_value());
                $testcase->assertSame('y', $radios2->get_field_value());
                $testcase->assertSame('y', $radios3->get_field_value());
                $testcase->assertSame(null, $radios4->get_field_value());
                $testcase->assertSame('n', $radios5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => 'n',
            'someradios2' => 'y',
            'someradios3' => 'n',
            'someradios4' => 'xxxxx',
            'someradios5' => 'n',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someradios1' => 'n',
            'someradios2' => 'n',
            'someradios3' => 'y',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someradios1' => 'n',
            'someradios2' => 'y',
            'someradios3' => 'y',
            'someradios4' => null,
            'someradios5' => 'n',
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_current_normalisation() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array(1 => 'Yes', 0 => 'No', '' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                /** @var radios $radios3 */
                $radios3 = $model->add(new radios('someradios3', 'Some radios 3', $options));
                $radios3->set_frozen(true);
                /** @var radios $radios4 */
                $radios4 = $model->add(new radios('someradios4', 'Some radios 4', $options));
                $radios4->set_frozen(true);
                /** @var radios $radios5 */
                $radios5 = $model->add(new radios('someradios5', 'Some radios 5', $options));

                // Test the form field values.
                $testcase->assertSame('0', $radios1->get_field_value());
                $testcase->assertSame('1', $radios2->get_field_value());
                $testcase->assertSame('1', $radios3->get_field_value());
                $testcase->assertSame(null, $radios4->get_field_value());
                $testcase->assertSame('', $radios5->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => '0',
            'someradios2' => '1',
            'someradios3' => '',
            'someradios4' => '1',
            'someradios5' => '',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someradios1' => 0,
            'someradios2' => 0,
            'someradios3' => 1,
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someradios1' => '0',
            'someradios2' => '1',
            'someradios3' => '1',
            'someradios4' => null,
            'someradios5' => '',
        );
        $this->assertSame($expected, $data);
    }

    public function test_submission_error() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));

                // Test the form field values.
                $testcase->assertSame('xxx', $radios1->get_field_value());
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => 'xxx',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_required() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                /** @var radios $radios1 */
                $radios1 = $model->add(new radios('someradios1', 'Some radios 1', $options));
                $radios1->set_attribute('required', true);
                /** @var radios $radios2 */
                $radios2 = $model->add(new radios('someradios2', 'Some radios 2', $options));
                $radios2->set_frozen(true);
                $radios2->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someradios1' => 'y',
            'someradios2' => 'y',
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someradios1' => 'n',
        );
        $form = new test_form($currentdata);
        $data = (array)$form->get_data();
        $expected = array(
            'someradios1' => 'y',
            'someradios2' => null,
        );
        $this->assertSame($expected, $data);

        $postdata = array();
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form(null);
        $data = $form->get_data();
        $this->assertNull($data);
    }

    public function test_incorrect_current() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $options = array('y' => 'Yes', 'n' => 'No', 'm' => 'Maybe');
                $testcase->assertDebuggingNotCalled();
                $model->add(new radios('someradios1', 'Some radios 1', $options));
                $testcase->assertDebuggingCalled();
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array());
        $currentdata = array(
            'someradios1' => 'xx',
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $this->assertNull($data);
    }
}
