/* -----------------------------------------------------------------------
   demo-tape-board :: shared playlist page
   Inlined verbatim into every published index.html. Vanilla, no build step,
   no network calls -- the only thing the page ever fetches is its own audio.
   ----------------------------------------------------------------------- */

(function () {
    'use strict';

    const dataEl = document.getElementById('dtb-data');
    if (!dataEl) {
        return;
    }

    let data;
    try {
        data = JSON.parse(dataEl.textContent || '{}');
    } catch (err) {
        return; // Leave the server-rendered list alone; the links still work.
    }

    const tracks = Array.isArray(data.tracks) ? data.tracks : [];
    const body = document.body;
    body.classList.add('dtb-js');

    /* ------------------------------------------------------------- helpers */

    const pad = (n) => (n < 10 ? '0' + n : String(n));

    // Mirrors dtb_format_time() so JS-filled durations match server-rendered ones.
    const fmt = (secs) => {
        const total = Math.round(Number(secs) || 0);
        if (!(total > 0)) {
            return '--:--';
        }
        const h = Math.floor(total / 3600);
        const m = Math.floor((total % 3600) / 60);
        const s = total % 60;
        return h > 0 ? h + ':' + pad(m) + ':' + pad(s) : m + ':' + pad(s);
    };

    /* --------------------------------------------------------------- share */

    const shareBtn = document.getElementById('dtb-share');
    if (shareBtn) {
        const shareLabel = shareBtn.textContent;
        let flashTimer = null;
        const flash = (msg) => {
            shareBtn.textContent = msg;
            clearTimeout(flashTimer);
            flashTimer = setTimeout(() => {
                shareBtn.textContent = shareLabel;
            }, 1600);
        };
        shareBtn.addEventListener('click', () => {
            const url = location.href;
            if (navigator.share) {
                // Dismissing the share sheet rejects with AbortError. Not an error.
                navigator.share({ title: data.name || document.title, url: url }).catch(() => {});
                return;
            }
            // navigator.clipboard is undefined on insecure origins and file://.
            if (navigator.clipboard?.writeText) {
                navigator.clipboard.writeText(url).then(
                    () => flash('Copied'),
                    () => flash('Copy failed')
                );
                return;
            }
            flash('Copy failed');
        });
    }

    // Nothing below this point makes sense for an empty playlist, and the page
    // does not render a player bar for one.
    if (!tracks.length) {
        return;
    }

    /* ------------------------------------------------------------ elements */

    // THE audio element. There is exactly one for the whole page and it is never
    // replaced or re-created -- only `src` is swapped. iOS/Safari unblock the
    // *element* on the first user gesture, so an element built fresh inside an
    // `ended` handler is still blocked and next-track would silently die on
    // phones. Swap the src on this one element, load(), then play().
    const audio = document.getElementById('dtb-audio');
    const bar = document.getElementById('dtb-bar');
    const nowTitle = document.getElementById('dtb-now-title');
    const prevBtn = document.getElementById('dtb-prev');
    const nextBtn = document.getElementById('dtb-next');
    const autoBtn = document.getElementById('dtb-autoplay');
    const repeatBtn = document.getElementById('dtb-repeat');
    const totalEl = document.getElementById('dtb-total');
    const rows = Array.prototype.slice.call(document.querySelectorAll('.dtb-track'));

    if (!audio || !rows.length) {
        return;
    }

    /* --------------------------------------------------- persisted settings */

    const STORE_KEY = 'dtb:' + (data.token || '');
    const REPEAT_MODES = ['none', 'all', 'one'];
    const REPEAT_LABEL = { none: 'Repeat off', all: 'Repeat all', one: 'Repeat one' };

    // Autoplay-next defaults ON. Read with an explicit boolean check so a stored
    // `false` actually survives a reload.
    const prefs = { autoplay: true, repeat: 'none' };

    try {
        const raw = localStorage.getItem(STORE_KEY);
        if (raw) {
            const saved = JSON.parse(raw);
            if (typeof saved?.autoplay === 'boolean') {
                prefs.autoplay = saved.autoplay;
            }
            if (REPEAT_MODES.indexOf(saved?.repeat) !== -1) {
                prefs.repeat = saved.repeat;
            }
        }
    } catch (err) {
        /* private mode / file:// -- fall back to the defaults */
    }

    const savePrefs = () => {
        try {
            localStorage.setItem(STORE_KEY, JSON.stringify(prefs));
        } catch (err) {
            /* nothing we can do, and nothing worth breaking playback over */
        }
    };

    const reflectPrefs = () => {
        if (autoBtn) {
            autoBtn.textContent = prefs.autoplay ? 'Autoplay on' : 'Autoplay off';
            autoBtn.setAttribute('aria-pressed', prefs.autoplay ? 'true' : 'false');
        }
        if (repeatBtn) {
            repeatBtn.textContent = REPEAT_LABEL[prefs.repeat];
            repeatBtn.setAttribute('aria-pressed', prefs.repeat === 'none' ? 'false' : 'true');
        }
    };

    reflectPrefs();

    if (autoBtn) {
        autoBtn.addEventListener('click', () => {
            prefs.autoplay = !prefs.autoplay;
            savePrefs();
            reflectPrefs();
        });
    }

    if (repeatBtn) {
        repeatBtn.addEventListener('click', () => {
            prefs.repeat = REPEAT_MODES[(REPEAT_MODES.indexOf(prefs.repeat) + 1) % REPEAT_MODES.length];
            savePrefs();
            reflectPrefs();
        });
    }

    /* ---------------------------------------------------------- track list */

    let current = -1;
    let intendPlay = false;
    let errorStreak = 0;

    const durationCell = (i) => rows[i] && rows[i].querySelector('.dtb-dur');
    const numberCell = (i) => rows[i] && rows[i].querySelector('.dtb-num');

    const updateTotal = () => {
        if (!totalEl) {
            return;
        }
        let total = 0;
        for (const track of tracks) {
            total += Number(track.duration) || 0;
        }
        if (total > 0) {
            totalEl.textContent = ' · ' + fmt(total);
            totalEl.hidden = false;
        }
    };

    const setDuration = (i, secs) => {
        if (!Number.isFinite(secs) || secs <= 0 || !tracks[i]) {
            return;
        }
        tracks[i].duration = secs;
        const cell = durationCell(i);
        if (cell && !rows[i].classList.contains('dtb-error')) {
            cell.textContent = fmt(secs);
        }
        updateTotal();
    };

    const updateIndicator = () => {
        rows.forEach((row, i) => {
            const cell = numberCell(i);
            if (!cell) {
                return;
            }
            if (i === current) {
                // U+FE0E keeps the play glyph as text; without it phones render a wide
                // colour emoji that breaks the number column.
                cell.textContent = audio.paused ? '❚❚' : '▶︎';
            } else {
                cell.textContent = String(i + 1);
            }
        });
    };

    const markCurrent = () => {
        rows.forEach((row, i) => {
            const on = i === current;
            row.classList.toggle('dtb-current', on);
            const link = row.querySelector('.dtb-row');
            if (link) {
                if (on) {
                    link.setAttribute('aria-current', 'true');
                } else {
                    link.removeAttribute('aria-current');
                }
            }
        });
        updateIndicator();
    };

    const setMediaSession = (track) => {
        if (!('mediaSession' in navigator) || typeof window.MediaMetadata !== 'function') {
            return;
        }
        try {
            navigator.mediaSession.metadata = new window.MediaMetadata({
                title: track.title || 'Untitled Track',
                album: data.name || '',
                artist: ''
            });
        } catch (err) {
            /* some engines are picky; metadata is a nicety */
        }
    };

    const attemptPlay = () => {
        intendPlay = true;
        const promise = audio.play();
        if (promise && typeof promise.catch === 'function') {
            // Autoplay policy or a decode failure: don't leave the UI claiming
            // it is playing.
            promise.catch(() => {
                intendPlay = false;
                updateIndicator();
            });
        }
    };

    const load = (i, shouldPlay) => {
        if (i < 0 || i >= tracks.length) {
            return;
        }
        current = i;
        intendPlay = !!shouldPlay;
        const track = tracks[i];

        audio.src = track.src;
        audio.load();

        if (nowTitle) {
            nowTitle.textContent = track.title || 'Untitled Track';
        }
        markCurrent();
        setMediaSession(track);

        if (shouldPlay) {
            attemptPlay();
        }
    };

    // Explicit prev/next always wrap; only the automatic `ended` advance is
    // bound by the repeat mode.
    const goNext = () => load((current + 1) % tracks.length, true);
    const goPrev = () => {
        // Standard behaviour: restart the track if we are more than a few
        // seconds in, otherwise step back.
        if (current >= 0 && audio.currentTime > 3) {
            audio.currentTime = 0;
            attemptPlay();
            return;
        }
        load((current - 1 + tracks.length) % tracks.length, true);
    };

    if (nextBtn) {
        nextBtn.addEventListener('click', goNext);
    }
    if (prevBtn) {
        prevBtn.addEventListener('click', goPrev);
    }

    rows.forEach((row, i) => {
        const link = row.querySelector('.dtb-row');
        if (!link) {
            return;
        }
        link.addEventListener('click', (e) => {
            // Let modifier / middle clicks follow the real href so the file can
            // still be opened or saved directly.
            if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
                return;
            }
            e.preventDefault();
            if (i === current) {
                if (audio.paused) {
                    attemptPlay();
                } else {
                    audio.pause();
                }
                return;
            }
            row.classList.remove('dtb-error');
            load(i, true);
        });
    });

    /* --------------------------------------------------------- audio events */

    audio.addEventListener('play', updateIndicator);
    audio.addEventListener('pause', updateIndicator);

    audio.addEventListener('playing', () => {
        errorStreak = 0;
        updateIndicator();
    });

    audio.addEventListener('loadedmetadata', () => {
        // Authoritative duration for whatever is actually loaded.
        setDuration(current, audio.duration);
    });

    audio.addEventListener('error', () => {
        if (current < 0) {
            return;
        }
        const row = rows[current];
        if (row) {
            row.classList.add('dtb-error');
            row.title = 'This track could not be loaded.';
            const cell = durationCell(current);
            if (cell) {
                cell.textContent = 'n/a';
            }
        }
        errorStreak += 1;
        // Move on rather than hanging -- but never spin through a whole
        // playlist of broken files.
        if (!intendPlay || errorStreak >= tracks.length) {
            intendPlay = false;
            updateIndicator();
            return;
        }
        const next = current + 1;
        if (next < tracks.length) {
            load(next, true);
        } else if (prefs.repeat === 'all') {
            load(0, true);
        } else {
            intendPlay = false;
            updateIndicator();
        }
    });

    // The one behaviour the owner cares about most, in order:
    //   repeat one   -> restart this track (wins over autoplay-next being off)
    //   autoplay off -> stop
    //   otherwise    -> advance, wrapping to the first track only on repeat all
    audio.addEventListener('ended', () => {
        if (prefs.repeat === 'one') {
            audio.currentTime = 0;
            attemptPlay();
            return;
        }
        if (!prefs.autoplay) {
            intendPlay = false;
            updateIndicator();
            return;
        }
        const next = current + 1;
        if (next < tracks.length) {
            load(next, true);
            return;
        }
        if (prefs.repeat === 'all') {
            load(0, true);
            return;
        }
        intendPlay = false;
        updateIndicator();
    });

    /* --------------------------------------------------------- media session */

    if ('mediaSession' in navigator) {
        try {
            navigator.mediaSession.setActionHandler('previoustrack', goPrev);
            navigator.mediaSession.setActionHandler('nexttrack', goNext);
        } catch (err) {
            /* unsupported action -- ignore */
        }
    }

    /* -------------------------------------------------------------- keyboard */

    const swallowsKeys = (target) => {
        if (!target || target === audio) {
            return true; // never fight the native controls
        }
        if (target.isContentEditable) {
            return true;
        }
        const tag = target.tagName;
        return tag === 'BUTTON' || tag === 'INPUT' || tag === 'SELECT' || tag === 'TEXTAREA';
    };

    const seekBy = (delta) => {
        if (current < 0) {
            return;
        }
        const now = Number(audio.currentTime) || 0;
        let next = now + delta;
        if (next < 0) {
            next = 0;
        }
        if (Number.isFinite(audio.duration) && next > audio.duration) {
            next = audio.duration;
        }
        try {
            audio.currentTime = next;
        } catch (err) {
            /* not seekable yet */
        }
    };

    document.addEventListener('keydown', (e) => {
        if (e.metaKey || e.ctrlKey || e.altKey || swallowsKeys(e.target)) {
            return;
        }
        if (e.code === 'Space' || e.key === ' ') {
            e.preventDefault();
            if (current < 0) {
                load(0, true);
            } else if (audio.paused) {
                attemptPlay();
            } else {
                audio.pause();
            }
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            seekBy(5);
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            seekBy(-5);
        } else if (e.key === 'n' || e.key === 'N') {
            e.preventDefault();
            goNext();
        } else if (e.key === 'p' || e.key === 'P') {
            e.preventDefault();
            goPrev();
        }
    });

    /* ------------------------------------------------- lazy duration probing */

    // One reusable hidden Audio object working a queue strictly one at a time.
    // A parallel burst of range requests is exactly what a shared-hosting box
    // does not need.
    const probeQueue = [];
    tracks.forEach((track, i) => {
        if (!(Number(track.duration) > 0)) {
            probeQueue.push(i);
        }
    });

    if (probeQueue.length) {
        const probe = new Audio();
        probe.preload = 'metadata';
        probe.muted = true;
        let probeIndex = -1;
        let probeBusy = false;
        let probeTimer = null;

        const finishProbe = () => {
            clearTimeout(probeTimer);
            probeBusy = false;
            probeIndex = -1;
            nextProbe();
        };

        const nextProbe = () => {
            if (probeBusy || !probeQueue.length) {
                return;
            }
            const i = probeQueue.shift();
            if (!tracks[i] || Number(tracks[i].duration) > 0) {
                nextProbe();
                return;
            }
            probeBusy = true;
            probeIndex = i;
            probe.src = tracks[i].src;
            probe.load();
            // A stalled request must not freeze every remaining duration.
            probeTimer = setTimeout(finishProbe, 15000);
        };

        probe.addEventListener('loadedmetadata', () => {
            if (probeIndex >= 0) {
                setDuration(probeIndex, probe.duration);
            }
            finishProbe();
        });
        probe.addEventListener('error', finishProbe);

        nextProbe();
    }

    /* ------------------------------------------------------------ bar sizing */

    const syncBarHeight = () => {
        if (!bar) {
            return;
        }
        // offsetHeight already includes the safe-area padding on the bar itself.
        document.documentElement.style.setProperty('--dtb-bar-h', bar.offsetHeight + 'px');
    };

    syncBarHeight();
    window.addEventListener('resize', syncBarHeight);
    window.addEventListener('orientationchange', syncBarHeight);
    if (typeof ResizeObserver === 'function' && bar) {
        new ResizeObserver(syncBarHeight).observe(bar);
    }

    /* ----------------------------------------------------------------- start */

    updateTotal();
    load(0, false); // cue the first track; playback still waits for a gesture
})();
