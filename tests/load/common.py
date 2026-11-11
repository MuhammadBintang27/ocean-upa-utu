"""Shared helpers for the contest-day load test (see tests/load/README.md)."""
import os, random, re, threading, time

import requests

BASE = os.environ.get('OCEAN_BASE', 'https://ocean-utu.my.id')
JSON = {'Accept': 'application/json'}

lock = threading.Lock()
stats = {}            # phase -> [(seconds, status)]
ABORT = threading.Event()
block = {}            # first refused connection: where and when
t_start = time.time()


def log(msg):
    print(f'[{time.time() - t_start:6.0f}s] {msg}', flush=True)


def rec(phase, t, code):
    with lock:
        stats.setdefault(phase, []).append((t, code))


def timed(phase, fn, *a, **kw):
    if ABORT.is_set():
        return None
    t0 = time.perf_counter()
    try:
        r = fn(*a, timeout=60, **kw)
        rec(phase, time.perf_counter() - t0, r.status_code)
        return r
    except Exception as e:  # noqa: BLE001 - every failure is recorded
        rec(phase, time.perf_counter() - t0, type(e).__name__)
        if isinstance(e, requests.exceptions.ConnectionError) and not ABORT.is_set():
            # reset / TLS failure: the host is refusing this IP. Record where, then stop this machine.
            with lock:
                if not block:
                    block.update(at=time.strftime('%H:%M:%S'), elapsed=round(time.time() - t_start), phase=phase,
                                 url=a[0] if a else '?', error=str(e)[:160])
            ABORT.set()
            log(f'BLOCKED: {block}')
        return None


def retry(phase, fn, *a, ok=(200, 302), tries=12, **kw):
    """Like the browser: autosave and the busy page retry every 1.5-5s."""
    t0 = time.perf_counter()
    r = None
    for _ in range(tries):
        if ABORT.is_set():
            return None
        r = timed(phase + '(try)', fn, *a, **kw)
        if r is not None and r.status_code in ok:
            break
        time.sleep(random.uniform(1.5, 5))
    rec(phase, time.perf_counter() - t0, r.status_code if r is not None else 'fail')
    return r


def csrf(html):
    m = re.search(r'name="csrf-token" content="([^"]+)"', html) or re.search(r'name="_token" value="([^"]+)"', html)
    return m.group(1) if m else ''


def login(username, password):
    s = requests.Session()
    s.user = username
    page = retry('login_page', s.get, BASE + '/login', ok=(200,))
    if page is None:
        return None
    r = retry('login_post', s.post, BASE + '/login', data={'_token': csrf(page.text), 'username': username, 'password': password},
              allow_redirects=False, ok=(302,))
    return s if r is not None and r.status_code == 302 and '/login' not in r.headers.get('Location', '') else None


def report(title):
    print(f'\n== {title}')
    for phase, rows in sorted(stats.items()):
        times = sorted(t for t, _ in rows)
        codes = {}
        for _, c in rows:
            codes[c] = codes.get(c, 0) + 1
        p = lambda q: times[min(len(times) - 1, int(q * len(times)))] * 1000  # noqa: E731
        print(f'{phase:30} n={len(rows):5}  p50={p(.5):6.0f}ms  p95={p(.95):6.0f}ms  max={times[-1] * 1000:6.0f}ms  {codes}')
    print('BLOCK:', block if block else 'none', flush=True)
