<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Models\ViolationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        [$section, $attempt] = $this->current($request->user());

        if (! $section) {
            return view('exam.done');
        }

        if (! $attempt) {
            $setting = Setting::current();

            if ($section->id !== Section::orderBy('sort')->value('id')) {
                return view('exam.intro', ['section' => $section, 'setting' => $setting]);
            }
            if (! $setting->is_open) {
                return view('exam.waiting', ['section' => $section, 'setting' => $setting]);
            }
            // First section starts together for everyone; latecomers get only the time left,
            // unless the committee reset this participant, who then gets the full duration.
            $user = $request->user();
            Attempt::firstOrCreate(
                ['user_id' => $user->id, 'section_id' => $section->id],
                ['started_at' => $user->exam_reset ? now() : ($setting->opened_at ?? now())],
            );
            if ($user->exam_reset) {
                $user->forceFill(['exam_reset' => false])->save();
            }

            return redirect()->route('exam');
        }

        $section->load('questions.passage');
        $section->setRelation('questions', $this->ordered($section, $request->user()));
        $choices = $attempt->answers()->pluck('choice', 'question_id');

        return view('exam.section', [
            'section' => $section,
            'attempt' => $attempt,
            'choices' => $choices,
            'remaining' => max(0, (int) now()->diffInSeconds($attempt->deadline(), false)),
            'setting' => Setting::current(),
            'violations' => $request->user()->violations,
        ]);
    }

    public function status(Request $request)
    {
        $request->user()->markInLobby();
        // cached so 200 polling participants don't each scan the whole participant list
        [$present, $total] = \Illuminate\Support\Facades\Cache::remember('lobby:count', 10, fn () => [
            User::inLobby()->count(), User::where('is_admin', false)->count(),
        ]);

        return response()->json(['open' => Setting::current()->is_open, 'present' => $present, 'total' => $total]);
    }

    public function start(Request $request)
    {
        [$section, $attempt] = $this->current($request->user());

        // The first section is started from the waiting room in index(), not here.
        if ($section && ! $attempt && $section->id !== Section::orderBy('sort')->value('id')) {
            if (! Setting::current()->is_open) {
                return back()->withErrors(['start' => 'Ujian belum dibuka oleh panitia.']);
            }
            Attempt::firstOrCreate(
                ['user_id' => $request->user()->id, 'section_id' => $section->id],
                ['started_at' => now()],
            );
        }

        return redirect()->route('exam');
    }

    public function answer(Request $request)
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'choice' => ['required', Rule::in(Question::CHOICES)],
        ]);

        [, $attempt] = $this->current($request->user());
        $question = Question::find($data['question_id']);

        if (! $attempt || ! $question || $question->section_id !== $attempt->section_id) {
            return response()->json(['ok' => false], 409);
        }

        $attempt->answers()->updateOrCreate(['question_id' => $question->id], ['choice' => $data['choice']]);

        return response()->json(['ok' => true]);
    }

    public function finish(Request $request)
    {
        [, $attempt] = $this->current($request->user());
        // During the grace period now() is past the deadline; time used must not exceed the duration.
        $attempt?->update(['finished_at' => now()->min($attempt->deadline())]);

        return redirect()->route('exam');
    }

    public function avatar(Request $request)
    {
        $data = $request->validate(['avatar' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(User::AVATARS))]]);
        $request->user()->update($data);

        return response()->json(['avatar' => $data['avatar'], 'url' => $request->user()->avatarUrl()]);
    }

    public function violation(Request $request)
    {
        $user = $request->user();
        [$section, $attempt] = $this->current($user);
        $max = Setting::current()->max_violations;

        // Only counts while a section is running (not in the waiting room or after finishing).
        if (! $attempt) {
            return response()->json(['violations' => $user->violations, 'max' => $max, 'locked' => ! $section]);
        }

        $user->increment('violations');
        ViolationLog::create([
            'user_id' => $user->id,
            'section_id' => $section->id,
            'reason' => Str::limit((string) $request->input('reason', 'tidak diketahui'), 97),
        ]);
        $locked = $max > 0 && $user->violations >= $max;

        if ($locked) {
            $this->lockOut($user);
        }

        return response()->json(['violations' => $user->violations, 'max' => $max, 'locked' => $locked]);
    }

    /**
     * Question order for this participant. With shuffling on, passage groups stay together and in order;
     * the seed is per user+section so a reload shows the same order.
     */
    private function ordered(Section $section, User $user): Collection
    {
        if (! Setting::current()->shuffle_questions) {
            return $section->questions;
        }
        $groups = $section->questions->groupBy(fn ($q) => $q->passage_id ? 'p'.$q->passage_id : 'q'.$q->id)->values()->all();
        $groups = (new Randomizer(new Mt19937($user->id * 1000 + $section->id)))->shuffleArray($groups);

        return collect($groups)->flatten(1);
    }

    /** Ends the running section and every section not started yet. */
    private function lockOut(User $user): void
    {
        foreach (Section::orderBy('sort')->get() as $section) {
            $attempt = Attempt::firstOrCreate(
                ['user_id' => $user->id, 'section_id' => $section->id],
                ['started_at' => now()],
            );
            if (! $attempt->finished_at) {
                $attempt->update(['finished_at' => now()]);
            }
        }
    }

    /**
     * The first section the user hasn't finished, with its attempt if started.
     * Returns [null, null] when everything is done.
     */
    private function current(User $user): array
    {
        $attempts = $user->attempts()->with('section')->get()->keyBy('section_id');

        foreach (Section::orderBy('sort')->get() as $section) {
            $attempt = $attempts->get($section->id);
            if (! $attempt) {
                return [$section, null];
            }
            if (! $attempt->closeIfExpired()) {
                return [$section, $attempt];
            }
        }

        return [null, null];
    }
}
