# Taxonomy Terms — what to actually create

Reference for populating Collections and Types. Every term becomes a hub page that can rank, so
these are content decisions, not admin housekeeping.

## The rule that governs both

**Create a term only when it will have several ROMs and someone actually searches for it.**

A term with one ROM produces a hub page with one link on it — thin content, and it dilutes the
crawl budget that should be reaching real pages. A term nobody searches for produces a page nobody
finds. When in doubt, leave it out; adding a term later is trivial, removing an indexed one means
redirects.

---

## Collections — franchises and series

The highest-intent hub pages on the site. Someone searching "god of war roms" wants a list, and
`/collection/god-of-war/` is the page that answers it. These also concentrate internal links inside
a topical cluster, which is what makes related-ROM linking worth anything.

### Start with these

| Tier | Collections |
|---|---|
| **Highest volume** | Pokémon, Mario, Zelda, Final Fantasy, Grand Theft Auto, Call of Duty, God of War, FIFA, Need for Speed, Dragon Ball |
| **Strong** | Sonic, Tekken, Metal Gear, Resident Evil, Crash Bandicoot, Naruto, Winning Eleven / PES, Spider-Man, Assassin's Creed, Mega Man |
| **Worth adding once stocked** | Castlevania, Kingdom Hearts, Gran Turismo, Street Fighter, Mortal Kombat, Silent Hill, Ratchet & Clank, LEGO games, Yu-Gi-Oh!, Digimon |

### A few non-franchise collections earn their place

Only where the grouping is something people search for as a group: **Arcade**, **Multiplayer**,
**Co-op**, **Best of PS2**. Keep these to a handful — they are editorial picks, not a second
genre taxonomy.

### Naming

Use the name people type. "Pokémon" not "Pokemon Series"; "GTA" as an alias in the description, but
"Grand Theft Auto" as the term. Consistency matters more than cleverness — the slug becomes the URL.

---

## Types — what kind of build the file is

This describes the *nature of the release*, not the game. It is the axis your competitor uses for
their suggested filters, and it captures a genuinely distinct search intent: someone looking for a
fan translation is not looking for a retail dump.

| Type | Use for |
|---|---|
| **Official** | Standard retail dump. Consider making this the implied default and only tagging exceptions. |
| **Fan Translation** | Community-translated releases. High search volume, especially for Japan-only titles. |
| **ROM Hack** | Modified gameplay, levels, sprites |
| **Repack** | Recompressed to a smaller download |
| **Homebrew** | Original software written for the platform |
| **Fan Game** | Unofficial games using an existing franchise |
| **Prototype** | Pre-release builds |
| **Beta / Demo** | Playable pre-release versions |
| **Undub** | English text with original Japanese audio |
| **Decrypted** | Required by some emulators, notably 3DS |
| **Trimmed** | Padding stripped, mainly Wii/GameCube |

### Do not create

`ISO`, `CIA`, `NSP`, `ZIP` — those are file formats, not release types, and they belong in a field
if you need them at all. Mixing formats into this taxonomy makes the filter meaningless.

---

## Image sizes

| Image | Upload at | Why |
|---|---|---|
| **Featured (box art)** | **600 × 800** (3:4) | Displays at 320px in the ROM hero, so 600px covers 2x retina. The theme generates 450×600 and 300×400 crops. |
| **Screenshots** | **1280 × 720** (16:9) | Your existing choice, and correct. The theme generates a 640×360 thumbnail for the row and serves the full file only in the lightbox. |
| **Hero banner** | **1920 × 520**, WebP | Largest element on the homepage; its weight directly affects the page-speed score. |

Box art that is not 3:4 still works — it is centre-cropped — but consistent ratios keep the card
grids from looking ragged.

**Always fill in alt text.** It is an accessibility requirement, it feeds image search, and image
search is a real traffic source for game covers specifically.
