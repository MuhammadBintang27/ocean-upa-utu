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
            ['name' => 'Administrator', 'password' => env('ADMIN_PASSWORD', 'admin123'), 'is_admin' => true],
        );

        Setting::current();

        if (Section::exists()) {
            return; // questions already seeded; don't overwrite admin edits
        }

        $structure = Section::create([
            'name' => 'Structure',
            'sort' => 1,
            'duration_minutes' => 25,
            'directions' => 'In this section, you will be given several incomplete sentences. Choose the one word — A, B, C or D — that best completes the sentence. Select the appropriate answer for each by clicking on it.',
        ]);
        $this->questions($structure, null, require __DIR__.'/data/structure.php');

        $reading = Section::create([
            'name' => 'Reading Comprehension',
            'sort' => 2,
            'duration_minutes' => 45,
            'directions' => "In this section, you will read several passages. Each one is followed by a number of questions about it. Choose the one best answer — A, B, C or D — to each question. As you're going through the questions, select the appropriate answer for each by clicking on it.",
        ]);
        $sort = 0;
        foreach (require __DIR__.'/data/reading.php' as $i => $p) {
            $passage = $reading->passages()->create(['title' => $p['title'], 'body' => $p['body'], 'sort' => $i + 1]);
            $sort = $this->questions($reading, $passage->id, $p['questions'], $sort);
        }
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
