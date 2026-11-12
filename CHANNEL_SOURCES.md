# japjapTV channel and source audit

This document is the human-readable companion to the seed catalogue. It records:

- which channels have an embedded official source,
- which channels are official-destination or unavailable references,
- how to verify and update a source,
- the rules japjapTV follows so it does not become an unauthorized IPTV directory.

**Snapshot:** 12 November 2026 (UTC) · **Catalogue:** 60 channels · **Embedded sources:** 27 · **Official-destination / unavailable references:** 33

> Availability changes constantly. The database stores a verification snapshot, not a promise. Re-run `bin/check_sources.php` or **Admin → Sources → Check all sources** after installation and regularly thereafter.

---

## Source policy

japjapTV only embeds a broadcaster-controlled source when embedding is permitted and the source has been explicitly marked active and authorized by an administrator.

Allowed:

- The broadcaster's own official YouTube live channel.
- An official public YouTube video where a fixed video is intended.
- An official HTTPS HLS manifest that you are authorized to use.
- An approved iframe destination on the `ALLOWED_IFRAME_HOSTS` list.
- An official external page as the fallback when embedding is not allowed.

Not allowed:

- Pirate IPTV lists, restreams, scraped player URLs, or leaked tokens.
- DRM circumvention, login bypass, paywall bypass, or geo-restriction bypass.
- Paid feeds added without authorization.
- Pretending an official destination link is an embedded live stream.

A channel can be marked **Live now** only when it has an active, authorized source. The checker promotes it when it confirms a live, embeddable YouTube source, and demotes it only after three consecutive definitive failures on every active authorized source. Uncertain results leave the state unchanged.

---

## Embedded official sources (27)

All rows below use `youtube_live` unless noted. The YouTube channel ID is a public identifier, not a secret. “Official destination” is the broadcaster's own page and is used as the honest fallback when the embed is unavailable or restricted.

| # | Channel | Country | Official destination | YouTube channel ID | Source label |
|---:|---|---|---|---|---|
| 1 | ABC Australia | Australia | <https://www.abc.net.au/news> | `UCVgO39Bk5sMo66-6o6Spn6Q` | Official ABC Australia YouTube livestream |
| 2 | ABC News | United States | <https://abcnews.go.com/live> | `UCBi2mrWuNuyYy4gbM6fU18Q` | Official ABC News YouTube livestream |
| 3 | Al Jazeera English | Qatar | <https://www.aljazeera.com/video/live> | `UCNye-wNBqNL5ZzHSJj3l8Bg` | Official Al Jazeera English YouTube livestream |
| 4 | BBC News | United Kingdom | <https://www.bbc.com/news> | `UC16niRr50-MSBwiO3YDb3RA` | Official BBC News YouTube livestream |
| 5 | Bloomberg Television | United States | <https://www.bloomberg.com/live> | `UCIALMKvObZNtJ6AmdCLP7Lg` | Official Bloomberg Television YouTube livestream |
| 6 | CBS News | United States | <https://www.cbsnews.com/live/> | `UC8p1vwvWtl6T73JiExfWs1g` | Official CBS News YouTube livestream |
| 7 | Chillhop Music | Netherlands | <https://www.youtube.com/@ChillhopMusic> | `UCOxqgCwgOqC2lMqC5PYz_Dg` | Official Chillhop Music YouTube livestream |
| 8 | CNA | Singapore | <https://www.channelnewsasia.com/watch> | `UC83jt4dlz1Gjl58fzQrrKZg` | Official CNA YouTube livestream |
| 9 | CNBC Marathon | United States | <https://www.cnbc.com/> | `UCvJJ_dzjViJCoLf5uKUTwoA` | Official CNBC Marathon YouTube livestream |
| 10 | DW English | Germany | <https://www.dw.com/en/live-tv/channel-english> | `UCknLrEdhRCp1aegoMqRaCZg` | Official DW English YouTube livestream |
| 11 | Euronews English | France | <https://www.euronews.com/live> | `UCSrZ3UV4jOidv8ppoVuvW9Q` | Official Euronews English YouTube livestream |
| 12 | France 24 English | France | <https://www.france24.com/en/live> | `UCQfwfsi5VrQ8yKZ-UWmAEFg` | Official France 24 English YouTube livestream |
| 13 | GMA Network | Philippines | <https://www.gmanetwork.com/> | `UCKL5hAuzgFQsyrsQKgU0Qng` | Official GMA Network YouTube livestream |
| 14 | IBC 13 | Philippines | <https://ibctv13.com/> | `UCQ5jV7tnWIISDf2ylEj560A` | Official IBC 13 YouTube livestream |
| 15 | Kapamilya Online Live | Philippines | <https://www.youtube.com/channel/UCstEtN0pgOmCf02EdXsGChw> | `UCstEtN0pgOmCf02EdXsGChw` | Official Kapamilya Online Live YouTube livestream |
| 16 | Lofi Girl | France | <https://www.youtube.com/@LofiGirl> | `UCSJ4gkVC6NrvII8umztf0Ow` | Official Lofi Girl YouTube livestream |
| 17 | NASA TV | United States | <https://www.nasa.gov/live/> | `UCLA_DiR1FfKNvjuUpBHmylQ` | Official NASA YouTube livestream |
| 18 | NBC News NOW | United States | <https://www.nbcnews.com/now> | `UCeY0bbntWzzVIaj2z3QigXg` | Official NBC News NOW YouTube livestream |
| 19 | NET25 | Philippines | <https://www.net25.com/> | `UC5IF8NlxMW1GkEUUqWJJBIw` | Official NET25 YouTube livestream |
| 20 | NHK WORLD-JAPAN | Japan | <https://www3.nhk.or.jp/nhkworld/en/live/> | `UCSPEjw8F2nQDtmUKPFNF7_A` | Official NHK WORLD-JAPAN YouTube livestream |
| 21 | PBS KIDS | United States | <https://pbskids.org/> | `UCrNnk0wFBnCS1awGjq_ijGQ` | Official PBS KIDS YouTube livestream |
| 22 | PTV 4 | Philippines | <https://ptvnews.ph/> | `UCJCUbMaY593_4SN1QPG7NFQ` | Official PTV YouTube livestream |
| 23 | Scripps News | United States | <https://www.scrippsnews.com/live> | `UCTln5ss6h6L_xNfMeujfPbg` | Official Scripps News YouTube livestream |
| 24 | Sky News | United Kingdom | <https://news.sky.com/watch-live> | `UCoMdktPbSTixAyNGwb-UYkQ` | Official Sky News YouTube livestream |
| 25 | TRT World | Türkiye | <https://www.trtworld.com/> | `UC7fWeaHhqgM4Ry-RMpM2YYw` | Official TRT World YouTube livestream |
| 26 | TV5 | Philippines | <https://www.tv5.com.ph/> | `UCiFx0kZFjR29u0uJxTSq5MQ` | Official TV5 YouTube livestream |
| 27 | UNTV | Philippines | <https://untvweb.com/> | `UC3XaG-7UVi2vD8ZZEMNnnpw` | Official UNTV YouTube livestream |

