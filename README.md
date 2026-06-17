# Modular Course Creator

A self-hosted e-learning platform built with Laravel 12. Teachers can build structured courses from modular content blocks, upload PDFs to auto-generate course content using a local AI pipeline, and track student progress in real time.

---

## Features

- **Course builder** — organize content into Courses → Chapters → Lessons → Blocks (text, code, math, exercises, headings, notes)
- **AI course generation** — upload a PDF and let a local LLM (Ollama) extract and structure it into a full course automatically
- **Interactive code editor** — students can write and run code directly inside lessons; supports Python, JavaScript, TypeScript, C/C++, Java, and Rust via a local Piston execution engine
- **Judge mode** — admins can attach test cases to coding exercises; student submissions are graded automatically
- **Progress tracking** — per-lesson, per-chapter, and per-course progress tracked for every student
- **Quiz engine** — multiple-choice quizzes attached to courses
- **Calendar** — shared event calendar for admins and students
- **Role-based access** — separate admin and student interfaces
- **LaTeX support** — math blocks render LaTeX expressions

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12, PHP 8.2 |
| Frontend | Livewire 4, Vite, CodeMirror 6 |
| Database | SQLite (default) |
| AI / LLM | Ollama (local, default model: `phi4`) |
| PDF extraction | MinerU (Python) + smalot/pdfparser |
| Code execution | Piston (self-hosted, via Podman or Docker) |
| Queue | Laravel queue worker |

---

## Requirements

- PHP 8.2+
- Composer
- Node.js 18+
- [Ollama](https://ollama.com) — for the AI course generation feature
- [Piston](https://github.com/engineer-man/piston) — for the interactive code editor feature (optional)
- Python 3.10+ with [MinerU](https://github.com/opendatalab/MinerU) — for PDF extraction (optional)

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/YounesDeghima/modular-course-creator.git
cd modular-course-creator

# 2. Install PHP dependencies
composer install

# 3. Set up environment
cp .env.example .env
php artisan key:generate

# 4. Run database migrations
php artisan migrate

# 5. Install and build frontend assets
npm install
npm run build
```

---

## Running the App

You need three processes running at the same time. On Windows you can just run:

```bat
start_everything.bat
```

Or start them manually:

```bash
php artisan serve        # web server → http://localhost:8000
php artisan queue:work   # background job processor (AI pipeline)
ollama serve             # local LLM
```

---

## AI Course Generation (Optional)

The AI pipeline converts a PDF into a structured course using MinerU for extraction and Ollama for structuring.

**Setup:**

1. Install Ollama and pull a model:
   ```bash
   ollama pull phi4
   ```
2. Set up the Python environment for MinerU:
   ```bash
   cd scripts
   python -m venv .venv
   .venv/Scripts/pip install mineru   # Windows
   # or
   .venv/bin/pip install mineru       # Linux/macOS
   ```
3. Go to **Admin → AI Panel** to upload a PDF and monitor jobs.

---

## Interactive Code Editor (Optional)

The code editor feature requires a running [Piston](https://github.com/engineer-man/piston) instance on `localhost:2000`.

**Quick start with Docker:**

```bash
docker run --rm -d \
  -p 2000:2000 \
  --name piston \
  ghcr.io/engineer-man/piston
```

Once running, the code editor is available at `/admin/editor` and `/user/editor`, and code blocks inside lessons will be executable.

---

## Project Structure

```
app/
  Http/Controllers/   — route controllers (courses, blocks, AI, code editor…)
  Models/             — Eloquent models (course, chapter, lesson, block, user…)
  Jobs/               — background jobs (ProcessPdfJob)
  Services/           — service classes
resources/
  views/
    pages/admin/      — admin views (dashboard, courses, chapters, lessons, blocks, AI panel, code editor)
    pages/user/       — student views (home, preview, code editor)
    components/       — reusable Livewire components (block create/edit)
database/
  migrations/         — 25 migrations
  seeders/            — database seeders
scripts/
  mineru_extract.py   — Python script used by the AI pipeline
routes/
  web.php             — all application routes
```

---

## Branches

| Branch | Description |
|---|---|
| `main` | Stable branch |
| `codeeditor` | Interactive code editor + judge mode |
| `aiintergration` | AI pipeline development |
| `calendar` | Calendar / events feature |
| `livewire-copy` | Livewire component work |
| `ineteractivecode` | Interactive code experiments |

---

## Known Issues

- CRUD errors in some admin views (in progress)
- `userEditor()` in `CodeEditorController` has an undefined `$user` variable (fix: add `$user = Auth::user();`)
- Duplicate route definitions for `/api/code/*` in `codeeditor` branch

---

## License

MIT
