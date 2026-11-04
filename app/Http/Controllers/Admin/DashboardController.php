<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Setting;
use App\Support\Scoring;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'rows' => Scoring::ranking(),
            'sections' => Section::orderBy('sort')->get(),
            'setting' => Setting::current(),
        ]);
    }

    public function export()
    {
        $sections = Section::orderBy('sort')->get();
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
            'sections' => Section::orderBy('sort')->get(),
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

    public function toggleOpen()
    {
        $setting = Setting::current();
        $setting->update(['is_open' => ! $setting->is_open]);

        return back()->with('status', $setting->is_open ? 'Ujian DIBUKA.' : 'Ujian DITUTUP.');
    }
}
