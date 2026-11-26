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
// phpcs:disable moodle.Files.LineLength.TooLong
// phpcs:disable moodle.Commenting.DocblockDescription.Missing

namespace tool_musudo\phpunit\mureport\system_report;

use tool_musudo\local\sudoer;
use tool_musudo\mureport\system_report\sudoers;
use tool_mureport\mureport\request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Privileged users report tests.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(sudoers::class)]
#[CoversClass(\tool_musudo\mureport\entity\sudoer::class)]
#[Group('MuTMS')]
final class sudoers_test extends \advanced_testcase {
    #[\Override]
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Returns exported rows.
     *
     * @param request $request
     * @return array
     */
    private function get_rows(request $request = new request([])): array {
        $report = sudoers::make_instance();
        $report->require_access();
        return $report->get_query()->get_page($request)->export_rows();
    }

    public function test_rows(): void {
        global $DB;
        $syscontext = \context_system::instance();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        $user1 = $this->getDataGenerator()->create_user(['firstname' => 'Anna', 'lastname' => 'Alpha']);
        $user2 = $this->getDataGenerator()->create_user(['firstname' => 'Bob', 'lastname' => 'Beta', 'suspended' => 1]);
        $this->setAdminUser();
        sudoer::create((object)['userid' => $user1->id, 'note' => 'Helpdesk', 'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id]]);
        sudoer::create((object)['userid' => $user2->id, 'note' => 'Auditor', 'mfarequired' => 1, 'contextid' => [$syscontext->id],
            'roleid' => [$managerrole->id]]);

        $rows = $this->get_rows();
        $this->assertSame(['Anna Alpha', 'Bob Beta'], array_map(fn(array $row): string => strip_tags($row['cells']['auser:fullname']), $rows));
        $this->assertSame('Helpdesk', $rows[0]['cells']['sudoer:note']);
        $this->assertStringContainsString('Manager', $rows[0]['cells']['sudoer:privileges']);
        $this->assertSame([false, true], array_column($rows, 'muted'));
        $this->assertSame(['update', 'delete'], array_column($rows[0]['actions']['sudoer:actions'], 'name'));
        $rows = $this->get_rows(new request([], null, 0, null, 'auditor'));
        $this->assertCount(1, $rows);

        $this->setUser($user1);
        $this->expectException(\core\exception\required_capability_exception::class);
        $this->get_rows();
    }
}
