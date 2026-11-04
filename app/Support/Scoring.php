<?php

namespace App\Support;

use App\Models\Question;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;

class Scoring
{
    /**
     * One row per participant, ranked by total score desc, then total time used asc.
     * Scores are computed live so admin changes to keys/points apply retroactively.
     */
    public static function ranking(): Collection
    {
        $sections = Section::orderBy('sort')->get();
        $keys = Question::get(['id', 'section_id', 'answer'])->groupBy('section_id');

        $rows = User::where('is_admin', false)
            ->with(['attempts.answers', 'attempts.section'])
            ->get()
            ->map(function (User $user) use ($sections, $keys) {
                $row = ['user' => $user, 'sections' => [], 'total' => 0.0, 'seconds' => 0, 'finished' => true];

                foreach ($sections as $section) {
                    $attempt = $user->attempts->firstWhere('section_id', $section->id);
                    $attempt?->closeIfExpired(); // participant may have left before time ran out
                    $sectionKeys = ($keys[$section->id] ?? collect())->pluck('answer', 'id');
                    $choices = $attempt ? $attempt->answers->pluck('choice', 'question_id') : collect();

                    $correct = $sectionKeys->filter(fn ($key, $id) => ($choices[$id] ?? null) === $key)->count();
                    $answered = $choices->intersectByKeys($sectionKeys)->count();
                    $wrong = $answered - $correct;
                    $blank = $sectionKeys->count() - $answered;
                    $score = $correct * $section->correct_points + $wrong * $section->wrong_points + $blank * $section->blank_points;

                    $status = ! $attempt ? 'belum' : ($attempt->finished_at ? 'selesai' : 'mengerjakan');
                    $row['sections'][$section->id] = compact('correct', 'wrong', 'blank', 'answered', 'score', 'status') + ['total_questions' => $sectionKeys->count()];
                    $row['total'] += $score;
                    $row['seconds'] += $attempt?->secondsUsed() ?? 0;
                    $row['finished'] = $row['finished'] && $status === 'selesai';
                }

                return $row;
            })
            ->sort(fn ($a, $b) => [$b['total'], $a['seconds']] <=> [$a['total'], $b['seconds']])
            ->values();

        return $rows->map(fn ($row, $i) => $row + ['rank' => $i + 1]);
    }
}
