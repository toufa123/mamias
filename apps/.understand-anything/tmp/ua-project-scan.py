#!/usr/bin/env python3
"""Project scanner — produces a structured inventory of a project directory."""
import json
import os
import re
import subprocess
import sys
from pathlib import Path

if len(sys.argv) < 3:
    print("Usage: ua-project-scan.py <project-root> <output-json>", file=sys.stderr)
    sys.exit(1)

ROOT = Path(sys.argv[1]).resolve()
OUT = Path(sys.argv[2])

if not ROOT.is_dir():
    print(f"Project root does not exist: {ROOT}", file=sys.stderr)
    sys.exit(1)


def safe_read(p):
    try:
        return p.read_text(encoding="utf-8", errors="replace")
    except Exception:
        return None


# --------------------------------------------------------------------------
# Step 1: discover files via git ls-files, fallback to walk
# --------------------------------------------------------------------------
all_files = []
used_git = False
try:
    res = subprocess.run(
        ["git", "ls-files"],
        cwd=str(ROOT),
        capture_output=True,
        text=True,
        check=True,
    )
    all_files = [l for l in res.stdout.split("\n") if l]
    used_git = True
except Exception:
    SKIP_DIRS = {"node_modules", ".git", "vendor", "venv", ".venv", "__pycache__"}
    for dirpath, dirnames, filenames in os.walk(ROOT):
        dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
        for f in filenames:
            rel = os.path.relpath(os.path.join(dirpath, f), ROOT).replace(os.sep, "/")
            all_files.append(rel)

original_count = len(all_files)

# --------------------------------------------------------------------------
# Step 2: gitignore-style matcher
# --------------------------------------------------------------------------
def glob_to_regex(pattern):
    p = re.sub(r"([.+^${}()|\\])", r"\\\1", pattern)
    p = p.replace("**/", "(?:.*/)?")
    p = p.replace("**", ".*")
    p = p.replace("*", "[^/]*")
    p = p.replace("?", "[^/]")
    return p


def compile_rule(raw):
    line = raw.strip()
    if not line or line.startswith("#"):
        return None
    negate = False
    if line.startswith("!"):
        negate = True
        line = line[1:]
    dir_only = False
    if line.endswith("/"):
        dir_only = True
        line = line[:-1]
    rooted = False
    if line.startswith("/"):
        rooted = True
        line = line[1:]
    basename = "/" not in line
    re_body = glob_to_regex(line)
    if basename:
        pat = "(?:^|/)" + re_body + ("(?:/|$)" if dir_only else "$")
    elif rooted:
        pat = "^" + re_body + ("(?:/|$)" if dir_only else "$")
    else:
        pat = "(?:^|/)" + re_body + ("(?:/|$)" if dir_only else "$")
    return (negate, re.compile(pat))


HARDCODED_DEFAULTS = [
    "node_modules/", ".git/", "vendor/", "venv/", ".venv/", "__pycache__/",
    "dist/", "build/", "out/", "coverage/", ".next/", ".cache/", ".turbo/", "target/", "obj/",
    "*.lock", "package-lock.json", "yarn.lock", "pnpm-lock.yaml",
    "*.png", "*.jpg", "*.jpeg", "*.gif", "*.svg", "*.ico",
    "*.woff", "*.woff2", "*.ttf", "*.eot",
    "*.mp3", "*.mp4", "*.pdf", "*.zip", "*.tar", "*.gz",
    "*.min.js", "*.min.css", "*.map", "*.generated.*",
    ".idea/", ".vscode/",
    "LICENSE", ".gitignore", ".editorconfig", ".prettierrc", ".eslintrc*", "*.log",
]


def read_ignore_file(p):
    c = safe_read(p)
    if not c:
        return []
    return [l.strip() for l in c.split("\n") if l.strip() and not l.strip().startswith("#")]


user_a = read_ignore_file(ROOT / ".understand-anything" / ".understandignore")
user_b = read_ignore_file(ROOT / ".understandignore")
has_user_ignore = bool(user_a or user_b)

all_patterns = HARDCODED_DEFAULTS + user_a + user_b
rules = [r for r in (compile_rule(p) for p in all_patterns) if r]


def is_ignored_by(rs, f):
    ig = False
    for negate, rx in rs:
        if rx.search(f):
            ig = not negate
    return ig


filtered = [f for f in all_files if not is_ignored_by(rules, f)]

filtered_by_ignore = 0
if has_user_ignore:
    hardcoded_rules = [r for r in (compile_rule(p) for p in HARDCODED_DEFAULTS) if r]
    hardcoded_kept = [f for f in all_files if not is_ignored_by(hardcoded_rules, f)]
    filtered_by_ignore = max(0, len(hardcoded_kept) - len(filtered))

