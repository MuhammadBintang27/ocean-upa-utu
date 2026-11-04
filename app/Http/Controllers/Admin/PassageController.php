<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Passage;
use App\Models\Section;
use Illuminate\Http\Request;

class PassageController extends Controller
{
    public function create(Request $request)
    {
        return $this->form(new Passage(['section_id' => $request->integer('section_id') ?: null]));
    }

    public function store(Request $request)
    {
        Passage::create($this->validated($request));

        return redirect()->route('admin.questions.index')->with('status', 'Passage ditambahkan.');
    }

    public function edit(Passage $passage)
    {
        return $this->form($passage);
    }

    public function update(Request $request, Passage $passage)
    {
        $passage->update($this->validated($request));

        return redirect()->route('admin.questions.index')->with('status', 'Passage diperbarui.');
    }

    public function destroy(Passage $passage)
    {
        $passage->delete();

        return back()->with('status', 'Passage dihapus. Soal-soalnya tetap ada tanpa passage.');
    }

    private function form(Passage $passage)
    {
        return view('admin.passages.form', ['passage' => $passage, 'sections' => Section::orderBy('sort')->get()]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'sort' => ['required', 'integer', 'min:0'],
        ]);
    }
}
