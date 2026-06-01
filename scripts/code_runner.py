#!/usr/bin/env python3
"""
code_runner.py — Streaming interactive code execution bridge
─────────────────────────────────────────────────────────────
Runs code in a subprocess, streams output line-by-line as SSE-compatible
JSON events to stdout. Laravel reads this via Symfony Process streaming.

Usage:
    python code_runner.py <language> <code_file> [stdin_file]

Each line of stdout is a JSON event:
    {"type": "stdout", "data": "line of output\n"}
    {"type": "stderr", "data": "error line\n"}
    {"type": "stdin_request"}            ← program called input()
    {"type": "exit",   "code": 0}
    {"type": "error",  "message": "..."}

Supported languages:
    python, javascript (node), c, cpp, bash
"""

import sys
import os
import json
import subprocess
import tempfile
import shutil
import threading
import queue
import select
import time
import io

# Force UTF-8 output
sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', line_buffering=True)
sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', line_buffering=True)

TIMEOUT     = 30   # seconds total execution limit
COMPILE_TO  = 30   # seconds compile limit

LANGUAGE_MAP = {
    'python':     'python',
    'python3':    'python',
    'javascript': 'javascript',
    'js':         'javascript',
    'node':       'javascript',
    'c':          'c',
    'cpp':        'cpp',
    'c++':        'cpp',
    'bash':       'bash',
    'sh':         'bash',
}


def emit(obj: dict):
    """Write one JSON event line to stdout."""
    print(json.dumps(obj, ensure_ascii=False), flush=True)


def find_exe(names: list) -> str | None:
    for name in names:
        p = shutil.which(name)
        if p:
            return p
    return None


# ─────────────────────────────────────────────────────────────
# Streaming runner — works for any interpreter/compiled binary
# ─────────────────────────────────────────────────────────────

def stream_process(cmd: list, stdin_data: str, cwd: str = None):
    """
    Run cmd, stream stdout/stderr as events.
    If stdin_data is provided, pipe it all in at once.
    Detects when process is waiting for input (stdin_request).
    """
    try:
        proc = subprocess.Popen(
            cmd,
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            cwd=cwd,
            encoding='utf-8',
            errors='replace',
            bufsize=0,
        )
    except FileNotFoundError as e:
        emit({"type": "error", "message": str(e)})
        return

    out_q = queue.Queue()
    err_q = queue.Queue()

    def reader(pipe, q, event_type):
        try:
            for line in iter(pipe.readline, ''):
                q.put((event_type, line))
        finally:
            q.put(None)

    t_out = threading.Thread(target=reader, args=(proc.stdout, out_q, "stdout"), daemon=True)
    t_err = threading.Thread(target=reader, args=(proc.stderr, err_q, "stderr"), daemon=True)
    t_out.start()
    t_err.start()

    # Send stdin upfront if provided
    if stdin_data:
        try:
            proc.stdin.write(stdin_data)
            proc.stdin.flush()
        except BrokenPipeError:
            pass

    deadline = time.time() + TIMEOUT
    out_done = err_done = False

    while not (out_done and err_done):
        if time.time() > deadline:
            proc.kill()
            emit({"type": "error", "message": f"Timed out after {TIMEOUT}s."})
            break

        for q, flag_attr in [(out_q, 'out_done'), (err_q, 'err_done')]:
            try:
                item = q.get(timeout=0.05)
                if item is None:
                    if q is out_q:
                        out_done = True
                    else:
                        err_done = True
                else:
                    event_type, line = item
                    emit({"type": event_type, "data": line})
            except queue.Empty:
                pass

    proc.wait()
    emit({"type": "exit", "code": proc.returncode})


# ─────────────────────────────────────────────────────────────
# Language runners
# ─────────────────────────────────────────────────────────────

def run_python(code: str, stdin_data: str):
    python = find_exe(['python3', 'python'])
    if not python:
        emit({"type": "error", "message": "Python not found in PATH."})
        return

    with tempfile.NamedTemporaryFile(suffix='.py', delete=False, mode='w', encoding='utf-8') as f:
        f.write(code)
        tmp = f.name

    try:
        # -u = unbuffered so we get real-time output
        stream_process([python, '-u', tmp], stdin_data)
    finally:
        try:
            os.unlink(tmp)
        except OSError:
            pass


