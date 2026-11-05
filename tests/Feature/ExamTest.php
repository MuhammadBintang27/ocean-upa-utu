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
        $this->assertSame([30, 30], Section::orderBy('sort')->withCount('questions')->pluck('questions_count')->all());
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
        [$structure, $reading] = Section::orderBy('sort')->get();

        $this->get('/exam')->assertSee('Ruang Tunggu');
        $this->getJson('/exam/status')->assertJson(['open' => false]);
        $this->post('/exam/start');
        $this->assertSame(0, Attempt::count());

        Setting::current()->update(['is_open' => true]);
        $this->getJson('/exam/status')->assertJson(['open' => true]);
        $this->get('/exam')->assertRedirect(route('exam'));
        $this->get('/exam')->assertOk()->assertSee('Terjawab');

        $q = $structure->questions->first();
        $this->postJson('/exam/answer', ['question_id' => $q->id, 'choice' => 'c'])->assertOk();
        // A Reading question can't be answered while in Structure.
        $this->postJson('/exam/answer', ['question_id' => $reading->questions->first()->id, 'choice' => 'a'])->assertStatus(409);

        $this->post('/exam/finish');
        $this->postJson('/exam/answer', ['question_id' => $q->id, 'choice' => 'a'])->assertStatus(409);
        $this->get('/exam')->assertSee('Mulai '.$reading->name);

        $this->post('/exam/start');
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
        $q = Section::orderBy('sort')->first()->questions->first();

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
        $q = Section::orderBy('sort')->first()->questions->first();

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
        $reading = Section::orderBy('sort')->skip(1)->first();
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
        $structure = Section::orderBy('sort')->first();
        $structure->update(['wrong_points' => -0.25]);
        $reading = Section::orderBy('sort')->skip(1)->first();
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
}
