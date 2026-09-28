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

use tool_musudo\muform\autocomplete\privilege_category;
use tool_musudo\muform\autocomplete\privilege_course;
use tool_musudo\muform\autocomplete\privilege_tenant;

/**
 * Privilege context autocomplete source tests.
 *
 * @group       MuTMS
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_musudo\muform\autocomplete\privilege_category
 * @covers \tool_musudo\muform\autocomplete\privilege_course
 * @covers \tool_musudo\muform\autocomplete\privilege_tenant
 */
final class privilege_context_test extends \advanced_testcase {
    public function test_category(): void {
        $this->resetAfterTest();

        $category1 = $this->getDataGenerator()->create_category(['name' => 'Kategorie 1', 'idnumber' => 'KAT1']);
        $category2 = $this->getDataGenerator()->create_category(['name' => 'Kategorie 2', 'parent' => $category1->id]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $course = $this->getDataGenerator()->create_course();

        $this->setAdminUser();
        $source = new privilege_category();
        $this->assertSame([], $source->get_args());
        $result = $source->search('Kategorie', 50);
        $this->assertSame([$catcontext1->id => 'Kategorie 1', $catcontext2->id => 'Kategorie 1 / Kategorie 2'], $result);
        $this->assertSame([$catcontext1->id => 'Kategorie 1'], $source->search('KAT1', 50));
        $this->assertNull($source->search('', 1));
        $this->assertSame('Kategorie 1 / Kategorie 2', $source->label((string)$catcontext2->id));
        $this->assertNull($source->label((string)\context_system::instance()->id));
        $this->assertNull($source->label((string)\context_course::instance($course->id)->id));
        $this->assertNull($source->label('x'));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\moodle_exception::class);
        new privilege_category();
    }

    public function test_course(): void {
        $this->resetAfterTest();

        $course1 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 1', 'shortname' => 'K1']);
        $course2 = $this->getDataGenerator()->create_course(['fullname' => 'Kurz 2', 'idnumber' => 'KID2']);
        $context1 = \context_course::instance($course1->id);
        $context2 = \context_course::instance($course2->id);
        $sitecontext = \context_course::instance(SITEID);

        $this->setAdminUser();
        $source = new privilege_course();
        $this->assertSame([], $source->get_args());
        $this->assertSame([$context1->id => 'Kurz 1', $context2->id => 'Kurz 2'], $source->search('Kurz', 50));
        $this->assertSame([$context2->id => 'Kurz 2'], $source->search('KID2', 50));
        $this->assertCount(3, $source->search('', 50));
        $this->assertNull($source->search('', 2));
        $this->assertSame('Kurz 1', $source->label((string)$context1->id));
        $this->assertNotNull($source->label((string)$sitecontext->id));
        $this->assertNull($source->label((string)\context_system::instance()->id));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\moodle_exception::class);
        new privilege_course();
    }

    public function test_tenant(): void {
        if (!\tool_mulib\local\mulib::is_mutenancy_available()) {
            $this->markTestSkipped('tenant support not available');
        }
        $this->resetAfterTest();
        \tool_mutenancy\local\tenancy::activate();

        /** @var \tool_mutenancy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_mutenancy');
        $tenant1 = $generator->create_tenant(['name' => 'Firma 1', 'idnumber' => 'f1']);
        $tenant2 = $generator->create_tenant(['name' => 'Firma 2', 'idnumber' => 'f2']);
        $context1 = \core\context\tenant::instance($tenant1->id);
        $context2 = \core\context\tenant::instance($tenant2->id);

        $this->setAdminUser();
        $source = new privilege_tenant();
        $this->assertSame([], $source->get_args());
        $this->assertSame([$context1->id => 'Firma 1', $context2->id => 'Firma 2'], $source->search('Firma', 50));
        $this->assertSame([$context2->id => 'Firma 2'], $source->search('f2', 50));
        $this->assertNull($source->search('', 1));
        $this->assertSame('Firma 1', $source->label((string)$context1->id));
        $this->assertNull($source->label((string)\context_system::instance()->id));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\core\exception\moodle_exception::class);
        new privilege_tenant();
    }
}
