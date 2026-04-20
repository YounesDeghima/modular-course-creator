#!/usr/bin/env python3
"""
mineru_extract.py  —  MinerU 3.x  (works with 2.x too)
────────────────────────────────────────────────────────
Bridge between Laravel (Symfony Process) and MinerU.
Uses the MinerU CLI directly — no fragile Python API imports.

The CLI is the most stable interface across MinerU versions.
This script finds the mineru executable next to its own Python
interpreter, so it always uses the venv version regardless of PATH.

Usage:
    python mineru_extract.py C:\\absolute\\path\\to\\file.pdf

Stdout (one JSON line):
    {"success": true,  "markdown": "...", "pages": 0}
    {"success": false, "error": "..."}
"""

import sys
import json
import os
import glob
import subprocess
import tempfile

import sys
import io


# Force stdout and stderr to use utf-8
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')


def find_mineru_exe() -> str:
    """
    Find the mineru executable in the same venv as this Python.
    Works on both Windows (Scripts/mineru.exe) and Linux (bin/mineru).
    Falls back to bare 'mineru' and lets the OS find it.
    """
    # sys.executable = .../scripts/.venv/Scripts/python.exe  (Windows)
    #                = .../scripts/.venv/bin/python3          (Linux)
    scripts_dir = os.path.dirname(sys.executable)

    candidates = [
        os.path.join(scripts_dir, "mineru.exe"),   # Windows venv
        os.path.join(scripts_dir, "mineru"),        # Linux/Mac venv
    ]

    for path in candidates:
        if os.path.isfile(path):
            return path

    # Last resort — rely on PATH (may not work when called from PHP)
    return "mineru"


def extract(pdf_path: str) -> dict:
    if not os.path.isfile(pdf_path):
        return {"success": False, "error": f"File not found: {pdf_path}"}

    mineru_exe = find_mineru_exe()

    with tempfile.TemporaryDirectory() as tmp_dir:
        try:
            cmd = [
                mineru_exe,
                "-p", pdf_path,
                "-o", tmp_dir,
                "--backend", "pipeline",
            ]

            result = subprocess.run(
                cmd,
                capture_output=True,
                text=True,
                timeout=600,           # 10 min — large PDFs can be slow
                env=os.environ.copy(), # inherit the fixed env from PHP
            )

            # ── Check for success ──────────────────────────────────────
            if result.returncode != 0:
                return {
                    "success": False,
                    "error":   "mineru exited with code " + str(result.returncode),
                    "detail":  (result.stderr or result.stdout)[:800],
                }

            # ── Find the generated .md file ────────────────────────────
            # MinerU 3.x writes: <output_dir>/<pdf_name>/<pdf_name>.md
            md_files = glob.glob(
                os.path.join(tmp_dir, "**", "*.md"),
                recursive=True,
            )

            if not md_files:
                # Sometimes MinerU writes to a sub-folder named after the PDF
                md_files = glob.glob(os.path.join(tmp_dir, "*.md"))

            if not md_files:
                return {
                    "success": False,
                    "error":   "MinerU ran but produced no .md file. "
                               "Make sure models are downloaded: "
                               "run  mineru-models-download  in your venv.",
                    "detail":  result.stdout[-500:] if result.stdout else "",
                }

            # Use the largest .md file (the actual content, not the summary)
            md_files.sort(key=os.path.getsize, reverse=True)
            with open(md_files[0], "r", encoding="utf-8", errors="replace") as f:
                md_content = f.read()

            if not md_content.strip():
                return {
                    "success": False,
                    "error":   "MinerU extracted an empty document. "
                               "The PDF may be image-only — OCR should handle it "
                               "but models may not be downloaded yet.",
                }

            return {
                "success":  True,
                "markdown": md_content,
                "pages":    0,  # CLI does not easily report page count
            }

        except subprocess.TimeoutExpired:
            return {
                "success": False,
                "error":   "MinerU timed out after 10 minutes. "
                           "Try a smaller PDF first.",
            }

        except FileNotFoundError:
            return {
                "success": False,
                "error":   f"mineru executable not found at: {mineru_exe}\n"
                           f"Make sure you installed mineru inside the venv:\n"
                           f"  scripts\\.venv\\Scripts\\pip install mineru[pipeline]",
            }

        except Exception as e:
            return {"success": False, "error": str(e)}


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({
            "success": False,
            "error":   "Usage: python mineru_extract.py <pdf_path>",
        }))
        sys.exit(1)

    output = extract(sys.argv[1])
    # One JSON line — Laravel reads this from stdout
    print(json.dumps(output, ensure_ascii=False))
