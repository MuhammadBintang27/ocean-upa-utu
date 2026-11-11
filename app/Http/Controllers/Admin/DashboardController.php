<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Support\Scoring;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'rows' => Scoring::ranking(),
            'sections' => Section::exam()->get(),
            'setting' => Setting::current(),
        ]);
    }

    public function export()
    {
        $sections = Section::exam()->get();
        $rows = Scoring::ranking();

        return response()->streamDownload(function () use ($sections, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM so Excel reads UTF-8
            $header = ['Peringkat', 'Nama', 'Username', 'Sekolah'];
            foreach ($sections as $s) {
                array_push($header, "{$s->name} Benar", "{$s->name} Salah", "{$s->name} Kosong", "{$s->name} Nilai");
            }
            fputcsv($out, array_merge($header, ['Total', 'Waktu (menit)', 'Pelanggaran']));

            foreach ($rows as $row) {
                // Leading ' stops Excel from running a name like "=cmd|..." as a formula (CSV injection).
                $safe = fn ($v) => preg_match('/^[=+\-@\t\r]/', (string) $v) ? "'".$v : $v;
                $line = [$row['rank'], $safe($row['user']->name), $safe($row['user']->username), $safe($row['user']->school)];
                foreach ($sections as $s) {
                    $r = $row['sections'][$s->id];
                    array_push($line, $r['correct'], $r['wrong'], $r['blank'], $r['score']);
                }
                fputcsv($out, array_merge($line, [$row['total'], round($row['seconds'] / 60, 2), $row['user']->violations]));
            }
            fclose($out);
        }, 'hasil-olimpiade-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function settings()
    {
        return view('admin.settings', [
            'setting' => Setting::current(),
            'sections' => Section::orderBy('is_practice')->orderBy('sort')->get(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'is_open' => ['boolean'],
            'max_violations' => ['required', 'integer', 'min:0', 'max:100'],
            'shuffle_questions' => ['boolean'],
            'sections' => ['array'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.directions' => ['nullable', 'string'],
            'sections.*.sort' => ['required', 'integer', 'min:0'],
            'sections.*.duration_minutes' => ['required', 'integer', 'min:1', 'max:600'],
            'sections.*.correct_points' => ['required', 'numeric', 'between:-100,100'],
            'sections.*.wrong_points' => ['required', 'numeric', 'between:-100,100'],
            'sections.*.blank_points' => ['required', 'numeric', 'between:-100,100'],
        ]);

        Setting::current()->update([
            'is_open' => $request->boolean('is_open'),
            'max_violations' => $data['max_violations'],
            'shuffle_questions' => $request->boolean('shuffle_questions'),
        ]);

        foreach ($data['sections'] ?? [] as $id => $values) {
            Section::whereKey($id)->update($values);
        }

        return back()->with('status', 'Pengaturan disimpan.');
    }

    public function lobby()
    {
        return view('admin.lobby', ['setting' => Setting::current()]);
    }

    public function lobbyData()
    {
        $setting = Setting::current();
        $card = fn (User $u, array $status = null) => [
            'id' => $u->id, 'name' => $u->name, 'school' => $u->school, 'avatar' => $u->avatarUrl(), 'status' => $status,
        ];

        // Before a start: who is in the waiting room. During the practice round or the exam: everyone's
        // progress through that round, straight from the DB.
        $practice = $setting->practiceRunning();
        if (! $setting->is_open && ! $practice) {
            $people = User::inLobby()->map(fn (User $u) => $card($u));
        } else {
            $sections = Section::mode($practice)->get();
            $people = User::where('is_admin', false)->orderBy('name')->with('attempts')->get()
                ->map(fn (User $u) => $card($u, $this->progress($u, $sections, $practice ? 0 : $setting->max_violations, $practice)));
        }

        return response()->json([
            'open' => $setting->is_open,
            'practice' => $practice,
            'total' => User::where('is_admin', false)->count(),
            'present' => $people->values(),
        ]);
    }

    /** [label, kind] for the projector board. Read-only: expired attempts count as finished without writing. */
    private function progress(User $user, $sections, int $maxViolations, bool $practice = false): array
    {
        if ($maxViolations > 0 && $user->violations >= $maxViolations) {
            return ['Terkunci', 'locked'];
        }
        $attempts = $user->attempts->keyBy('section_id');
        foreach ($sections as $i => $section) {
            $attempt = $attempts->get($section->id);
            if (! $attempt) {
                return $i === 0 ? ['Belum masuk', 'absent'] : ['Menuju '.$section->name, 'break'];
            }
            $attempt->setRelation('section', $section);
            if (! $attempt->finished_at && now()->lt($attempt->deadline()->addSeconds(\App\Models\Attempt::GRACE_SECONDS))) {
                return [$section->name, 'work'];
            }
        }

        return [$practice ? 'Selesai simulasi' : 'Selesai', 'done'];
    }

    /** Lobby start button: only ever opens, so a double click can't close the exam again. */
    public function open()
    {
        Setting::current()->update(['is_open' => true]);

        return response()->json(['open' => true]);
    }

    /** Lobby practice button: starts the short, unscored practice round (only before the real exam). */
    public function practice()
    {
        $setting = Setting::current();
        if ($setting->is_open) {
            return response()->json(['message' => 'Ujian sudah dimulai.'], 409);
        }
        $setting->update(['practice_open' => true, 'practice_round' => (int) $setting->practice_round + 1]);

        return response()->json(['practice' => true]);
    }

    /** Lobby "ULANGI SIMULASI": wipes every practice attempt and starts the practice round again for everyone. */
    public function restartPractice()
    {
        $setting = Setting::current();
        if ($setting->is_open) {
            return response()->json(['message' => 'Ujian sudah dimulai.'], 409);
        }
        // answers go with their attempts (cascade); real-exam attempts are untouched
        Attempt::whereIn('section_id', Section::mode(true)->pluck('id'))->delete();
        $setting->update(['practice_open' => true, 'practice_round' => (int) $setting->practice_round + 1]);

        return response()->json(['practice' => true]);
    }

    public function toggleOpen()
    {
        $setting = Setting::current();
        $setting->update(['is_open' => ! $setting->is_open]);

        return back()->with('status', $setting->is_open ? 'Ujian DIBUKA.' : 'Ujian DITUTUP.');
    }
}