Each row's live URL is:

```text
https://www.youtube.com/channel/<CHANNEL_ID>/live
```

The application stores the channel ID and builds the official embed URL server-side. The public fallback button uses the **official destination** in the table, not an unofficial mirror.

---

## Official-destination and unavailable references (33)

These entries do **not** have an embedded player. They are still useful catalogue entries: they tell visitors where the broadcaster's own service lives, and they label paid access honestly. A channel marked `external_only` may still have a free official website; `subscription` means the provider requires a paid account or TV subscription.

| Channel | Country | Status | Access | Official destination |
|---|---|---|---|---|
| A2Z | Philippines | Official site | Free | <https://www.abs-cbn.com/> |
| ABS-CBN News | Philippines | Official site | Free | <https://news.abs-cbn.com/> |
| ANC | Philippines | Official site | Subscription | <https://news.abs-cbn.com/anc> |
| beIN SPORTS | Qatar | Official site | Subscription | <https://www.beinsports.com/en-asia> |
| Cartoon Network | United States | Official site | Subscription | <https://www.cartoonnetwork.com/> |
| Cinema One | Philippines | Official site | Subscription | <https://www.abs-cbn.com/> |
| CNN International | United States | Official site | Subscription | <https://edition.cnn.com/videos/live> |
| Discovery Channel | United States | Official site | Subscription | <https://www.discovery.com/> |
| Disney Channel | United States | Official site | Subscription | <https://disneynow.com/> |
| ESPN | United States | Official site | Subscription | <https://www.espn.com/watch/> |
| EWTN | United States | Official site | Free | <https://www.ewtn.com/live> |
| Food Network | United States | Official site | Subscription | <https://www.foodnetwork.com/> |
| Fox News | United States | Official site | Subscription | <https://www.foxnews.com/video/5614615980001> |
| GMA News | Philippines | Official site | Free | <https://www.gmanetwork.com/news/> |
| GTV | Philippines | Official site | Free | <https://www.gmanetwork.com/gtv/> |
| Hallypop | Philippines | Unavailable | Check access | <https://www.gmanetwork.com/entertainment/> |
| HBO | United States | Official site | Subscription | <https://www.max.com/> |
| Heart of Asia | Philippines | Official site | Subscription | <https://www.gmanetwork.com/entertainment/tv/heart_of_asia_channel/> |
| Knowledge Channel | Philippines | Official site | Free | <https://www.knowledgechannel.org/> |
| MS NOW | United States | Official site | Subscription | <https://www.ms.now/> |
| MTV | United States | Official site | Subscription | <https://www.mtv.com/> |
| National Geographic | United States | Official site | Subscription | <https://www.nationalgeographic.com/tv/> |
| NBA TV | United States | Official site | Subscription | <https://www.nba.com/watch/nba-tv> |
| NBA TV Philippines | Philippines | Official site | Subscription | <https://www.cignalplay.com/> |
| Nickelodeon | United States | Official site | Subscription | <https://www.nick.com/> |
| One News | Philippines | Official site | Check access | <https://www.onenews.ph/> |
| One Sports | Philippines | Official site | Free | <https://www.onesports.ph/> |
| PBA | Philippines | Official site | Check access | <https://www.pba.ph/> |
| PBA Rush | Philippines | Official site | Subscription | <https://www.cignalplay.com/> |
| RPTV | Philippines | Official site | Free | <https://www.rptv.ph/> |
| SMNI | Philippines | Official site | Free | <https://www.smni.com/> |
| Star Movies | United States | Official site | Subscription | <https://www.starmovies.com/> |
| TLC | United States | Official site | Subscription | <https://go.tlc.com/> |