# --------------------------------------------------------------------------
# Step 3 & 4: language + category
# --------------------------------------------------------------------------
EXT_LANG = {
    ".ts": "typescript", ".tsx": "typescript",
    ".js": "javascript", ".jsx": "javascript", ".mjs": "javascript", ".cjs": "javascript",
    ".py": "python",
    ".go": "go",
    ".rs": "rust",
    ".java": "java",
    ".rb": "ruby",
    ".cpp": "cpp", ".cc": "cpp", ".cxx": "cpp", ".h": "cpp", ".hpp": "cpp",
    ".c": "c",
    ".cs": "csharp",
    ".swift": "swift",
    ".kt": "kotlin",
    ".php": "php",
    ".vue": "vue",
    ".svelte": "svelte",
    ".sh": "shell", ".bash": "shell",
    ".ps1": "powershell",
    ".bat": "batch", ".cmd": "batch",
    ".md": "markdown", ".rst": "markdown",
    ".yaml": "yaml", ".yml": "yaml",
    ".json": "json",
    ".jsonc": "jsonc",
    ".toml": "toml",
    ".sql": "sql",
    ".graphql": "graphql", ".gql": "graphql",
    ".proto": "protobuf",
    ".tf": "terraform", ".tfvars": "terraform",
    ".html": "html", ".htm": "html",
    ".css": "css", ".scss": "css", ".sass": "css", ".less": "css",
    ".xml": "xml",
    ".cfg": "config", ".ini": "config", ".env": "config",
}


def detect_language(f):
    base = os.path.basename(f)
    if base == "Dockerfile" or base.startswith("Dockerfile."):
        return "dockerfile"
    if base == "Makefile":
        return "makefile"
    if base == "Jenkinsfile":
        return "jenkinsfile"
    if base.endswith(".blade.php"):
        return "blade"
    _, ext = os.path.splitext(base)
    ext = ext.lower()
    if ext and ext in EXT_LANG:
        return EXT_LANG[ext]
    if not ext:
        return "unknown"
    return ext[1:].lower()


def detect_category(f):
    base = os.path.basename(f)
    _, ext = os.path.splitext(base)
    ext = ext.lower()
    lower = f.lower()

    if base == "Dockerfile" or base.startswith("Dockerfile."):
        return "infra"
    if base.startswith("docker-compose"):
        return "infra"
    if ext in (".tf", ".tfvars"):
        return "infra"
    if base in ("Makefile", "Jenkinsfile", "Procfile", "Vagrantfile"):
        return "infra"
    if lower.startswith(".github/workflows/"):
        return "infra"
    if base == ".gitlab-ci.yml":
        return "infra"
    if lower.startswith(".circleci/"):
        return "infra"
    if base.endswith(".k8s.yaml") or base.endswith(".k8s.yml"):
        return "infra"
    if re.search(r"(^|/)k8s/", f) or re.search(r"(^|/)kubernetes/", f):
        return "infra"

    if ext in (".md", ".rst", ".txt") and base != "LICENSE":
        return "docs"

    config_exts = {".yaml", ".yml", ".json", ".jsonc", ".toml", ".xml", ".cfg", ".ini", ".env"}
    if ext in config_exts:
        return "config"
    if base in ("tsconfig.json", "package.json", "pyproject.toml", "Cargo.toml", "go.mod", "composer.json"):
        return "config"

    if ext in (".sql", ".graphql", ".gql", ".proto", ".prisma", ".csv"):
        return "data"
    if base.endswith(".schema.json"):
        return "data"

    if ext in (".sh", ".bash", ".ps1", ".bat"):
        return "script"

    if ext in (".html", ".htm", ".css", ".scss", ".sass", ".less"):
        return "markup"

    return "code"


# --------------------------------------------------------------------------
# Step 5: count lines
# --------------------------------------------------------------------------
def count_lines(rel):
    p = ROOT / rel
    try:
        with p.open("rb") as fh:
            data = fh.read()
    except Exception:
        return 0
    if not data:
        return 0
    n = data.count(b"\n")
    if data[-1:] != b"\n":
        n += 1
    return n


line_counts = {f: count_lines(f) for f in filtered}

# --------------------------------------------------------------------------
# Step 6: framework detection
# --------------------------------------------------------------------------
frameworks = set()
project_name = None
raw_description = ""

composer_raw = safe_read(ROOT / "composer.json")
composer_json = None
if composer_raw:
    try:
        composer_json = json.loads(composer_raw)
    except Exception:
        composer_json = None

