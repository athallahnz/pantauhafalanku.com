<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TahsinExam extends Model
{
    use HasFactory, LogsActivity;

    public const TYPE_PROMOTION = 'promotion';

    public const TYPE_SEMESTER = 'semester';

    public const RESULT_PASSED = 'passed';

    public const RESULT_REPEAT = 'repeat';

    public const BOOK_UMMI_1 = 'ummi_1';

    public const BOOK_UMMI_2 = 'ummi_2';

    public const BOOK_UMMI_3 = 'ummi_3';

    public const BOOK_GHARIB_1 = 'gharib_1';

    public const BOOK_GHARIB_2 = 'gharib_2';

    public const BOOK_TAJWID = 'tajwid';

    public const GRADE_MUMTAZ = 'mumtaz';

    public const GRADE_JAYYID_JIDDAN = 'jayyid_jiddan';

    public const GRADE_JAYYID = 'jayyid';

    public const GRADE_MARDUD = 'mardud';

    protected $fillable = [
        'santri_id',
        'musyrif_id',
        'semester_id',
        'tanggal',
        'exam_type',
        'buku',
        'attempt_number',
        'grade_label',
        'result',
        'next_book',
        'submission_uuid',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'attempt_number' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $exam): void {
            $exam->submission_uuid ??= (string) Str::uuid();
            $exam->result = self::resultForGrade($exam->grade_label);
            $exam->next_book = self::nextBookFor(
                $exam->exam_type,
                $exam->buku,
                $exam->result
            );
        });

        static::updating(function (self $exam): void {
            if ($exam->isDirty('grade_label')) {
                $exam->result = self::resultForGrade($exam->grade_label);
                $exam->next_book = self::nextBookFor(
                    $exam->exam_type,
                    $exam->buku,
                    $exam->result
                );
            }
        });
    }

    /** @return array<int, string> */
    public static function examTypes(): array
    {
        return [self::TYPE_PROMOTION, self::TYPE_SEMESTER];
    }

    /** @return array<string, string> */
    public static function examTypeLabels(): array
    {
        return [
            self::TYPE_PROMOTION => 'Kenaikan Buku/Jilid',
            self::TYPE_SEMESTER => 'Ujian Semester',
        ];
    }

    /** @return array<int, string> */
    public static function books(): array
    {
        return array_keys(self::bookLabels());
    }

    /** @return array<string, string> */
    public static function bookLabels(): array
    {
        return [
            self::BOOK_UMMI_1 => 'Ummi 1',
            self::BOOK_UMMI_2 => 'Ummi 2',
            self::BOOK_UMMI_3 => 'Ummi 3',
            self::BOOK_GHARIB_1 => 'Gharib 1',
            self::BOOK_GHARIB_2 => 'Gharib 2',
            self::BOOK_TAJWID => 'Tajwid',
        ];
    }

    /** @return array<int, string> */
    public static function grades(): array
    {
        return array_keys(self::gradeLabels());
    }

    /** @return array<string, string> */
    public static function gradeLabels(): array
    {
        return [
            self::GRADE_MUMTAZ => 'Mumtaz',
            self::GRADE_JAYYID_JIDDAN => 'Jayyid Jiddan',
            self::GRADE_JAYYID => 'Jayyid',
            self::GRADE_MARDUD => 'Mardud',
        ];
    }

    public static function resultForGrade(?string $grade): string
    {
        return match ($grade) {
            self::GRADE_MUMTAZ,
            self::GRADE_JAYYID_JIDDAN,
            self::GRADE_JAYYID => self::RESULT_PASSED,
            self::GRADE_MARDUD => self::RESULT_REPEAT,
            default => throw new InvalidArgumentException(
                'Label nilai Ujian Tahsin tidak valid.'
            ),
        };
    }

    public static function nextBookFor(
        ?string $examType,
        ?string $book,
        ?string $result
    ): ?string {
        if (
            $examType !== self::TYPE_PROMOTION
            || $result !== self::RESULT_PASSED
        ) {
            return null;
        }

        $books = self::books();
        $index = array_search($book, $books, true);

        if ($index === false) {
            return null;
        }

        return $books[$index + 1] ?? null;
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function musyrif(): BelongsTo
    {
        return $this->belongsTo(Musyrif::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
