<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ParticipantController extends Controller
{
    private const USERNAME_PREFIX = 'ocean';
    // No 0/O, 1/l/I so printed credentials can't be misread.
    private const PASSWORD_CHARS = 'abcdefghjkmnpqrstuvwxyz23456789';

    public function index()
    {
        return view('admin.participants.index', [
            'participants' => User::where('is_admin', false)->orderBy('username')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
        ]);
        $user = $this->createParticipants([[$data['name'], $data['school'] ?? null]])[0];

        return back()->with('status', "Peserta ditambahkan: {$user->username} / {$user->plain_password}");
    }

    /** Excel/CSV with columns: Nama, Asal Sekolah. A header row is skipped automatically. */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120']]);

        try {
            $rows = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        } catch (Throwable) {
            return back()->withErrors(['file' => 'File tidak bisa dibaca. Pastikan formatnya .xlsx, .xls, atau .csv.']);
        }

        if ($rows && str_contains(strtolower((string) ($rows[0][0] ?? '')), 'nama')) {
            array_shift($rows);
        }
        $rows = array_values(array_filter(
            array_map(fn ($r) => [trim((string) ($r[0] ?? '')), trim((string) ($r[1] ?? '')) ?: null], $rows),
            fn ($r) => $r[0] !== '',
        ));

        if (! $rows) {
            return back()->withErrors(['file' => 'Tidak ada data peserta. Kolom A = Nama, kolom B = Asal Sekolah.']);
        }
        if (count($rows) > 2000) {
            return back()->withErrors(['file' => 'Maksimal 2000 peserta per file.']);
        }
        foreach ($rows as $i => [$name, $school]) {
            if (mb_strlen($name) > 255 || mb_strlen((string) $school) > 255) {
                return back()->withErrors(['file' => 'Baris '.($i + 1).': nama atau sekolah terlalu panjang.']);
            }
        }

        $created = $this->createParticipants($rows);

        return back()->with('status', count($created).' peserta berhasil diimport. Download daftar akun untuk dibagikan.');
    }

    public function template()
    {
        return $this->xlsx('template-import-peserta.xlsx', [['Nama', 'Asal Sekolah'], ['Budi Santoso', 'SMAN 1 Meulaboh'], ['Siti Aminah', 'MAN 1 Aceh Barat']]);
    }

    public function exportAccounts()
    {
        $rows = [['No', 'Nama', 'Asal Sekolah', 'Username', 'Password']];
        foreach (User::where('is_admin', false)->orderBy('username')->get() as $i => $u) {
            $rows[] = [$i + 1, $u->name, $u->school, $u->username, $u->plain_password ?? '(diganti manual)'];
        }

        return $this->xlsx('akun-peserta-'.now()->format('Ymd-His').'.xlsx', $rows);
    }

    /** A4 PDF of exam cards, 4 or 6 per page. */
    public function cards(Request $request)
    {
        $perPage = $request->integer('per_page') === 6 ? 6 : 4;
        set_time_limit(300); // dompdf is slow for hundreds of cards

        $html = view('admin.participants.cards', [
            'participants' => User::where('is_admin', false)->orderBy('username')->get(),
            'perPage' => $perPage,
        ])->render();

        $pdf = new Dompdf(new Options(['chroot' => public_path(), 'defaultFont' => 'DejaVu Sans']));
        $pdf->loadHtml($html);
        $pdf->setPaper('A4');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="kartu-peserta-'.$perPage.'-per-halaman.pdf"',
        ]);
    }

    public function show(User $participant)
    {
        $participant->load(['attempts.answers', 'violationLogs.section']);

        return view('admin.participants.show', [
            'participant' => $participant,
            'sections' => Section::orderBy('sort')->with('questions')->get(),
        ]);
    }

    public function edit(User $participant)
    {
        return view('admin.participants.edit', ['participant' => $participant]);
    }

    public function update(Request $request, User $participant)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($participant)],
            'name' => ['required', 'string', 'max:255'],
            'school' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4'],
        ]);
        if (blank($data['password'])) {
            unset($data['password']);
        } else {
            $data['plain_password'] = $data['password'];
        }
        $participant->update($data);

        return redirect()->route('admin.participants.index')->with('status', 'Peserta diperbarui.');
    }

    public function destroy(User $participant)
    {
        abort_if($participant->is_admin, 403);
        $participant->delete();

        return back()->with('status', 'Peserta dihapus.');
    }

    public function resetDevice(User $participant)
    {
        $participant->forceFill(['device_token' => null])->save();

        return back()->with('status', "Perangkat {$participant->username} di-reset. Peserta bisa login dari perangkat baru.");
    }

    public function resetExam(User $participant)
    {
        $participant->attempts()->delete();
        $participant->violationLogs()->delete();
        $participant->forceFill(['violations' => 0, 'exam_reset' => true])->save();

        return back()->with('status', "Ujian {$participant->username} di-reset. Semua jawaban dihapus, waktu section pertama diulang penuh.");
    }

    /**
     * @param  array<int, array{0: string, 1: ?string}>  $rows  [name, school]
     * @return User[]
     */
    private function createParticipants(array $rows): array
    {
        return DB::transaction(function () use ($rows) {
            $next = User::where('username', 'like', self::USERNAME_PREFIX.'%')->pluck('username')
                ->map(fn ($u) => preg_match('/^'.self::USERNAME_PREFIX.'(\d+)$/', $u, $m) ? (int) $m[1] : 0)
                ->max() + 1;

            return array_map(function ($row) use (&$next) {
                $password = '';
                for ($i = 0; $i < 6; $i++) {
                    $password .= self::PASSWORD_CHARS[random_int(0, strlen(self::PASSWORD_CHARS) - 1)];
                }

                return User::create([
                    'name' => $row[0],
                    'school' => $row[1],
                    'username' => self::USERNAME_PREFIX.str_pad($next++, 3, '0', STR_PAD_LEFT),
                    'password' => $password,
                    'plain_password' => $password,
                    'is_admin' => false,
                ]);
            }, $rows);
        });
    }

    private function xlsx(string $filename, array $rows)
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                // Explicit string type: a name like "=HYPERLINK(...)" must not become a formula.
                is_int($value)
                    ? $sheet->setCellValue([$c + 1, $r + 1], $value)
                    : $sheet->setCellValueExplicit([$c + 1, $r + 1], (string) $value, DataType::TYPE_STRING);
            }
        }
        $sheet->getStyle('1:1')->getFont()->setBold(true);
        foreach (range('A', $sheet->getHighestColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return response()->streamDownload(fn () => (new Xlsx($book))->save('php://output'), $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
