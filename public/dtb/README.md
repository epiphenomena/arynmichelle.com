# Playlist manager

A small, self-contained tool for uploading mp3s into playlists and sharing them
by link. It is completely independent of the main site: nothing here is linked
from arynmichelle.com, and nothing on arynmichelle.com reads from here.

## How it works

- **Admin** (`admin.php`, `edit.php`) is dynamic PHP, behind the site's existing
  HTTP Basic auth.
- **Shared playlist pages are static HTML.** Saving a playlist regenerates
  `<token>/<playlist-name>/index.html` — one self-contained file with its CSS and
  JS inlined. Serving a playlist runs no PHP at all.
- **Source of truth** is `data/playlists/<token>.json`, one file per playlist,
  written atomically. The static pages are derived output and can always be
  regenerated with the "Rebuild all pages" button.

## The share link

    https://arynmichelle.com/dtb/<32 hex characters>/<playlist-name>/

The 32-character token is generated once per playlist from
`random_bytes(16)` — 128 bits, not guessable. Everyone shares the same link for
a given playlist. This is obscurity, not authentication: anyone holding the link
can listen, and the link is only as private as the people it is sent to. Pages
are served with `noindex, nofollow` so they stay out of search results.

Uploaded audio is stored under equally random filenames in `media/<token>/`, so
the mp3s are no more guessable than the link itself.

**Renaming a playlist changes its share link**, because the name is part of the
URL. The old link stops working. The admin warns about this next to the name field.

## Layout

    dtb/
      .htaccess            auth, no-index headers, denies *.json
      lib.php              storage, publishing, uploads
      index.php            redirect to the admin
      admin.php            playlist list: create, delete, share, rebuild
      edit.php             one playlist: rename, upload, reorder, retitle, remove
      admin_header.php     shared admin chrome
      admin_footer.php
      admin.css            light admin theme, matches /admin
      page_template.php    generates the shared page
      player.css           inlined into every generated page
      player.js            inlined into every generated page
      data/playlists/      *.json, one per playlist        [server-only]
      media/<token>/       uploaded audio                  [server-only]
      <token>/<name>/      generated index.html            [server-only]

`data/` and `media/` are created automatically on first request — rsync never
deploys them.

## Deploying

    rake publish_dtb

`rake publish` deliberately **excludes** `dtb/`. It runs with `--delete`, and the
uploads and generated pages exist only on the server, so it would erase them.

`publish_dtb` runs *without* `--delete` for the same reason. A source file that
is retired here has to be removed on the server by hand.

After deploying a change to `player.css`, `player.js` or `page_template.php`,
click **Rebuild all pages** in the admin — existing pages carry an inlined copy
of the old assets until they are regenerated.

## Local development

    rake serve      # then http://localhost:8000/dtb/

`php -S` ignores `.htaccess`, so locally there is **no password prompt** and the
`.json` deny rule is not enforced. Both work on Apache. Range requests (seeking
within a track) are also unreliable under `php -S`; test scrubbing on the server.

## Notes

- Track durations are read in the browser when files are selected and stored in
  the JSON, which avoids parsing MP3 frames in PHP. If one is missing, the player
  fills it in lazily.
- The player uses the browser's native `<audio>` controls, so seeking, volume and
  phone lock-screen controls come for free. There is exactly one `<audio>`
  element and only its `src` changes — iOS unblocks the element on first touch,
  so replacing it would break autoplay-next on phones.
- Autoplay-next (default on) and repeat (none / all / one) are stored per
  playlist in the listener's `localStorage`. Nothing about a listener is stored
  on the server.
- Accepted uploads: mp3, m4a, aac, ogg, opus, wav, flac. The upload form shows
  the server's file size limit.
