<?php

namespace Tests\Unit;

use App\Models\TahsinExam;
use InvalidArgumentException;
use Tests\TestCase;

class TahsinExamTest extends TestCase
{
    public function test_non_mardud_grades_pass(): void
    {
        foreach ([
            TahsinExam::GRADE_MUMTAZ,
            TahsinExam::GRADE_JAYYID_JIDDAN,
            TahsinExam::GRADE_JAYYID,
        ] as $grade) {
            $this->assertSame(
                TahsinExam::RESULT_PASSED,
                TahsinExam::resultForGrade($grade)
            );
        }
    }

    public function test_mardud_requires_repeat(): void
    {
        $this->assertSame(
            TahsinExam::RESULT_REPEAT,
            TahsinExam::resultForGrade(TahsinExam::GRADE_MARDUD)
        );
    }

    public function test_unknown_grade_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TahsinExam::resultForGrade('unknown');
    }

    public function test_passed_promotion_recommends_next_book(): void
    {
        $this->assertSame(
            TahsinExam::BOOK_UMMI_2,
            TahsinExam::nextBookFor(
                TahsinExam::TYPE_PROMOTION,
                TahsinExam::BOOK_UMMI_1,
                TahsinExam::RESULT_PASSED
            )
        );
    }

    public function test_semester_and_repeat_results_do_not_advance_book(): void
    {
        $this->assertNull(TahsinExam::nextBookFor(
            TahsinExam::TYPE_SEMESTER,
            TahsinExam::BOOK_UMMI_1,
            TahsinExam::RESULT_PASSED
        ));
        $this->assertNull(TahsinExam::nextBookFor(
            TahsinExam::TYPE_PROMOTION,
            TahsinExam::BOOK_UMMI_1,
            TahsinExam::RESULT_REPEAT
        ));
    }

    public function test_tajwid_is_terminal_promotion_stage(): void
    {
        $this->assertNull(TahsinExam::nextBookFor(
            TahsinExam::TYPE_PROMOTION,
            TahsinExam::BOOK_TAJWID,
            TahsinExam::RESULT_PASSED
        ));
    }
}
