<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_musudo\phpunit\muform\autocomplete;

use tool_musudo\local\sudoer;
use tool_musudo\muform\autocomplete\sudoer_create_userid;

/**
 * New privileged user autocomplete source tests.
 *
 * @group       MuTMS
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\muform\autocomplete\sudoer_create_userid
 */
final class sudoer_create_userid_test extends \advanced_testcase {
    public function test_source(): void {
        global $DB;
        $this->resetAfterTest();

        $syscontext = \context_system::instance();
        $manager1 = $this->getDataGenerator()->create_user(['firstname' => 'Manager', 'lastname' => '1']);
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        assign_capability('moodle/site:config', CAP_ALLOW, $managerrole->id, $syscontext);
        role_assign($managerrole->id, $manager1->id, $syscontext->id);

        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '1', 'email' => 'user1@example.com']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '2', 'email' => 'user2@example.com']);
        $user3 = $this->getDataGenerator()->create_user(['firstname' => 'User', 'lastname' => '3', 'suspended' => 1]);

        $admin1 = get_admin();
        $admin2 = $this->getDataGenerator()->create_user(['firstname' => 'Admin', 'lastname' => '2']);
        set_config('siteadmins', "$admin1->id,$admin2->id");

        $this->setAdminUser();
        $source = new sudoer_create_userid();
        $this->assertSame([], $source->get_args());
        $this->assertSame(
            [(int)$manager1->id, (int)$user1->id, (int)$user2->id, (int)$user3->id],
            array_keys($source->search('', 50))
        );

        sudoer::create((object)[
            'userid' => $user1->id,
            'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id],
        ]);
        $this->assertSame(
            [(int)$manager1->id, (int)$user2->id, (int)$user3->id],
            array_keys($source->search('', 50))
        );
        $this->assertNull($source->search('', 2));

        $result = $source->search('user2', 50);
        $this->assertSame([(int)$user2->id], array_keys($result));
        $this->assertStringContainsString(fullname($user2), $result[$user2->id]);
        $this->assertStringContainsString($user2->email, $result[$user2->id]);

        $this->assertStringContainsString(fullname($user2), $source->label((string)$user2->id));
        $this->assertNull($source->label((string)$user1->id));
        $this->assertNull($source->label((string)$admin2->id));
        $this->assertNull($source->validate((string)$user2->id));
        $this->assertSame('Suspended user', $source->validate((string)$user3->id));

        $this->setUser($manager1);
        try {
            new sudoer_create_userid();
            $this->fail('Exception expected');
        } catch (\core\exception\moodle_exception $ex) {
            $this->assertSame('Invalid role', $ex->getMessage());
        }
    }
}
