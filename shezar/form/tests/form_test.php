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

use shezar_form\file_area,
    shezar_form\form\group\buttons,
    shezar_form\form\element\action_button,
    shezar_form\form\element\filemanager,
    shezar_form\form\element\filepicker,
    shezar_form\form\element\text,
    shezar_form\model,
    shezar_form\test\test_definition,
    shezar_form\test\test_form;

/**
 * Test for \shezar_form\form class.
 */
class shezar_form_form_testcase extends advanced_testcase {
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

    public function test_test_form() {
        test_form::phpunit_set_post_data(array('a' => 'b', 'c' => 'd'));
        $expected = array(
            'a' => 'b',
            'c' => 'd',
            'sesskey' => sesskey(),
            '___tf_formclass' => 'shezar_form\test\test_form',
            '___tf_idsuffix' => 'shezar_form_test_test_form',
        );
        $this->assertSame($expected, $_POST);

        test_form::phpunit_set_post_data(array('a' => 'b', 'c' => 'd'), 'lala');
        $expected = array(
            'a' => 'b',
            'c' => 'd',
            'sesskey' => sesskey(),
            '___tf_formclass' => 'shezar_form\test\test_form',
            '___tf_idsuffix' => 'lala',
        );
        $this->assertSame($expected, $_POST);

        test_form::phpunit_set_post_data(null);
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                debugging('defined', DEBUG_ALL);
            });
        test_form::phpunit_set_definition($definition);

        $this->assertDebuggingNotCalled();
        $form = new test_form();
        $this->assertDebuggingCalled('defined', DEBUG_ALL);

        test_form::phpunit_set_definition(null);
        $form = new test_form();
        $this->assertDebuggingNotCalled();
    }

    public function test_get_form_controller() {
        $this->assertNull(test_form::get_form_controller());
    }

    public function test_get_parameters() {
        $form = new test_form();
        $this->assertSame(array(), $form->get_parameters());

        $parameters = array('a' => 'b');
        $form = new test_form(null, $parameters);
        $this->assertSame($parameters, $form->get_parameters());

        $parameters = new stdClass();
        $parameters->a = 'b';
        $form = new test_form(null, $parameters);
        $this->assertSame((array)$parameters, $form->get_parameters());
    }

    public function test_get_action_url() {
        global $FULLME;

        $FULLME = 'http://www.example.com/myform.php?xx=yy';
        $form = new test_form();
        $url = $form->get_action_url();
        $this->assertInstanceOf('moodle_url', $url);
        $this->assertSame('http://www.example.com/myform.php', $url->out());
    }

    public function test_is_cancelled() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new text('sometext', 'Some text', PARAM_TEXT));
                /** @var buttons $actionbuttonsgroup */
                $actionbuttonsgroup = $model->add_action_buttons();
                $actionbuttonsgroup->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array('cancelbutton' => 'xxx'));
        $form = new test_form();
        $this->assertTrue($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        test_form::phpunit_set_post_data(array('cancelbutton' => 'xxx'), '666');
        $form = new test_form(null, null, '666');
        $this->assertTrue($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Cancelling has the highest priority.
        $postdata = array(
            'cancelbutton' => 'xxx',
            'submitbutton' => 'yyy',
            'reloadbutton' => 'zzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $this->assertTrue($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong class in submission.
        test_form::phpunit_set_post_data(array('cancelbutton' => 'xxx'));
        $_POST['___tf_formclass'] = 'shezar_form\test\test_formxx';
        $form = new test_form();
        $this->assertFalse($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong sesskey in submission.
        test_form::phpunit_set_post_data(array('cancelbutton' => 'xxx'));
        $_POST['sesskey'] = 'xxx';
        $form = new test_form();
        $this->assertFalse($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong suffix in submission.
        test_form::phpunit_set_post_data(array('cancelbutton' => 'xxx'), '666');
        $form = new test_form(null, null, '667');
        $this->assertFalse($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        test_form::phpunit_set_post_data(array());
        $form = new test_form();
        $this->assertFalse($form->is_cancelled());

        test_form::phpunit_set_post_data(array('submitbutton' => 'yyy'));
        $form = new test_form();
        $this->assertFalse($form->is_cancelled());

        test_form::phpunit_set_post_data(array('reloadbutton' => 'zzz'));
        $form = new test_form();
        $this->assertFalse($form->is_cancelled());
    }

    public function test_is_reloaded() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new text('sometext', 'Some text', PARAM_TEXT));
                /** @var buttons $actionbuttonsgroup */
                $actionbuttonsgroup = $model->add_action_buttons();
                $actionbuttonsgroup->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array('reloadbutton' => 'xxx'));
        $form = new test_form();
        $this->assertTrue($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        test_form::phpunit_set_post_data(array('reloadbutton' => 'xxx'), '666');
        $form = new test_form(null, null, '666');
        $this->assertTrue($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Cancelling has the highest priority.
        $postdata = array(
            'cancelbutton' => 'xxx',
            'submitbutton' => 'yyy',
            'reloadbutton' => 'zzz',
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $this->assertTrue($form->is_cancelled());
        $this->assertFalse($form->is_reloaded());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong class in submission.
        test_form::phpunit_set_post_data(array('reloadbutton' => 'xxx'));
        $_POST['___tf_formclass'] = 'shezar_form\test\test_formxx';
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong sesskey in submission.
        test_form::phpunit_set_post_data(array('reloadbutton' => 'xxx'));
        $_POST['sesskey'] = 'xxxx';
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong suffix in submission.
        test_form::phpunit_set_post_data(array('reloadbutton' => 'xxx'), '666');
        $form = new test_form(null, null, '667');
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        test_form::phpunit_set_post_data(array());
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());

        test_form::phpunit_set_post_data(array('submitbutton' => 'yyy'));
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());

        test_form::phpunit_set_post_data(array('cancelbutton' => 'zzz'));
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());

        // Test JS reload trick.
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
            });
        test_form::phpunit_set_definition($definition);

        test_form::phpunit_set_post_data(array('___tf_reload' => '1'));
        $form = new test_form();
        $this->assertTrue($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

    }

    public function test_get_data() {
        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new text('sometext', 'Some text', PARAM_TEXT));
                /** @var buttons $actionbuttonsgroup */
                $actionbuttonsgroup = $model->add_action_buttons();
                $actionbuttonsgroup->add(new action_button('reloadbutton', 'Reload', action_button::TYPE_RELOAD));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'sometext' => 'lala',
            'submitbutton' => 'xxx',
        );
        test_form::phpunit_set_post_data($postdata);
        $data = new stdClass();
        $files = new stdClass();
        $data->sometext = 'lala';
        $data->submitbutton = '1';
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertEquals($data, $form->get_data());
        $this->assertEquals($files, $form->get_files());

        // Submit button is optional.
        $postdata = array(
            'sometext' => 'lala',
        );
        test_form::phpunit_set_post_data($postdata);
        $data = new stdClass();
        $files = new stdClass();
        $data->sometext = 'lala';
        $data->submitbutton = '0';
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertEquals($data, $form->get_data());
        $this->assertEquals($files, $form->get_files());

        $postdata = array(
            'sometext' => 'lala',
        );
        test_form::phpunit_set_post_data($postdata, '666');
        $data = new stdClass();
        $files = new stdClass();
        $data->sometext = 'lala';
        $data->submitbutton = '0';
        $form = new test_form(null, null, '666');
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertEquals($data, $form->get_data());
        $this->assertEquals($files, $form->get_files());

        // Different suffix.
        $postdata = array(
            'sometext' => 'lala',
        );
        test_form::phpunit_set_post_data($postdata, '666');
        $form = new test_form(null, null, '667');
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong sesskey.
        $postdata = array(
            'sometext' => 'lala',
        );
        test_form::phpunit_set_post_data($postdata);
        $_POST['sesskey'] = sesskey() . 'xx';
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());

        // Wrong class.
        $postdata = array(
            'sometext' => 'lala',
        );
        test_form::phpunit_set_post_data($postdata);
        $_POST['___tf_formclass'] = 'shezar_form\test\test_form' . 'xx';
        $form = new test_form();
        $this->assertFalse($form->is_reloaded());
        $this->assertFalse($form->is_cancelled());
        $this->assertNull($form->get_data());
        $this->assertNull($form->get_files());
    }

    public function test_get_files() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertEquals($draftfile, $files->somefilepicker1[0]);
    }

    public function test_update_file_area() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus2.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'haha');
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'test',
            'itemid' => 6,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $file = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                /** @var filemanager $filemanager1 */
                $filemanager1 = $model->add(new filemanager('somefilemanager1', 'Some filemanager 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilemanager1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $currentdata = array(
            'somefilemanager1' => new file_area($usercontext, 'user', 'test', 6),
        );
        $form = new test_form($currentdata);
        $data = $form->get_data();
        $files = $form->get_files();
        $this->assertInstanceOf('stdClass', $data);
        $this->assertSame(array(), (array)$data);
        $this->assertInstanceOf('stdClass', $files);
        $this->assertCount(1, (array)$files);
        $this->assertCount(2, $files->somefilemanager1);
        $this->assertEquals('.', $files->somefilemanager1[0]->get_filename());
        $this->assertEquals($draftfile, $files->somefilemanager1[1]);

        $form->update_file_area('somefilemanager1');
        $this->assertTrue($fs->file_exists($usercontext->id, 'user', 'test', 6, '/', 'pokus2.txt'));
        $this->assertFalse($fs->file_exists($usercontext->id, 'user', 'test', 6, '/', 'pokus.txt'));
    }

    public function test_save_file() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $tempfile = make_request_directory(true) . '/test.txt';
        $result = $form->save_file('somefilepicker1', $tempfile);
        $this->assertTrue($result);
        $this->assertSame(6, filesize($tempfile));

        // No override by default.
        unlink($tempfile);
        file_put_contents($tempfile, 'xx');
        $this->assertSame(2, filesize($tempfile));
        $result = $form->save_file('somefilepicker1', $tempfile);
        $this->assertFalse($result);
        $this->assertSame(2, filesize($tempfile));

        // Override file.
        $result = $form->save_file('somefilepicker1', $tempfile, true);
        $this->assertTrue($result);
        $this->assertSame(6, filesize($tempfile));

        // Incorrect element name.
        unlink($tempfile);
        $result = $form->save_file('somefilepicker2', $tempfile);
        $this->assertFalse($result);
        $this->assertFalse(file_exists($tempfile));
    }

    public function test_save_temp_file() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $tempfile = $form->save_temp_file('somefilepicker1');
        $this->assertTrue(file_exists($tempfile));
        $this->assertSame(6, filesize($tempfile));
        unlink($tempfile);

        $tempfile = $form->save_temp_file('somefilepicker2');
        $this->assertFalse($tempfile);
    }

    public function test_save_stored_file() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $storedfile = $form->save_stored_file('somefilepicker1', $usercontext->id, 'user', 'test', '1', '/', $draftfile->get_filename());
        $this->assertInstanceOf('stored_file', $storedfile);
        $this->assertSame($draftfile->get_content(), $storedfile->get_content());
        $this->assertSame($draftfile->get_filename(), $storedfile->get_filename());

        $storedfile = $form->save_stored_file('somefilepicker2', $usercontext->id, 'user', 'test', '1', '/', 'xx.txt');
        $this->assertFalse($storedfile);
    }

    public function test_get_file_content() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $content = $form->get_file_content('somefilepicker1');
        $this->assertSame($draftfile->get_content(), $content);

        $content = $form->get_file_content('somefilepicker2');
        $this->assertFalse($content);
    }

    public function test_get_new_filename() {
        global $USER;
        $this->setAdminUser();
        $usercontext = \context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $record = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filepath' => '/',
            'filename' => 'pokus.txt',
        );
        $draftfile = $fs->create_file_from_string($record, 'lalala');

        $definition = new test_definition($this,
            function (model $model, advanced_testcase $testcase) {
                $model->add(new filepicker('somefilepicker1', 'Some filepicker 1'));
            });
        test_form::phpunit_set_definition($definition);

        $postdata = array(
            'somefilepicker1' => $draftitemid,
        );
        test_form::phpunit_set_post_data($postdata);
        $form = new test_form();
        $this->assertDebuggingNotCalled();
        $filename = $form->get_new_filename('somefilepicker1');
        $this->assertDebuggingCalled();
        $this->assertSame($draftfile->get_filename(), $filename);
    }

    public function test_get_template() {
        $form = new test_form();
        $this->assertSame('shezar_form/form', $form->get_template());
    }

    public function test_export_for_template() {
        global $OUTPUT, $FULLME;

        $FULLME = 'http://www.example.com/myform.php?xx=yy';
        $form = new test_form();
        $result = $form->export_for_template($OUTPUT);
        $this->assertInternalType('array', $result);

        $this->assertSame('tf_fid_shezar_form_test_test_form', $result['formid']);
        $this->assertSame('http://www.example.com/myform.php', $result['action']);
        $this->assertSame('shezar_form__test__test_form', $result['cssclass']);
        $this->assertInternalType('array', $result['items']);
        $this->assertFalse($result['failedsubmission']);
        $this->assertFalse($result['requiredpresent']);
        $this->assertFalse($result['errors_has_items']);
        $this->assertSame(array(), $result['errors']);
        $this->assertFalse($result['helphtml']);
    }

    public function test_render() {
        $form = new test_form();
        $result = $form->render();
        $this->assertContains('<form', $result);
    }

    public function test_legacy() {
        $form = new test_form();

        ob_start();
        $form->display();
        $result = ob_get_contents();
        ob_end_clean();
        $this->assertDebuggingCalled();
        $this->assertContains('<form', $result);

        $form->focus();
        $this->assertDebuggingCalled();

        try {
            $form->no_submit_button_pressed();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }

        try {
            $form->get_submitted_data();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        try {
            $form->repeat_elements();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        try {
            $form->add_checkbox_controller();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        try {
            $form->save_files();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        try {
            $form->get_form_identifier();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        try {
            $form->set_data();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        try {
            $form->definition_after_data();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
        try {
            $form->xxxxx();
        } catch (moodle_exception $e) {
            $this->assertInstanceOf('coding_exception', $e);
        }
    }
}
