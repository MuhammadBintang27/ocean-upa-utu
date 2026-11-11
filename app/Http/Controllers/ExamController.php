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
        $setting = Setting::current();
        $practice = $setting->practiceRunning();
        [$section, $attempt] = $this->current($request->user(), $practice);
        // consumed on every visit (after the first DB call, so a refused connection keeps it for the retry)
        $wantsStart = $request->session()->pull('wants_start');

        if (! $section) {
            // practice round done: back to the waiting room until the real exam starts
            return $practice
                ? view('exam.waiting', ['section' => Section::exam()->first(), 'setting' => $setting, 'practiceDone' => true])
                : view('exam.done');
        }

        if (! $attempt) {
            $firstId = Section::mode($practice)->value('id');

            if ($section->id !== $firstId) {
                if (! $wantsStart || ! ($setting->is_open || $practice)) {
                    return view('exam.intro', ['section' => $section, 'setting' => $setting]);
                }
                $attempt = Attempt::firstOrCreate(
                    ['user_id' => $request->user()->id, 'section_id' => $section->id],
                    ['started_at' => now()],
                );
                $attempt->setRelation('section', $section);
            } elseif ($practice) {
                // practice: everyone gets the full (short) time from the moment their questions arrive
                $attempt = Attempt::firstOrCreate(
                    ['user_id' => $request->user()->id, 'section_id' => $section->id],
                    ['started_at' => now()],
                );
                $attempt->setRelation('section', $section);
            } else {
                if (! $setting->is_open) {
                    return view('exam.waiting', ['section' => $section, 'setting' => $setting, 'practiceDone' => false]);
                }
                // First section starts together for everyone; latecomers get only the time left,
                // unless the committee reset this participant, who then gets the full duration.
                $user = $request->user();
                $attempt = Attempt::firstOrCreate(
                    ['user_id' => $user->id, 'section_id' => $section->id],
                    ['started_at' => $user->exam_reset ? now() : ($setting->opened_at ?? now())],
                );
                $attempt->setRelation('section', $section);
                if ($user->exam_reset) {
                    $user->forceFill(['exam_reset' => false])->save();
                }
            }
            // render right away instead of redirecting: the clock starts in the request that delivers the questions
        }

        $section->load('questions.passage');
        $section->setRelation('questions', $this->ordered($section, $request->user()));
        $choices = $attempt->answers()->pluck('choice', 'question_id');

        return view('exam.section', [
            'section' => $section,
            'attempt' => $attempt,
            'choices' => $choices,
            'remaining' => max(0, (int) now()->diffInSeconds($attempt->deadline(), false)),
            'setting' => $setting,
            'violations' => $request->user()->violations,
        ]);
    }

    /** Which round the participant is in right now (the real exam always wins). */
    private function practiceMode(): bool
    {
        return Setting::current()->practiceRunning();
    }

    /**
     * Waiting-room poll (every participant, every few seconds). Deliberately skips the auth middleware:
     * the user id comes straight from the session so this request makes no DB connection.
     */
    public function status(Request $request)
    {
        $id = $request->session()->get(\Illuminate\Support\Facades\Auth::guard()->getName());
        if (! $id) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        User::markInLobby((int) $id);
        // cached so 200 polling participants don't each scan the whole participant list
        [$present, $total] = \Illuminate\Support\Facades\Cache::remember('lobby:count', 10, fn () => [
            User::inLobby()->count(), User::where('is_admin', false)->count(),
        ]);

        [$open, $practice, $round] = Setting::state();

        return response()->json(['open' => $open, 'practice' => $practice, 'round' => $round, 'present' => $present, 'total' => $total]);
    }

    /**
     * "Start" on a later section's intro only marks the wish to start. The attempt (and its clock) is created
     * by index() in the same request that delivers the questions, so a request refused while the server is
     * busy costs the participant no exam time. No DB here.
     */
    public function start(Request $request)
    {
        $request->session()->put('wants_start', true);

        return redirect()->route('exam');
    }

    public function answer(Request $request)
    {
        $data = $request->validate([
            'question_id' => ['required', 'integer'],
            'choice' => ['required', Rule::in(Question::CHOICES)],
        ]);

        [, $attempt] = $this->current($request->user(), $this->practiceMode());
        $question = Question::find($data['question_id']);

        if (! $attempt || ! $question || $question->section_id !== $attempt->section_id) {
            return response()->json(['ok' => false], 409);
        }

        $attempt->answers()->updateOrCreate(['question_id' => $question->id], ['choice' => $data['choice']]);

        return response()->json(['ok' => true]);
    }

    public function finish(Request $request)
    {
        [, $attempt] = $this->current($request->user(), $this->practiceMode());
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
        $practice = $this->practiceMode();
        [$section, $attempt] = $this->current($user, $practice);
        $max = Setting::current()->max_violations;

        // Only counts while a real section is running: not in the waiting room, after finishing, or in practice
        // (the practice round shows the warning so students learn the rules, but can't lock anyone out).
        if (! $attempt || $practice) {
            return response()->json(['violations' => $user->violations, 'max' => $max, 'locked' => ! $section && ! $practice, 'practice' => $practice]);
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
        foreach (Section::exam()->get() as $section) {
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
    private function current(User $user, bool $practice = false): array
    {
        $attempts = $user->attempts()->with('section')->get()->keyBy('section_id');

        foreach (Section::mode($practice)->get() as $section) {
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