package_raw = safe_read(ROOT / "package.json")
package_json = None
if package_raw:
    try:
        package_json = json.loads(package_raw)
    except Exception:
        package_json = None

if composer_json:
    deps = {}
    deps.update(composer_json.get("require") or {})
    deps.update(composer_json.get("require-dev") or {})
    names_lower = [k.lower() for k in deps.keys()]

    def has(n):
        return n.lower() in names_lower

    def has_prefix(p):
        return any(k.startswith(p.lower()) for k in names_lower)

    if has("laravel/framework"):
        frameworks.add("Laravel")
    if has("filament/filament") or has_prefix("filament/"):
        frameworks.add("Filament")
    if has("livewire/livewire"):
        frameworks.add("Livewire")
    if has("pestphp/pest") or has_prefix("pestphp/"):
        frameworks.add("Pest")
    if has("phpunit/phpunit"):
        frameworks.add("PHPUnit")
    if has("laravel/pint"):
        frameworks.add("Pint")
    if has("rector/rector"):
        frameworks.add("Rector")
    if has("laravel/boost"):
        frameworks.add("Laravel Boost")
    if has("laravel/sanctum"):
        frameworks.add("Sanctum")
    if has_prefix("spatie/"):
        frameworks.add("Spatie")
    if has("clickbar/laravel-magellan"):
        frameworks.add("Magellan (PostGIS)")
    if has("laravel/horizon"):
        frameworks.add("Horizon")
    if has("laravel/telescope"):
        frameworks.add("Telescope")
    if has("bezhansalleh/filament-shield"):
        frameworks.add("Filament Shield")

    if not project_name and composer_json.get("name"):
        project_name = composer_json["name"]
    if composer_json.get("description"):
        raw_description = composer_json["description"]

if package_json:
    deps = {}
    deps.update(package_json.get("dependencies") or {})
    deps.update(package_json.get("devDependencies") or {})

    def jhas(n):
        return n in deps

    if jhas("react"):
        frameworks.add("React")
    if jhas("vue"):
        frameworks.add("Vue")
    if jhas("svelte"):
        frameworks.add("Svelte")
    if jhas("@angular/core"):
        frameworks.add("Angular")
    if jhas("next"):
        frameworks.add("Next.js")
    if jhas("nuxt"):
        frameworks.add("Nuxt")
    if jhas("vite") or jhas("laravel-vite-plugin"):
        frameworks.add("Vite")
    if jhas("vitest"):
        frameworks.add("Vitest")
    if jhas("jest"):
        frameworks.add("Jest")
    if jhas("mocha"):
        frameworks.add("Mocha")
    if jhas("tailwindcss"):
        frameworks.add("Tailwind CSS")
    if jhas("alpinejs"):
        frameworks.add("Alpine.js")
    if jhas("axios"):
        frameworks.add("Axios")
    if not project_name and package_json.get("name"):
        project_name = package_json["name"]
    if not raw_description and package_json.get("description"):
        raw_description = package_json["description"]

for f in filtered:
    base = os.path.basename(f)
    if base == "Dockerfile" or base.startswith("Dockerfile."):
        frameworks.add("Docker")
    if base.startswith("docker-compose"):
        frameworks.add("Docker Compose")
    if f.endswith(".tf"):
        frameworks.add("Terraform")
    if f.startswith(".github/workflows/") and (f.endswith(".yml") or f.endswith(".yaml")):
        frameworks.add("GitHub Actions")
    if base == ".gitlab-ci.yml":
        frameworks.add("GitLab CI")
    if base == "Jenkinsfile":
        frameworks.add("Jenkins")

if not project_name:
    project_name = ROOT.name


def complexity_for(n):
    if n <= 30:
        return "small"
    if n <= 150:
        return "moderate"
    if n <= 500:
        return "large"
    return "very-large"


# README head
readme_head = ""
for c in ("README.md", "README.rst", "README.txt", "README"):
    r = safe_read(ROOT / c)
    if r:
        readme_head = "\n".join(r.split("\n")[:10])
        break

# --------------------------------------------------------------------------
# files array
# --------------------------------------------------------------------------
files = sorted(
    [
        {
            "path": p,
            "language": detect_language(p),
            "sizeLines": line_counts.get(p, 0),
            "fileCategory": detect_category(p),
        }
        for p in filtered
    ],
    key=lambda f: f["path"],
)

languages = sorted({f["language"] for f in files})

# --------------------------------------------------------------------------
# Step 9: import resolution
# --------------------------------------------------------------------------
file_set = {f["path"] for f in files}
import_map = {}

