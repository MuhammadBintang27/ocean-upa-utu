<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Support\Scoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function loginParticipant(string $username = 'p1'): User
    {
        $user = User::create(['username' => $username, 'name' => 'Peserta', 'password' => 'secret']);
        $this->post('/login', ['username' => $username, 'password' => 'secret'])->assertRedirect(route('home'));
        $user->refresh();
        // test client doesn't keep response cookies, and JSON calls skip cookies without withCredentials()
        $this->withCookie('device', $user->device_token)->withCredentials();

        return $user;
    }

    public function test_seeder_loads_both_sections_with_30_questions(): void
    {
        $this->assertSame([30, 30], Section::exam()->withCount('questions')->pluck('questions_count')->all());
    }

    public function test_account_is_locked_to_first_device(): void
    {
        $user = $this->loginParticipant();
        $this->assertNotNull($user->device_token);

        // Another browser (no device cookie) is rejected.
        $this->post('/logout');
        $this->flushSession();
        $this->disableCookieEncryption()->withCookies([])
            ->post('/login', ['username' => 'p1', 'password' => 'secret'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_waiting_room_then_runs_sections_in_order(): void
    {
        $user = $this->loginParticipant();
        [$structure, $reading] = Section::exam()->get();

        $this->get('/exam')->assertSee('Ruang Tunggu');
        $this->getJson('/exam/status')->assertJson(['open' => false]);
        $this->post('/exam/start');
        $this->assertSame(0, Attempt::count());

        Setting::current()->update(['is_open' => true]);
        $this->getJson('/exam/status')->assertJson(['open' => true]);
        $this->get('/exam')->assertOk()->assertSee('Terjawab'); // starts and renders in one request
        $this->get('/exam')->assertOk()->assertSee('Terjawab');

        $q = $structure->questions->first();
        $this->postJson('/exam/answer', ['question_id' => $q->id, 'choice' => 'c'])->assertOk();
        // A Reading question can't be answered while in Structure.
        $this->postJson('/exam/answer', ['question_id' => $reading->questions->first()->id, 'choice' => 'a'])->assertStatus(409);

        $this->post('/exam/finish');
        $this->postJson('/exam/answer', ['question_id' => $q->id, 'choice' => 'a'])->assertStatus(409);
        $this->get('/exam')->assertSee('Mulai '.$reading->name);

        $this->post('/exam/start')->assertRedirect(route('exam'));
        $this->assertSame(1, $user->attempts()->count()); // clicking start alone doesn't start the clock
        $this->travel(30)->seconds(); // e.g. stuck on the busy page
        $this->get('/exam')->assertOk()->assertSee('Terjawab');
        $readingAttempt = $user->attempts()->where('section_id', $reading->id)->first();
        $this->assertEqualsWithDelta(now()->timestamp, $readingAttempt->started_at->timestamp, 2); // clock starts when questions arrive
        $this->get('/exam')->assertSee('Terjawab'); // a reload keeps the same attempt
        $this->assertSame(2, $user->attempts()->count());
        $this->post('/exam/finish');
        $this->get('/exam')->assertSee('Terima kasih');
        $this->assertSame('c', $user->attempts()->where('section_id', $structure->id)->first()->answers->first()->choice);
    }

    public function test_latecomer_clock_starts_when_admin_opened(): void
    {
        Setting::current()->update(['is_open' => true]);
        $openedAt = Setting::current()->opened_at;

        $this->travel(10)->minutes();
        $this->loginParticipant();
        $this->get('/exam');

        $this->assertEquals($openedAt, Attempt::first()->started_at);
    }

    public function test_answers_rejected_after_deadline(): void
    {
        $this->loginParticipant();
        Setting::current()->update(['is_open' => true]);
        $this->get('/exam');
        $q = Section::exam()->first()->questions->first();

        $this->travel(26)->minutes(); // Structure is 25 minutes
        $this->postJson('/exam/answer', ['question_id' => $q->id, 'choice' => 'c'])->assertStatus(409);
        $this->assertNotNull(Attempt::first()->finished_at);
    }

    public function test_violations_lock_out_all_sections(): void
    {
        $user = $this->loginParticipant();
        Setting::current()->update(['is_open' => true, 'max_violations' => 2]);
        $this->get('/exam');

        $this->postJson('/exam/violation')->assertJson(['locked' => false]);
        $this->postJson('/exam/violation')->assertJson(['locked' => true]);
        $this->assertSame(0, $user->attempts()->whereNull('finished_at')->count());
        $this->assertSame(2, $user->attempts()->count());
        $this->get('/exam')->assertSee('Terima kasih');
    }

    public function test_answer_in_grace_period_counts_but_time_is_capped(): void
    {
        $this->loginParticipant();
        Setting::current()->update(['is_open' => true]);
        $this->get('/exam');
        $q = Section::exam()->first()->questions->first();

        $this->travel(25 * 60 + 5)->seconds(); // 5s past the 25-minute deadline
        $this->postJson('/exam/answer', ['question_id' => $q->id, 'choice' => 'c'])->assertOk();
        $this->post('/exam/finish');
        $this->assertSame(25 * 60, Attempt::first()->secondsUsed());
    }

    public function test_violations_are_logged_only_during_a_section(): void
    {
        $user = $this->loginParticipant();
        $this->postJson('/exam/violation', ['reason' => 'pindah tab/aplikasi']); // waiting room
        $this->assertSame(0, $user->fresh()->violations);

        Setting::current()->update(['is_open' => true]);
        $this->get('/exam');
        $this->postJson('/exam/violation', ['reason' => 'pindah tab/aplikasi']);
        $this->assertSame(1, $user->fresh()->violations);
        $this->assertSame('pindah tab/aplikasi', $user->violationLogs()->first()->reason);

        $this->actingAs(User::create(['username' => 'adm', 'name' => 'Admin', 'password' => 'x', 'is_admin' => true]))
            ->get(route('admin.participants.show', $user))->assertSee('Riwayat pelanggaran');
    }

    public function test_shuffle_keeps_passages_together_and_is_stable(): void
    {
        $this->loginParticipant();
        Setting::current()->update(['is_open' => true, 'shuffle_questions' => true]);
        $this->get('/exam');
        Attempt::query()->update(['finished_at' => now()]); // move on to the Reading section
        $reading = Section::exam()->skip(1)->first();
        $this->post('/exam/start');

        $order = fn () => $this->get('/exam')->viewData('section')->questions;
        $first = $order();
        $this->assertSame($first->pluck('id')->all(), $order()->pluck('id')->all());
        $this->assertEqualsCanonicalizing($reading->questions->pluck('id')->all(), $first->pluck('id')->all());
        $this->assertNotSame($reading->questions->pluck('id')->all(), $first->pluck('id')->all());
        // Each passage's questions are one consecutive block, in their original order.
        $runs = $first->pluck('passage_id')->filter()->values()->reduce(fn ($c, $p) => $c->last() === $p ? $c : $c->push($p), collect());
        $this->assertSame($runs->unique()->count(), $runs->count());
    }

    public function test_ranking_uses_points_then_fastest_time(): void
    {
        $structure = Section::exam()->first();
        $structure->update(['wrong_points' => -0.25]);
        $reading = Section::exam()->skip(1)->first();
        $q = $structure->questions;

        $make = function (string $name, array $answers, int $minutes) use ($structure, $reading) {
            $user = User::create(['username' => $name, 'name' => $name, 'password' => 'x']);
            $attempt = $user->attempts()->create(['section_id' => $structure->id, 'started_at' => now(), 'finished_at' => now()->addMinutes($minutes)]);
            foreach ($answers as $id => $choice) {
                $attempt->answers()->create(['question_id' => $id, 'choice' => $choice]);
            }
            $user->attempts()->create(['section_id' => $reading->id, 'started_at' => now(), 'finished_at' => now()->addMinutes(10)]);
        };
        $wrong = fn (Question $x) => $x->answer === 'a' ? 'b' : 'a';

        $make('slow', [$q[0]->id => $q[0]->answer, $q[1]->id => $q[1]->answer], 20);   // 2
        $make('fast', [$q[0]->id => $q[0]->answer, $q[1]->id => $q[1]->answer], 15);   // 2, faster
        $make('guess', [$q[0]->id => $q[0]->answer, $q[1]->id => $q[1]->answer, $q[2]->id => $wrong($q[2])], 5); // 1.75

        $rows = Scoring::ranking();
        $this->assertSame(['fast', 'slow', 'guess'], $rows->pluck('user.username')->all());
        $this->assertEquals(1.75, $rows[2]['total']);
        $this->assertSame([2, 1, 27], [$rows[2]['sections'][$structure->id]['correct'], $rows[2]['sections'][$structure->id]['wrong'], $rows[2]['sections'][$structure->id]['blank']]);
    }

    public function test_admin_imports_participants_from_excel_with_generated_accounts(): void
    {
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $book->getActiveSheet()->fromArray([['Nama', 'Asal Sekolah'], ['Budi', 'SMAN 1'], ['', ''], ['Siti', 'MAN 2']]);
        $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);

        $this->post('/login', ['username' => 'admin', 'password' => 'Ocean@Utu2026']);
        $this->post('/admin/participants', ['name' => '=1+1', 'school' => 'SMA X']);
        $this->post('/admin/participants/import', ['file' => new \Illuminate\Http\UploadedFile($path, 'peserta.xlsx', null, null, true)])
            ->assertSessionHasNoErrors();

        $users = User::where('is_admin', false)->orderBy('username')->get();
        $this->assertSame(['ocean001', 'ocean002', 'ocean003'], $users->pluck('username')->all());
        $this->assertSame(['=1+1', 'Budi', 'Siti'], $users->pluck('name')->all());
        $this->assertSame('SMAN 1', $users[1]->school);
        $this->assertMatchesRegularExpression('/^[a-hjkmnp-z2-9]{6}$/', $users[1]->plain_password);

        // Exported names stay literal text, not formulas.
        $out = tempnam(sys_get_temp_dir(), 'exp').'.xlsx';
        file_put_contents($out, $this->get('/admin/participants/accounts')->assertOk()->streamedContent());
        $cell = \PhpOffice\PhpSpreadsheet\IOFactory::load($out)->getActiveSheet()->getCell('B2');
        $this->assertSame('=1+1', $cell->getValue());
        $this->assertSame(\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING, $cell->getDataType());

        // Generated credentials actually log in.
        $this->post('/logout');
        $this->post('/login', ['username' => 'ocean002', 'password' => $users[1]->plain_password])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($users[1]);
    }

    public function test_participants_cannot_reach_admin(): void
    {
        $this->loginParticipant();
        $this->get('/admin')->assertForbidden();
    }

    public function test_admin_pages_render(): void
    {
        $p = User::create(['username' => 'p1', 'name' => 'Peserta', 'password' => 'x']);
        $p->attempts()->create(['section_id' => Section::first()->id, 'started_at' => now()]);
        $this->post('/login', ['username' => 'admin', 'password' => 'Ocean@Utu2026']);
        $pages = ['/admin', '/admin/participants', "/admin/participants/{$p->id}", "/admin/participants/{$p->id}/edit",
            '/admin/questions', '/admin/questions/create', '/admin/questions/1/edit', '/admin/passages/create', '/admin/passages/1/edit',
            '/admin/settings', '/admin/export', '/admin/participants/template', '/admin/participants/accounts'];
        foreach ($pages as $url) {
            $this->get($url)->assertOk();
        }
        foreach ([4, 6] as $n) {
            $pdf = $this->get("/admin/participants/cards?per_page={$n}")->assertOk()->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $pdf->getContent());
        }
    }

    public function test_participant_can_pick_only_listed_avatars(): void
    {
        $user = $this->loginParticipant();

        $this->postJson('/exam/avatar', ['avatar' => 'penyu'])->assertOk()->assertJsonPath('avatar', 'penyu');
        $this->assertSame('penyu', $user->fresh()->avatar);
        $this->get('/exam')->assertSee('image/avatars/penyu.svg');

        $this->postJson('/exam/avatar', ['avatar' => '../../etc'])->assertStatus(422);
        $this->assertSame('penyu', $user->fresh()->avatar);
    }

    public function test_lobby_shows_waiting_participants_and_start_opens_exam(): void
    {
        $user = $this->loginParticipant();
        $this->getJson('/exam/status')->assertOk()->assertJsonPath('open', false)->assertJsonPath('present', 1);

        $this->post('/logout');
        $this->post('/login', ['username' => 'admin', 'password' => 'Ocean@Utu2026']);
        $this->get('/admin/lobby')->assertOk()->assertSee('MULAI UJIAN');
        $this->getJson('/admin/lobby/data')->assertOk()->assertJsonPath('present.0.id', $user->id)->assertJsonPath('total', 1);

        $this->postJson('/admin/open')->assertOk();
        $this->postJson('/admin/open')->assertOk(); // double click must not close it again
        $this->assertTrue(Setting::current()->is_open);

        // after the start the board shows progress from the DB, not waiting-room presence
        $this->getJson('/admin/lobby/data')->assertJsonPath('present.0.status', ['Belum masuk', 'absent']);
        Attempt::create(['user_id' => $user->id, 'section_id' => Section::exam()->value('id'), 'started_at' => now()]);
        $this->getJson('/admin/lobby/data')->assertJsonPath('present.0.status', ['Structure', 'work']);
        $user->forceFill(['violations' => 99])->save();
        $this->getJson('/admin/lobby/data')->assertJsonPath('present.0.status', ['Terkunci', 'locked']);
    }

    public function test_waiting_room_poll_makes_no_db_queries_once_cached(): void
    {
        $this->loginParticipant();
        $this->getJson('/exam/status')->assertOk(); // warms the cached open flag and counts

        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->getJson('/exam/status')->assertOk()->assertJsonPath('present', 1);
        $this->assertSame([], \Illuminate\Support\Facades\DB::getQueryLog());

        $this->post('/logout');
        $this->getJson('/exam/status')->assertStatus(401);
    }

    public function test_refused_db_connection_shows_auto_retry_page_not_500(): void
    {
        \Illuminate\Support\Facades\Route::get('/_busy', fn () => throw new \Illuminate\Database\QueryException(
            'mysql', 'select 1', [], new \PDOException('SQLSTATE[HY000] [2002] Operation not permitted')
        ));

        $this->get('/_busy')->assertStatus(503)->assertSee('Server sedang ramai')->assertHeader('Retry-After');
        $this->getJson('/_busy')->assertStatus(503)->assertJsonPath('message', 'Server sedang ramai, coba lagi.');
    }

    public function test_practice_round_is_unscored_and_returns_to_waiting_room(): void
    {
        $this->assertSame([3, 3], Section::mode(true)->withCount('questions')->pluck('questions_count')->all());
        $this->assertSame(2, Section::exam()->count()); // practice sections never reach scoring

        $user = $this->loginParticipant();
        Setting::current()->update(['practice_open' => true]);
        $this->getJson('/exam/status')->assertJsonPath('practice', true)->assertJsonPath('open', false);

        // practice Structure: full short time, violations shown but not counted
        $page = $this->get('/exam')->assertOk()->assertSee('SIMULASI')->assertSee('Simulasi Structure')
            ->assertSee('location.replace', false); // watches for the real exam to start
        $this->assertEqualsWithDelta(2 * 60, $page->viewData('remaining'), 2);
        [$simStructure, $simReading] = Section::mode(true)->get();
        $this->postJson('/exam/answer', ['question_id' => $simStructure->questions->first()->id, 'choice' => 'b'])->assertOk();
        $this->postJson('/exam/violation', ['reason' => 'test'])->assertJsonPath('practice', true)->assertJsonPath('locked', false);
        $this->assertSame(0, $user->fresh()->violations);

        $this->post('/exam/finish');
        $this->get('/exam')->assertSee('Mulai '.$simReading->name)->assertSee('location.replace', false);
        $this->post('/exam/start')->assertRedirect(route('exam'));
        $this->get('/exam')->assertSee('Simulasi Reading Comprehension');
        $this->post('/exam/finish');

        // done: back in the waiting room, which must not bounce back into practice
        $this->get('/exam')->assertSee('Simulasi selesai')->assertSee('(!true || d.round !== 0)', false);

        // admin starts the real exam: practice ends, real Structure starts, practice answers never count
        Setting::current()->update(['is_open' => true]);
        $this->assertFalse(Setting::current()->practice_open);
        $this->get('/exam')->assertOk()->assertSee(Section::exam()->first()->name)->assertDontSee('SIMULASI')->assertDontSee('location.replace', false);
        $row = Scoring::ranking()->first();
        $this->assertSame(Section::exam()->pluck('id')->all(), array_keys($row['sections']));
        $this->assertEquals(0, $row['total']);
    }

    public function test_practice_questions_and_passage_never_repeat_the_real_exam(): void
    {
        $norm = fn ($t) => strtolower(trim(preg_replace('/\s+/', ' ', $t)));
        $real = Question::whereIn('section_id', Section::exam()->pluck('id'))->pluck('text')->map($norm);
        $practice = Question::whereIn('section_id', Section::mode(true)->pluck('id'))->pluck('text')->map($norm);

        $this->assertCount(6, $practice);
        $this->assertSame([], $practice->intersect($real)->values()->all());
        $realTitles = \App\Models\Passage::whereIn('section_id', Section::exam()->pluck('id'))->pluck('title')->map($norm);
        $practiceTitles = \App\Models\Passage::whereIn('section_id', Section::mode(true)->pluck('id'))->pluck('title')->map($norm);
        $this->assertSame([], $practiceTitles->intersect($realTitles)->values()->all());
    }

    public function test_admin_can_restart_the_practice_round_for_everyone(): void
    {
        $user = $this->loginParticipant();
        $this->post('/logout');
        $this->post('/login', ['username' => 'admin', 'password' => 'Ocean@Utu2026']);
        $this->postJson('/admin/practice')->assertOk();
        $this->assertSame(1, Setting::current()->practice_round);

        // participant finishes the whole practice round
        [$simStructure, $simReading] = Section::mode(true)->get();
        foreach ([$simStructure, $simReading] as $s) {
            Attempt::create(['user_id' => $user->id, 'section_id' => $s->id, 'started_at' => now()->subMinutes(5), 'finished_at' => now()]);
        }
        $this->assertSame(2, $user->attempts()->count());

        $this->postJson('/admin/practice/restart')->assertOk();
        $this->assertSame(0, $user->attempts()->count()); // practice progress wiped
        $this->assertSame(2, Setting::current()->practice_round); // open pages see a new round and rejoin
        $this->assertTrue(Setting::current()->practiceRunning());
        $this->get('/admin/lobby')->assertOk()->assertSee('Ulangi simulasi');

        // never after the real exam has started
        Setting::current()->update(['is_open' => true]);
        $this->postJson('/admin/practice/restart')->assertStatus(409);
        $this->postJson('/admin/practice')->assertStatus(409);
    }
}
