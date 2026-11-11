"""Participants for the load test: one shard of loadtest001..NNN, run on its own machine (= its own IP).

    python tests/load/participants.py --shard 1 --of 5 --total 200 --start-at 1794450000

Each participant: login -> waiting room poll -> practice round (when the admin starts it) -> back to the
waiting room -> real exam (when the admin starts it) -> Structure -> Reading -> done page. Mirrors the
page JavaScript: 5s status polls, 0-6s jitter on start, autosave retries, 0-15s spread on the 00:00 finish.
"""
import argparse, json, random, re, sys, time
from concurrent.futures import ThreadPoolExecutor

import os as _os, sys as _sys
_sys.path.insert(0, _os.path.dirname(_os.path.abspath(__file__)))  # find common.py even with python -I

from common import ABORT, BASE, JSON, csrf, lock, log, login, rec, report, retry, timed

saved = []


def status(s, phase):
    r = timed(phase, s.get, BASE + '/exam/status', headers=JSON)
    try:
        return r.json() if r is not None and r.status_code == 200 else {}
    except ValueError:
        return {}


def wait_for(s, cond, phase):
    time.sleep(random.uniform(0, 5))
    while not ABORT.is_set():
        d = status(s, phase)
        if cond(d):
            time.sleep(random.uniform(0, 6))
            return d
        time.sleep(5)
    return {}


def section(s, page, name, practice):
    token = csrf(page.text)
    m = re.search(r'Date\.now\(\) \+ (\d+) \* 1000', page.text)
    if not m:
        return
    end = time.time() + int(m.group(1))
    qids = sorted({int(q) for q in re.findall(r'data-q="(\d+)"', page.text)})

    def answer(phase):
        q, c = random.choice(qids), random.choice('abcd')
        r = retry(phase, s.post, BASE + '/exam/answer', ok=(200, 409), json={'question_id': q, 'choice': c},
                  headers={**JSON, 'X-CSRF-TOKEN': token})
        if r is not None and r.status_code == 200:
            with lock:
                saved.append((s.user, q, c))

    # real pace: practice 3 questions in 2 min, exam ~1 answer per 15-20s
    while time.time() + 6 < end and not ABORT.is_set():
        time.sleep(5)
        if practice:
            status(s, 'practice_watch_poll')
        if random.random() < (0.12 if practice else 0.3):
            answer(f'{name}_answer')
    time.sleep(max(0, end - time.time() - 0.5))
    answer(f'{name}_answer_at_0000')
    time.sleep(random.uniform(0, 15))
    retry(f'{name}_finish', s.post, BASE + '/exam/finish', data={'_token': token}, allow_redirects=False, ok=(302,))


def via_intro(s, name, practice):
    intro = retry(f'{name}_intro', s.get, BASE + '/exam', ok=(200,))
    if intro is None:
        return
    time.sleep(random.uniform(3, 15))
    retry(f'{name}_start', s.post, BASE + '/exam/start', data={'_token': csrf(intro.text)}, allow_redirects=False, ok=(302,))
    page = retry(f'{name}_open', s.get, BASE + '/exam', ok=(200,))
    if page is not None and 'Terjawab' in page.text:
        section(s, page, name, practice)


def participant(s):
    wait_for(s, lambda d: d.get('practice') or d.get('open'), 'waiting_poll')
    if ABORT.is_set():
        return
    page = retry('sim_structure_open', s.get, BASE + '/exam', ok=(200,))
    if page is not None and 'SIMULASI' in page.text:
        section(s, page, 'sim_structure', True)
        via_intro(s, 'sim_reading', True)
        back = retry('back_to_waiting', s.get, BASE + '/exam', ok=(200,))
        rec('practice_done', 0, 'yes' if back is not None and 'Simulasi selesai' in back.text else 'no')
        wait_for(s, lambda d: d.get('open'), 'waiting_poll_after_sim')
    if ABORT.is_set():
        return
    page = retry('exam_structure_open', s.get, BASE + '/exam', ok=(200,))
    if page is not None and 'Terjawab' in page.text and 'SIMULASI' not in page.text:
        section(s, page, 'exam_structure', False)
        via_intro(s, 'exam_reading', False)
        done = retry('done_page', s.get, BASE + '/exam', ok=(200,))
        rec('reached_done_page', 0, 'yes' if done is not None and 'Terima kasih' in done.text else 'no')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--shard', type=int, default=1)
    ap.add_argument('--of', type=int, default=1)
    ap.add_argument('--total', type=int, default=200)
    ap.add_argument('--start-at', type=float, default=0, help='unix time all shards start logging in')
    args = ap.parse_args()

    ids = [i for i in range(1, args.total + 1) if (i - 1) % args.of == args.shard - 1]
    wait = args.start_at - time.time()
    if wait > 0:
        log(f'shard {args.shard}/{args.of}: {len(ids)} participants, starting in {wait:.0f}s')
        time.sleep(wait)
    try:
        ip = __import__('requests').get('https://api.ipify.org', timeout=10).text
    except Exception:  # noqa: BLE001
        ip = '?'
    log(f'shard {args.shard}: public IP {ip}, logging in {len(ids)} participants (~1/s across all shards)')

    def staggered(k):  # all shards together still log in ~1 participant per second
        time.sleep(k * 0.9 * args.of + random.uniform(0, 0.9 * args.of))
        return login(f'loadtest{ids[k]:03d}', 'loadtest')

    with ThreadPoolExecutor(len(ids)) as ex:
        people = [s for s in ex.map(staggered, range(len(ids))) if s]
    log(f'{len(people)}/{len(ids)} logged in')
    with ThreadPoolExecutor(len(people) or 1) as ex:
        list(ex.map(participant, people))
    report(f'shard {args.shard}/{args.of} (IP {ip})')
    print(f'confirmed answers: {len(saved)}')
    with open(f'saved-shard{args.shard}.json', 'w') as f:
        json.dump(saved, f)
    sys.exit(1 if ABORT.is_set() else 0)


if __name__ == '__main__':
    main()
