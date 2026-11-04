#!/usr/bin/env python3
"""Guards the accent palettes: frozen variable names, and swatch drift.

1. Names. Every palette file (css/accent/{light,dark}/selectify-color-NAME-
   MODE.css) defines exactly the 29 frozen --selectify-* names below, each
   once. Sites and themes override these by name, so a name is never added,
   renamed or removed. Values may change (the gentle palettes of 2026-11-02).
2. Swatches. The settings form stylesheet (css/selectify-settings-style.css)
   repeats each palette's --selectify-checkbox-checked-border-color as
   --sy-sw-NAME-MODE for the swatch dots and the live specimen. The palette
   file owns the value; the swatch must equal it.

Exits 1 on any difference.
Usage: python3 scripts/check-accent-drift.py [--self-test]
"""
import re
import sys
from collections import Counter
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SETTINGS = ROOT / "css" / "selectify-settings-style.css"
ACCENT_RE = re.compile(r"--selectify-checkbox-checked-border-color:\s*(#[0-9a-fA-F]{3,8})\s*;")
DEF_RE = re.compile(r"(--selectify-[a-z0-9-]+)\s*:")
COMMENT_RE = re.compile(r"/\*.*?\*/", re.S)
SWATCH_RE = re.compile(
    r"--sy-sw-(?P<name>[a-z]+)-(?P<mode>light|dark):\s*(?P<hex>#[0-9a-fA-F]{3,8})\s*;"
)

# The variable API of a palette file. Frozen: names only, never values.
FROZEN_NAMES = frozenset("--selectify-" + n for n in (
    "checkbox-border-color", "checkbox-bg", "checkbox-check-circle",
    "checkbox-checked-border-color", "checkbox-checked-bg", "checkbox-checked-circle",
    "checkbox-disabled-border-color", "checkbox-disabled-bg", "checkbox-disabled-circle",
    "checkbox-hover-border-color", "checkbox-hover-bg",
    "checkbox-active-border-color", "checkbox-active-bg",
    "checkbox-indeterminate-border-color", "checkbox-indeterminate-bg",
    "checkbox-indeterminate-bar",
    "checkbox-invalid-border-color", "checkbox-invalid-bg",
    "select-bg", "select-hover-bg", "select-border-color", "select-focus-border",
    "select-color", "select-focus-border-color", "select-disabled-bg",
    "select-arrow-color", "dropdown-shadow", "option-hover-bg", "option-active-bg",
))


def name_errors(label: str, text: str) -> list:
    found = Counter(DEF_RE.findall(COMMENT_RE.sub("", text)))
    errors = []
    for n in sorted(set(found) - FROZEN_NAMES):
        errors.append("%s: adds %s (names are frozen)" % (label, n))
    for n in sorted(FROZEN_NAMES - set(found)):
        errors.append("%s: missing %s" % (label, n))
    for n, c in sorted(found.items()):
        if c > 1:
            errors.append("%s: defines %s %d times" % (label, n, c))
    return errors


def palette_colors(root: Path) -> tuple:
    found, errors = {}, []
    for mode in ("light", "dark"):
        for f in sorted((root / "css" / "accent" / mode).glob("selectify-color-*-%s.css" % mode)):
            name = f.name[len("selectify-color-"):-len("-%s.css" % mode)]
            text = f.read_text()
            errors += name_errors(f.name, text)
            values = set(v.lower() for v in ACCENT_RE.findall(text))
            if len(values) != 1:
                found[(name, mode)] = "ERROR: %d distinct --selectify-checkbox-checked-border-color values" % len(values)
            else:
                found[(name, mode)] = values.pop()
    return found, errors


def swatch_colors(text: str) -> dict:
    return {(m["name"], m["mode"]): m["hex"].lower() for m in SWATCH_RE.finditer(text)}


def compare(palettes: dict, swatches: dict) -> list:
    errors = []
    for key in sorted(set(palettes) | set(swatches)):
        p, s = palettes.get(key), swatches.get(key)
        if p is None:
            errors.append("%s-%s: swatch %s has no palette file" % (key + (s,)))
        elif s is None:
            errors.append("%s-%s: palette %s has no swatch in the settings CSS" % (key + (p,)))
        elif p != s:
            errors.append("%s-%s: palette %s, settings swatch %s" % (key + (p, s)))
    return errors


def self_test() -> int:
    pal = {("blue", "light"): "#5b92d9"}
    good = swatch_colors("--sy-sw-blue-light: #5B92D9;")
    bad = swatch_colors("--sy-sw-blue-light: #3b82f6;")
    missing = swatch_colors("")
    swatch_ok = not compare(pal, good) and compare(pal, bad) and compare(pal, missing)

    full = "".join("%s: x;" % n for n in sorted(FROZEN_NAMES))
    one = sorted(FROZEN_NAMES)[0]
    names_ok = (
        not name_errors("full", "a{%s}" % full)
        and name_errors("added", "a{%s --selectify-new-thing: x;}" % full)
        and name_errors("removed", "a{%s}" % full.replace("%s: x;" % one, ""))
        and name_errors("twice", "a{%s %s: y;}" % (full, one))
        and not name_errors("comment", "a{%s /* --selectify-new-thing: x; */}" % full)
    )
    ok = bool(swatch_ok and names_ok)
    print("self-test: %s (swatches %s, names %s)" % (
        "PASS" if ok else "FAIL", "ok" if swatch_ok else "FAIL", "ok" if names_ok else "FAIL"))
    return 0 if ok else 1


def main() -> int:
    if "--self-test" in sys.argv:
        return self_test()
    palettes, errors = palette_colors(ROOT)
    swatches = swatch_colors(SETTINGS.read_text())
    if len(palettes) != 14:
        print("expected 14 palette files, found %d" % len(palettes))
        return 1
    errors += compare(palettes, swatches)
    for e in errors:
        print(e)
    print("accent drift: %s (%d palettes, %d frozen names each, %d swatches)" % (
        "FAIL" if errors else "PASS", len(palettes), len(FROZEN_NAMES), len(swatches)))
    return 1 if errors else 0


if __name__ == "__main__":
    sys.exit(main())
