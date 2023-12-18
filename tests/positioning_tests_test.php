<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * local_digital_training_account_services tests.
 *
 * @package    local_digital_training_account_services
 * @copyright  2020 Université de Strasbourg {@link https://unistra.fr}
 * @author  Céline Pervès <cperves@unistra.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_digital_training_account_services;
global $CFG;

require_once(__DIR__.'/../locallib.php');
require_once(__DIR__.'/../externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot.'/local/digital_training_account_services/tests/positioning_mockdatas.php');
require_once($CFG->dirroot.'/local/metadata_tools/tests/mockdatas.php');

use advanced_testcase;
use context_module;
use local_digital_training_account_services\positioning_mockdatas;
use local_digital_training_account_services_tools;
use local_metadata_tools\database\metadata_value;
use local_metadata_tools\mockdatas;
use stdClass;

class positioning_tests_test extends advanced_testcase{

    private $positioningmockdatas;
    private $metadatasmockdatas;
    public function test_get_quiz_infos() {
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $this->positioningmockdatas->pass_tests_as_positioning();
        // At this point only quiz are created and some marked as positioning tests.
        $quizinfos11 = local_digital_training_account_services_tools::get_quiz_infos(
                $this->positioningmockdatas->get_quiz11(),
                $this->positioningmockdatas->get_user2()->id
        );
        $this->check_result_structure($quizinfos11);
        $this->assertEquals($this->positioningmockdatas->get_quiz11()->get_cmid(), $quizinfos11['id']);
        $this->assertEquals($this->positioningmockdatas->get_quiz11()->get_quiz()->name, $quizinfos11['name']);
        $this->assertEquals(null, $quizinfos11['grade']);
        $this->assertEquals('', $quizinfos11['feedback']);
        $this->assertEquals([], $quizinfos11['history']);
        $this->assertEquals(2, $quizinfos11['attemptsinfos']['numberAllowedAttempts']);
        $this->assertEquals(0, $quizinfos11['attemptsinfos']['alreadyMadeAttempts']);
        // Submit an attempt.
        $this->positioningmockdatas->attempt_submit(
            $this->positioningmockdatas->get_quiz11(), $this->positioningmockdatas->get_user2()->id);
        $quizinfos11 = local_digital_training_account_services_tools::get_quiz_infos(
                $this->positioningmockdatas->get_quiz11(),
                $this->positioningmockdatas->get_user2()->id
        );
        $this->assertEmpty($quizinfos11['grade']);
        $this->assertEquals('', $quizinfos11['feedback']);
        $this->assertTrue(is_array($quizinfos11['history']));
        $this->assertCount(1, $quizinfos11['history']);
        $this->positioningmockdatas->attempt_submit(
            $this->positioningmockdatas->get_quiz21(), $this->positioningmockdatas->get_user2()->id);
        $quizinfos21 = local_digital_training_account_services_tools::get_quiz_infos(
                $this->positioningmockdatas->get_quiz21(),
                $this->positioningmockdatas->get_user2()->id
        );
        $this->assertEquals(0.0, $quizinfos21['grade']);
        $this->assertEquals('', $quizinfos21['feedback']);
        $this->assertTrue(is_array($quizinfos21['history']));
        $this->assertCount(1, $quizinfos21['history']);
        $this->assertEquals(2, $quizinfos11['attemptsinfos']['numberAllowedAttempts']);
        $this->assertEquals(1, $quizinfos11['attemptsinfos']['alreadyMadeAttempts']);
        $this->assertTrue($quizinfos11['attemptsinfos']['hasOtherAttempts']);

        // Add a second attempt for quiz11.
        $this->positioningmockdatas->attempt_submit(
            $this->positioningmockdatas->get_quiz11(), $this->positioningmockdatas->get_user2()->id);
        $quizinfos11 = local_digital_training_account_services_tools::get_quiz_infos(
                $this->positioningmockdatas->get_quiz11(),
                $this->positioningmockdatas->get_user2()->id
        );
        $this->assertCount(2, $quizinfos11['history']);

        // Grade quiz => override grave threw gradebook.
        $this->positioningmockdatas->grade_quiz(
            $this->positioningmockdatas->get_quiz11()->get_cm(), $this->positioningmockdatas->get_user2()->id, 50.0, 'comment');
        $this->positioningmockdatas->grade_quiz(
            $this->positioningmockdatas->get_quiz21()->get_cm(), $this->positioningmockdatas->get_user2()->id, 50.0, 'comment');
        $quizinfos11 = local_digital_training_account_services_tools::get_quiz_infos(
                $this->positioningmockdatas->get_quiz11(),
                $this->positioningmockdatas->get_user2()->id
        );
        $this->assertEquals(50.0, $quizinfos11['grade']);
        $this->assertEquals('comment', $quizinfos11['feedback']);
        $this->assertEquals(2, $quizinfos11['attemptsinfos']['numberAllowedAttempts']);
        $this->assertEquals(2, $quizinfos11['attemptsinfos']['alreadyMadeAttempts']);
        $this->assertFalse($quizinfos11['attemptsinfos']['hasOtherAttempts']);
        $quizinfos21 = local_digital_training_account_services_tools::get_quiz_infos(
                $this->positioningmockdatas->get_quiz21(),
                $this->positioningmockdatas->get_user2()->id
        );
        $this->assertEquals(50.0, $quizinfos21['grade']);
        $this->assertEquals('comment', $quizinfos21['feedback']);

    }

    public function test_get_positionning_tests_for_user() {
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $this->positioningmockdatas->pass_tests_as_positioning();
        $user1tests = local_digital_training_account_services_tools::get_positionning_tests_for_user(
                $this->positioningmockdatas->get_user1()->id);
        $this->assertCount(0, $user1tests);
        $user2tests = local_digital_training_account_services_tools::get_positionning_tests_for_user(
                $this->positioningmockdatas->get_user2()->id);
        $this->assertCount(2, $user2tests);
        $this->assertContains((int)$this->positioningmockdatas->get_quiz11()->get_cmid(), array_keys($user2tests));
        $this->assertContains((int)$this->positioningmockdatas->get_quiz21()->get_cmid(), array_keys($user2tests));
        $this->check_result_structure($user2tests[$this->positioningmockdatas->get_quiz11()->get_cmid()]);
        $this->check_result_structure($user2tests[$this->positioningmockdatas->get_quiz21()->get_cmid()]);

    }

    public function test_get_metadatas() {
        $this->setup_datas();
        $this->positioningmockdatas->create_courses();
        $this->positioningmockdatas->enrol_users();
        $this->positioningmockdatas->create_tests();
        $this->positioningmockdatas->pass_tests_as_positioning();
        list($metadatafieldinstitution, $metadatafielddomain) = $this->create_metadatas_fields();
        $this->create_metadatas_values(
            $this->positioningmockdatas->get_quiz11()->get_cmid(), $metadatafieldinstitution, 'L1');
        $this->create_metadatas_values(
            $this->positioningmockdatas->get_quiz11()->get_cmid(), $metadatafielddomain, 'domain1;domain2;domain3');
        get_log_manager(true); // Trigger all events.
        $quiz11context =  context_module::instance($this->positioningmockdatas->get_quiz11()->get_cmid());
        $quiz21context =  context_module::instance($this->positioningmockdatas->get_quiz21()->get_cmid());
        $metadatas = local_digital_training_account_services_tools::get_metadatas_values(
            $quiz11context->id, 'eole', array(), ['eolepositioning']);
        $this->check_metatadas_result_structure($metadatas);
        $this->assertCount(2, $metadatas['metadatas']);
        foreach ($metadatas['metadatas'] as $metadata) {
            $this->assertTrue(is_array($metadata));
            $this->assertArrayHasKey('fieldId', $metadata);
            $this->assertArrayHasKey('fieldName', $metadata);
            $this->assertArrayHasKey('value', $metadata);
            $this->assertTrue($metadata['fieldName'] == 'eoleinstitution' || $metadata['fieldName'] == 'eoledomain');
            $this->assertTrue($metadata['value'] == 'L1' || $metadata['value'] == 'domain1;domain2;domain3');
        }

    }


    private function create_metadatas_values($cmid, $metadatafield, $value) {
        $metadatavalueinstitution = new metadata_value;
        $data = new  stdClass();
        $data->instanceid = $cmid;
        $data->fieldid = $metadatafield->get_id();
        $data->data = $value;
        $metadatavalueinstitution->set_datas($data);
        $metadatavalueinstitution->save_datas();
        return $metadatavalueinstitution->get_id();
    }

    private function create_metadatas_fields() {
        $metadatacategory = $this->metadatasmockdatas->create_category();
        $metadatafieldinstitution = $this->metadatasmockdatas->create_field(
            'text', 'eoleinstitution', $metadatacategory->get_id(), 1);
        $metadatafielddomain = $this->metadatasmockdatas->create_field('text', 'eoledomain', $metadatacategory->get_id(), 2);
        return array($metadatafieldinstitution, $metadatafielddomain);
    }

    private function setup_datas() {
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
        $this->setup_mockdatas();
        $this->setAdminUser();
        $this->positioningmockdatas->setup_users();
    }

    private function setup_mockdatas() {
        $this->positioningmockdatas = new positioning_mockdatas($this->getDataGenerator());
        $this->metadatasmockdatas = new mockdatas($this->getDataGenerator());
    }

    /**
     * @param array $quizinfoskeys
     * @param array $quizinfos
     */
    private function check_result_structure($quizinfos) {
        $quizinfoskeys = array_keys($quizinfos);
        $this->assertContains('id', $quizinfoskeys);
        $this->assertContains('name', $quizinfoskeys);
        $this->assertContains('grade', $quizinfoskeys);
        $this->assertContains('feedback', $quizinfoskeys);
        $this->assertContains('history', $quizinfoskeys);
        $this->assertTrue(is_array($quizinfos['history']));
        $this->assertContains('attemptsinfos', $quizinfoskeys);
        $this->assertTrue(is_array($quizinfos['attemptsinfos']));
        $attemptsinfoskeys = array_keys($quizinfos['attemptsinfos']);
        $this->assertContains('numberAllowedAttempts', $attemptsinfoskeys);
        $this->assertContains('alreadyMadeAttempts', $attemptsinfoskeys);
        $this->assertContains('hasOtherAttempts', $attemptsinfoskeys);
        $this->assertTrue($quizinfos['attemptsinfos']['hasOtherAttempts']);
    }

    private function  check_metatadas_result_structure($metadatasvalues) {
        $this->assertContains('metadatas', array_keys($metadatasvalues));
        $this->assertTrue(is_array($metadatasvalues['metadatas']));
    }
}