Notes:

- **Hallypop** is seeded as unavailable because no legitimate current official live destination was verified. It is a reference entry, not a playable channel.
- **MS NOW** is used instead of the former MSNBC branding, with the provider's current official destination.
- **GTV** is seeded as an official-destination entry. It is not embedded.
- **NBA TV / NBA TV Philippines** are official paid services. The NBA page never presents them as free streams.
- Access labels describe the official provider's model. They are not a guarantee of current availability in your territory.

---

## NBA official viewing options

japjapTV does not host or restream NBA games. The NBA page uses ESPN's public scoreboard for schedule reference and links to the following official options:

| Option | Access | Official link |
|---|---|---|
| NBA League Pass | Paid subscription; blackout/territory rules apply | <https://www.nba.com/watch/league-pass-stream> |
| NBA TV | Paid subscription / participating TV providers | <https://www.nba.com/watch/nba-tv> |
| NBA TV Philippines via Cignal Play | Paid subscription | <https://www.cignalplay.com/> |
| NBA official YouTube | Free highlights, not live full games | <https://www.youtube.com/@NBA> |
| NBA schedule and scores | Free reference | <https://www.nba.com/games> |

Free full-game streams are not promised anywhere in the application. The admin **NBA games** rows are only a local fallback schedule when the ESPN feed is unreachable; they do not create a stream.

---

## Updating a source

### Admin workflow

1. Sign in at `/admin/login.php`.
2. Open **Sources**.
3. Add or edit a source.
4. Choose the channel and source type.
5. Paste the broadcaster's own identifier or URL.
6. Mark **Active**.
7. Mark **Authorized** only after confirming the broadcaster permits the use.
8. Verify the source now, or leave verification for the scheduled checker.
9. Open the channel's public watch page and confirm the player/fallback behaves honestly.
10. If the channel is no longer legitimately available, set it to **Unavailable** rather than leaving a dead embed.

### CLI workflow

```powershell
C:\xampp\php\php.exe bin\check_sources.php --dry-run
C:\xampp\php\php.exe bin\check_sources.php
C:\xampp\php\php.exe bin\check_sources.php --source=12
C:\xampp\php\php.exe bin\check_sources.php --channel=2
```

Use `--json` for machine-readable results and `--quiet` for scheduled jobs. See the README for exit codes and Task Scheduler/cron examples.

### Source type reference

| Type | What to enter | Verification |
|---|---|---|
| `youtube_live` | Official channel ID (`UC…`), `/channel/UC…` URL, or official `@handle` | Public `/live` page must report live, playable, and embeddable |
| `youtube_video` | Official video URL or 11-character ID | Public oEmbed; not proof of live status |
| `hls` | Official HTTPS `.m3u8` URL | Manifest and segment check |
| `iframe` | Approved iframe URL only | Manual; remains unverified automatically |
| `external` | Official viewing page | Manual; never embedded |

### Updating the seed

`tools/seed-data.php` is the authoritative catalogue. After editing it:

```powershell
C:\xampp\php\php.exe tools/build_database_sql.php
C:\xampp\php\php.exe tools/generate_logos.php   # only if channels were added
```

Then re-import `database.sql` in a maintenance window. Re-importing drops and recreates the japjapTV tables, so back up first. Run the source checker after import.

---

## Audit checklist

- [ ] Source belongs to the broadcaster or an explicitly authorized provider.
- [ ] Embedding/linking is permitted by the provider's terms or direct permission.
- [ ] The URL is HTTPS and publicly reachable.
- [ ] The source is active and marked authorized.
- [ ] The official destination matches the broadcaster, not a third-party mirror.
- [ ] The channel's availability label matches reality.
- [ ] The watch page and fallback link were tested.
- [ ] No DRM, paywall, login, or geographic restriction is bypassed.
- [ ] Paid access is labelled as paid.
- [ ] The source checker or a manual browser check was run after the change.

If a source cannot be verified as official and authorized, leave it out of `channel_sources` and use the broadcaster's official destination as an `external_only` entry instead. An honest fallback is better than an unauthorized player.
