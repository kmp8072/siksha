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

use shezar_form\form\element\editor,
    shezar_form\model,
    shezar_form\test\test_definition,
    shezar_form\test\test_form;

/**
 * Test for \shezar_form\form\element\editor class.
 */
class shezar_form_element_editor_testcase extends advanced_testcase {
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

    public function test_no_post() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor1 = $model->add(new editor('someeditor1', 'Some editor 1'));
            });
        test_form::phpunit_set_definition($definition);
        test_form::phpunit_set_post_data(null);
        $currentdata = array('someeditor1' => '', 'someeditor1format' => null);
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);
    }

    public function test_submission() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor1 = $model->add(new editor('someeditor1', 'Some editor 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someeditor1' => array('text' => 'lala', 'format' => FORMAT_HTML),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => 'hehe',
            'someeditor1format' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertInstanceOf('stdClass', $files);
        $expected = array(
            'someeditor1' => 'lala',
            'someeditor1format' => (string)FORMAT_HTML,
        );
        $this->assertSame($expected, (array)$data);
        $this->assertSame(array(), (array)$files);
    }

    public function test_required() {
        $this->setAdminUser();
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var editor $editor1 */
                $editor1 = $model->add(new editor('someeditor1', 'Some editor 1'));
                $editor1->set_attribute('required', true);
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'someeditor1' => array('text' => 'lala', 'format' => FORMAT_HTML),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => 'hehe',
            'someeditor1format' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertInstanceOf('stdClass', $files);
        $expected = array(
            'someeditor1' => 'lala',
            'someeditor1format' => (string)FORMAT_HTML,
        );
        $this->assertSame($expected, (array)$data);
        $this->assertSame(array(), (array)$files);

        $postdata = array(
            'someeditor1' => array('text' => '', 'format' => FORMAT_HTML),
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'someeditor1' => 'hehe',
            'someeditor1format' => null
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertNull($data);
        $this->assertNull($files);
    }

    public function test_submission_files() {
        // TODO TL-9422: test submission files.
    }

    public function test_frozen() {
        // TODO TL-9422: test frozen files.
    }
}