psr4_map = {}
if composer_json:
    autoload = composer_json.get("autoload") or {}
    for ns, d in (autoload.get("psr-4") or {}).items():
        psr4_map[ns] = d if isinstance(d, list) else [d]
    autoload_dev = composer_json.get("autoload-dev") or {}
    for ns, d in (autoload_dev.get("psr-4") or {}).items():
        psr4_map[ns] = d if isinstance(d, list) else [d]


def try_resolve(candidate):
    return candidate if candidate in file_set else None


def normalize_posix(*parts):
    return os.path.normpath("/".join(parts)).replace(os.sep, "/")


def resolve_js_like(from_file, imp):
    if not imp.startswith("."):
        return None
    from_dir = os.path.dirname(from_file)
    base = normalize_posix(from_dir, imp) if from_dir else os.path.normpath(imp).replace(os.sep, "/")
    exts = [".ts", ".tsx", ".js", ".jsx", ".mjs", ".cjs", ".vue", ".svelte"]
    if try_resolve(base):
        return base
    for e in exts:
        r = try_resolve(base + e)
        if r:
            return r
    for e in exts:
        r = try_resolve(base + "/index" + e)
        if r:
            return r
    return None


def resolve_php(fqcn):
    # try longest namespace prefix first
    for ns in sorted(psr4_map.keys(), key=len, reverse=True):
        if fqcn.startswith(ns):
            rel = fqcn[len(ns):].replace("\\", "/")
            for d in psr4_map[ns]:
                cand = normalize_posix(d.rstrip("/"), rel + ".php")
                if try_resolve(cand):
                    return cand
    return None


PHP_USE_RE = re.compile(
    r"^\s*use\s+(?:function\s+|const\s+)?([A-Za-z_\\][\w\\]*)(?:\s+as\s+\w+)?\s*;",
    re.MULTILINE,
)
PHP_GROUP_RE = re.compile(
    r"^\s*use\s+(?:function\s+|const\s+)?([A-Za-z_\\][\w\\]*)\\\{([^}]+)\}\s*;",
    re.MULTILINE,
)


def strip_php_comments(s):
    s = re.sub(r"//[^\n]*", "", s)
    s = re.sub(r"/\*[\s\S]*?\*/", "", s)
    return s


def extract_php_imports(content):
    stripped = strip_php_comments(content)
    out = []
    for m in PHP_USE_RE.finditer(stripped):
        out.append(m.group(1).strip())
    for m in PHP_GROUP_RE.finditer(stripped):
        prefix = m.group(1)
        for x in m.group(2).split(","):
            name = re.sub(r"\s+as\s+\w+$", "", x.strip(), flags=re.IGNORECASE).strip()
            if name:
                out.append(prefix + "\\" + name)
    return out


JS_IMPORT_RE = re.compile(r"""import\s+(?:[^'"]+?\s+from\s+)?['"]([^'"]+)['"]""")
JS_REQUIRE_RE = re.compile(r"""require\(\s*['"]([^'"]+)['"]\s*\)""")
JS_DYN_RE = re.compile(r"""import\(\s*['"]([^'"]+)['"]\s*\)""")


def extract_js_imports(content):
    out = []
    out.extend(JS_IMPORT_RE.findall(content))
    out.extend(JS_REQUIRE_RE.findall(content))
    out.extend(JS_DYN_RE.findall(content))
    return out


for f in files:
    import_map[f["path"]] = []
    if f["fileCategory"] != "code":
        continue
    content = safe_read(ROOT / f["path"])
    if not content:
        continue
    resolved = set()
    lang = f["language"]
    if lang in ("php", "blade"):
        for imp in extract_php_imports(content):
            r = resolve_php(imp)
            if r and r != f["path"]:
                resolved.add(r)
    elif lang in ("javascript", "typescript", "vue", "svelte"):
        for imp in extract_js_imports(content):
            r = resolve_js_like(f["path"], imp)
            if r and r != f["path"]:
                resolved.add(r)
    import_map[f["path"]] = sorted(resolved)

# --------------------------------------------------------------------------
# write result
# --------------------------------------------------------------------------
result = {
    "scriptCompleted": True,
    "name": project_name or ROOT.name,
    "rawDescription": raw_description or "",
    "readmeHead": readme_head,
    "languages": languages,
    "frameworks": sorted(frameworks),
    "files": files,
    "totalFiles": len(files),
    "filteredByIgnore": filtered_by_ignore,
    "estimatedComplexity": complexity_for(len(files)),
    "importMap": import_map,
}

OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.write_text(json.dumps(result, indent=2))
print(f"Wrote {len(files)} files (from {original_count} discovered, usedGit={used_git}, filteredByIgnore={filtered_by_ignore})")
