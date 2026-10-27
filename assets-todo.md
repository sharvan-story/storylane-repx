# wordpress.org assets still needed

Create these images and add them to SVN `assets/` after the plugin is approved. They are not part of the plugin zip.

| File | Size | Notes |
|---|---|---|
| `icon-128x128.png` | 128x128 | RepX / Storylane logomark, no wordmark |
| `icon-256x256.png` | 256x256 | Same artwork, retina |
| `banner-772x250.png` | 772x250 | Low-res plugin page banner |
| `banner-1544x500.png` | 1544x500 | High-res banner (use this as the master) |
| `screenshot-1.png` | any, about 1200 px wide | Settings > RepX page. Matches readme screenshot 1 |
| `screenshot-2.png` | any, about 1200 px wide | RepX agent answering a visitor on a site. Matches readme screenshot 2 |

Use PNG or JPG. Keep file names exact and lower case. Add more `screenshot-N` files and matching lines in `readme.txt` if wanted.

## SVN layout

```
storylane-repx/            (SVN repo root)
  trunk/                   plugin files: storylane-repx.php, readme.txt, uninstall.php
  tags/1.0.0/              copy of trunk at release
  assets/                  the images above (NOT inside trunk)
```

Release steps: copy the plugin folder contents into `trunk/`, `svn add`, `svn cp trunk tags/1.0.0`, check `Stable tag: 1.0.0` in `trunk/readme.txt`, then `svn ci`.
