<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Passage;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestionController extends Controller
{
    public function index()
    {
        return view('admin.questions.index', [
            'sections' => Section::orderBy('sort')->with(['questions.passage', 'passages'])->get(),
        ]);
    }

    public function create(Request $request)
    {
        $question = new Question([
            'section_id' => $request->integer('section_id') ?: null,
            'sort' => Question::where('section_id', $request->integer('section_id'))->max('sort') + 1,
        ]);

        return $this->form($question);
    }

    public function store(Request $request)
    {
        Question::create($this->validated($request));

        return redirect()->route('admin.questions.index')->with('status', 'Soal ditambahkan.');
    }

    public function edit(Question $question)
    {
        return $this->form($question);
    }

    public function update(Request $request, Question $question)
    {
        $question->update($this->validated($request));

        return redirect()->route('admin.questions.index')->with('status', 'Soal diperbarui.');
    }

    public function destroy(Question $question)
    {
        $question->delete();

        return back()->with('status', 'Soal dihapus.');
    }

    private function form(Question $question)
    {
        return view('admin.questions.form', [
            'question' => $question,
            'sections' => Section::orderBy('sort')->get(),
            'passages' => Passage::orderBy('section_id')->orderBy('sort')->get(),
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'section_id' => ['required', 'exists:sections,id'],
            'passage_id' => ['nullable', Rule::exists('passages', 'id')->where('section_id', $request->input('section_id'))],
            'sort' => ['required', 'integer', 'min:0'],
            'text' => ['required', 'string'],
            'option_a' => ['required', 'string', 'max:255'],
            'option_b' => ['required', 'string', 'max:255'],
            'option_c' => ['required', 'string', 'max:255'],
            'option_d' => ['required', 'string', 'max:255'],
            'answer' => ['required', Rule::in(Question::CHOICES)],
        ]);

        return $data + ['passage_id' => null];
    }
}
