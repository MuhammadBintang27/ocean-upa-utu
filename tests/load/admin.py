"""Admin side of the load test, run from one laptop while the participant shards run elsewhere.

    python tests/load/admin.py --total 200 --password '...'

Watches the projector lobby, presses MULAI SIMULASI once everyone is in the waiting room, MULAI UJIAN once
everyone finished the practice round, and prints the board every 30s until everyone reached "Selesai".
"""
import argparse, time
from collections import Counter

import os as _os, sys as _sys
_sys.path.insert(0, _os.path.dirname(_os.path.abspath(__file__)))  # find common.py even with python -I

from common import BASE, JSON, csrf, log, login, report, retry, timed


def board(s):
    r = timed('admin_lobby_poll', s.get, BASE + '/admin/lobby/data', headers=JSON)
    try:
        return r.json() if r is not None and r.status_code == 200 else None
    except ValueError:
        return None


def summary(d):
    mode = 'UJIAN' if d['open'] else 'SIMULASI' if d['practice'] else 'ruang tunggu'
    kinds = Counter(p['status'][0] if p['status'] else 'hadir' for p in d['present'])
    return mode, kinds


def wait_until(s, cond, label, timeout):
    t0, last = time.time(), 0
    while time.time() - t0 < timeout:
        d = board(s)
        if d is not None:
            mode, kinds = summary(d)
            if time.time() - last >= 30:
                log(f'{label} | mode {mode} | ' + ', '.join(f'{k}: {v}' for k, v in kinds.most_common()))
                last = time.time()
            if cond(d, kinds):
                return d
        time.sleep(5)
    log(f'{label}: timeout after {timeout}s, continuing')
    return None


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--total', type=int, default=200)
    ap.add_argument('--password', required=True)
    ap.add_argument('--practice-only', action='store_true')
    args = ap.parse_args()

    s = login('admin', args.password)
    lobby = retry('admin_lobby_page', s.get, BASE + '/admin/lobby', ok=(200,))
    s.headers.update({'X-CSRF-TOKEN': csrf(lobby.text)})

    wait_until(s, lambda d, k: len(d['present']) >= args.total, 'menunggu semua hadir', 15 * 60)
    time.sleep(20)
    log('MULAI SIMULASI')
    retry('admin_start_practice', s.post, BASE + '/admin/practice', headers=JSON, ok=(200,))

    wait_until(s, lambda d, k: k.get('Selesai simulasi', 0) >= args.total, 'simulasi', 10 * 60)
    if args.practice_only:
        report('admin')
        return
    log('MULAI UJIAN')
    retry('admin_start_exam', s.post, BASE + '/admin/open', headers=JSON, ok=(200,))

    wait_until(s, lambda d, k: k.get('Selesai', 0) >= args.total, 'ujian', 25 * 60)
    log('selesai')
    report('admin')


if __name__ == '__main__':
    main()
