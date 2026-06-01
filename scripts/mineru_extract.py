#!/usr/bin/env python3
"""
mineru_extract.py  —  MinerU 3.x  (works with 2.x too)
────────────────────────────────────────────────────────
Bridge between Laravel (Symfony Process) and MinerU.
Uses the MinerU CLI directly — no fragile Python API imports.

Usage:
    python mineru_extract.py <pdf_path> <output_dir>

    output_dir: persistent directory (e.g. storage/app/ai_images/{job_id}/{md_index}/)
                Laravel creates this before calling the script.

Stdout (one JSON line):
    {"success": true,  "markdown": "...", "pages": 0, "images": ["abs/path/img1.png", ...]}
    {"success": false, "error": "..."}
"""

import sys
import json
import os
import glob
import subprocess
import io

# Force stdout/stderr utf-8
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8')


def find_mineru_exe() -> str:
    scripts_dir = os.path.dirname(sys.executable)
    candidates = [
        os.path.join(scripts_dir, "mineru.exe"),
        os.path.join(scripts_dir, "mineru"),
    ]
    for path in candidates:
        if os.path.isfile(path):
            return path
    return "mineru"


def collect_images(output_dir: str) -> list:
    """Collect all image files MinerU produced in output_dir."""
    exts = ('*.png', '*.jpg', '*.jpeg', '*.gif', '*.webp', '*.svg')
    images = []
    for ext in exts:
        images.extend(glob.glob(os.path.join(output_dir, '**', ext), recursive=True))
    return sorted(images)


def extract(pdf_path: str, output_dir: str) -> dict:
    if not os.path.isfile(pdf_path):
        return {"success": False, "error": f"File not found: {pdf_path}"}

    os.makedirs(output_dir, exist_ok=True)

    mineru_exe = find_mineru_exe()

    try:
        cmd = [
            mineru_exe,
            "-p", pdf_path,
            "-o", output_dir,
            "--backend", "pipeline",
        ]

        result = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=600,
            env=os.environ.copy(),
        )

        if result.returncode != 0:
            return {
                "success": False,
                "error":   "mineru exited with code " + str(result.returncode),
                "detail":  (result.stderr or result.stdout)[:800],
            }

        # ── Find generated .md file ────────────────────────────────────────────
        md_files = glob.glob(os.path.join(output_dir, "**", "*.md"), recursive=True)
        if not md_files:
            md_files = glob.glob(os.path.join(output_dir, "*.md"))

        if not md_files:
            return {
                "success": False,
                "error":   "MinerU ran but produced no .md file. "
                           "Make sure models are downloaded: "
                           "run  mineru-models-download  in your venv.",
                "detail":  result.stdout[-500:] if result.stdout else "",
            }

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

        # ── Collect images ─────────────────────────────────────────────────────
        images = collect_images(output_dir)

        return {
            "success":  True,
            "markdown": md_content,
            "pages":    0,
            "images":   images,   # absolute paths inside output_dir
        }

    except subprocess.TimeoutExpired:
        return {
            "success": False,
            "error":   "MinerU timed out after 10 minutes. Try a smaller PDF first.",
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
    if len(sys.argv) < 3:
        print(json.dumps({
            "success": False,
            "error":   "Usage: python mineru_extract.py <pdf_path> <output_dir>",
        }))
        sys.exit(1)

    output = extract(sys.argv[1], sys.argv[2])
    print(json.dumps(output, ensure_ascii=False))