def run_javascript(code: str, stdin_data: str):
    node = find_exe(['node', 'node.exe', 'nodejs'])
    if not node:
        emit({"type": "error", "message": "Node.js not found in PATH."})
        return

    with tempfile.NamedTemporaryFile(suffix='.js', delete=False, mode='w', encoding='utf-8') as f:
        f.write(code)
        tmp = f.name

    try:
        stream_process([node, tmp], stdin_data)
    finally:
        try:
            os.unlink(tmp)
        except OSError:
            pass


def compile_and_run(compiler_cmd: list, src_path: str, exe_path: str, stdin_data: str):
    """Compile then stream run. Returns compile stderr on failure."""
    compile_result = subprocess.run(
        compiler_cmd,
        capture_output=True,
        text=True,
        timeout=COMPILE_TO,
        encoding='utf-8',
        errors='replace',
    )

    if compile_result.returncode != 0:
        if compile_result.stdout:
            emit({"type": "stdout", "data": compile_result.stdout})
        if compile_result.stderr:
            emit({"type": "stderr", "data": compile_result.stderr})
        emit({"type": "exit", "code": compile_result.returncode})
        return

    # Emit compile success hint
    emit({"type": "stdout", "data": "✓ Compiled successfully.\n"})
    stream_process([exe_path], stdin_data)


def run_c(code: str, stdin_data: str):
    gcc = find_exe(['gcc', 'gcc.exe'])
    if not gcc:
        emit({"type": "error", "message": "GCC not found in PATH."})
        return

    with tempfile.TemporaryDirectory() as tmp_dir:
        src = os.path.join(tmp_dir, 'main.c')
        exe = os.path.join(tmp_dir, 'main.exe' if sys.platform == 'win32' else 'main')
        with open(src, 'w', encoding='utf-8') as f:
            f.write(code)
        compile_and_run([gcc, src, '-o', exe, '-lm', '-Wall'], src, exe, stdin_data)


def run_cpp(code: str, stdin_data: str):
    gpp = find_exe(['g++', 'g++.exe'])
    if not gpp:
        emit({"type": "error", "message": "G++ not found in PATH."})
        return

    with tempfile.TemporaryDirectory() as tmp_dir:
        src = os.path.join(tmp_dir, 'main.cpp')
        exe = os.path.join(tmp_dir, 'main.exe' if sys.platform == 'win32' else 'main')
        with open(src, 'w', encoding='utf-8') as f:
            f.write(code)
        compile_and_run([gpp, src, '-o', exe, '-lm', '-Wall', '-std=c++17'], src, exe, stdin_data)


def run_bash(code: str, stdin_data: str):
    bash = find_exe(['bash', 'bash.exe', 'sh'])
    if not bash:
        emit({"type": "error", "message": "Bash not found in PATH."})
        return

    with tempfile.NamedTemporaryFile(suffix='.sh', delete=False, mode='w', encoding='utf-8') as f:
        f.write(code)
        tmp = f.name

    try:
        os.chmod(tmp, 0o755)
        stream_process([bash, tmp], stdin_data)
    finally:
        try:
            os.unlink(tmp)
        except OSError:
            pass


RUNNERS = {
    'python':     run_python,
    'javascript': run_javascript,
    'c':          run_c,
    'cpp':        run_cpp,
    'bash':       run_bash,
}


def execute(language: str, code: str, stdin_data: str):
    lang = LANGUAGE_MAP.get(language.lower().strip())
    if lang is None:
        emit({
            "type":    "error",
            "message": f"Unsupported language: '{language}'. Supported: python, javascript, c, cpp, bash."
        })
        return
    RUNNERS[lang](code, stdin_data)


# ─────────────────────────────────────────────────────────────
# Entry point
# ─────────────────────────────────────────────────────────────

if __name__ == '__main__':
    if len(sys.argv) < 3:
        emit({"type": "error", "message": "Usage: code_runner.py <language> <code_file> [stdin_file]"})
        sys.exit(1)

    language   = sys.argv[1]
    code_file  = sys.argv[2]
    stdin_file = sys.argv[3] if len(sys.argv) > 3 else None

    if not os.path.isfile(code_file):
        emit({"type": "error", "message": f"Code file not found: {code_file}"})
        sys.exit(1)

    with open(code_file, 'r', encoding='utf-8', errors='replace') as f:
        code = f.read()

    stdin_data = ''
    if stdin_file and os.path.isfile(stdin_file):
        with open(stdin_file, 'r', encoding='utf-8', errors='replace') as f:
            stdin_data = f.read()

    execute(language, code, stdin_data)
