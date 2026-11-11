<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => env('ADMIN_USERNAME', 'admin')],
            ['name' => 'Administrator', 'password' => env('ADMIN_PASSWORD', 'Ocean@Utu2026'), 'is_admin' => true],
        );

        Setting::current();

        if (! Section::exam()->exists()) {
            $this->examSections();
        }
        // separate check so an existing install gets the practice round with a plain db:seed (no fresh)
        if (! Section::mode(true)->exists()) {
            $this->practiceSections();
        }
    }

    private function examSections(): void
    {
        $structure = Section::create([
            'name' => 'Structure',
            'sort' => 1,
            'duration_minutes' => 25,
            'directions' => 'In this section, you will be given several incomplete sentences. For each sentence, choose the one word or phrase (A, B, C, or D) that best completes it. Click on your answer to select it.',
        ]);
        $this->questions($structure, null, require __DIR__.'/data/structure.php');

        $reading = Section::create([
            'name' => 'Reading Comprehension',
            'sort' => 2,
            'duration_minutes' => 45,
            'directions' => "In this section, you will read several passages, each followed by a number of questions. For each question, choose the best answer (A, B, C, or D) based on what is stated or implied in the passage. Click on your answer to select it.",
        ]);
        $sort = 0;
        foreach (require __DIR__.'/data/reading.php' as $i => $p) {
            $passage = $reading->passages()->create(['title' => $p['title'], 'body' => $p['body'], 'sort' => $i + 1]);
            $sort = $this->questions($reading, $passage->id, $p['questions'], $sort);
        }
    }

    /** Practice round (simulasi): short, unscored, run from the lobby before the real exam. */
    private function practiceSections(): void
    {
        $practice = require __DIR__.'/data/practice.php';
        [$structure, $reading] = Section::exam()->get();
        $simStructure = Section::create([
            'name' => 'Simulasi Structure',
            'sort' => 1,
            'is_practice' => true,
            'duration_minutes' => 2,
            'directions' => $structure->directions,
        ]);
        $this->questions($simStructure, null, $practice['structure']);

        $simReading = Section::create([
            'name' => 'Simulasi Reading Comprehension',
            'sort' => 2,
            'is_practice' => true,
            'duration_minutes' => 2,
            'directions' => $reading->directions,
        ]);
        $passage = $simReading->passages()->create(['title' => $practice['reading']['title'], 'body' => $practice['reading']['body'], 'sort' => 1]);
        $this->questions($simReading, $passage->id, $practice['reading']['questions']);
    }

    private function questions(Section $section, ?int $passageId, array $rows, int $sort = 0): int
    {
        foreach ($rows as [$text, $a, $b, $c, $d, $answer]) {
            $section->questions()->create([
                'passage_id' => $passageId,
                'sort' => ++$sort,
                'text' => $text,
                'option_a' => $a, 'option_b' => $b, 'option_c' => $c, 'option_d' => $d,
                'answer' => $answer,
            ]);
        }
        return $sort;
    }
}
