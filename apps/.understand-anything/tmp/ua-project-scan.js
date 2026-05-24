#!/usr/bin/env node
'use strict';

const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');

const root = process.argv[2];
const outPath = process.argv[3];

if (!root || !outPath) {
  console.error('Usage: ua-project-scan.js <project-root> <output-json>');
  process.exit(1);
}

function safeRead(p) {
  try { return fs.readFileSync(p, 'utf8'); } catch { return null; }
}

// --------------------------------------------------------------------------
// Step 1: Discover files via `git ls-files` using execFileSync (no shell)
// --------------------------------------------------------------------------
let allFiles = [];
let usedGit = false;
try {
  const out = execFileSync('git', ['ls-files'], {
    cwd: root,
    encoding: 'utf8',
    maxBuffer: 256 * 1024 * 1024,
  });
  allFiles = out.split('\n').filter(Boolean);
  usedGit = true;
} catch {
  // Recursive walk fallback
  function walk(dir, base = '') {
    let entries;
    try { entries = fs.readdirSync(dir, { withFileTypes: true }); } catch { return; }
    for (const ent of entries) {
      const rel = base ? base + '/' + ent.name : ent.name;
      if (ent.isDirectory()) {
        if (['node_modules', '.git', 'vendor', 'venv', '.venv', '__pycache__'].includes(ent.name)) continue;
        walk(path.join(dir, ent.name), rel);
      } else if (ent.isFile()) {
        allFiles.push(rel);
      }
    }
  }
  walk(root);
}

const originalCount = allFiles.length;

// --------------------------------------------------------------------------
// Step 2 hardcoded defaults — gitignore-style matcher
// --------------------------------------------------------------------------
function globToRegex(pattern) {
  let p = pattern;
  p = p.replace(/[.+^${}()|\\]/g, '\\$&');
  p = p.replace(/\*\*\//g, '(?:.*/)?');
  p = p.replace(/\*\*/g, '.*');
  p = p.replace(/\*/g, '[^/]*');
  p = p.replace(/\?/g, '[^/]');
  return p;
}

function compileRule(raw) {
  let line = raw.trim();
  if (!line || line.startsWith('#')) return null;
  let negate = false;
  if (line.startsWith('!')) { negate = true; line = line.slice(1); }
  let dirOnly = false;
  if (line.endsWith('/')) { dirOnly = true; line = line.slice(0, -1); }
  let rooted = false;
  if (line.startsWith('/')) { rooted = true; line = line.slice(1); }
  const basename = !line.includes('/');
  const re = globToRegex(line);
  let pattern;
  if (basename) {
    pattern = '(?:^|/)' + re + (dirOnly ? '(?:/|$)' : '$');
  } else if (rooted) {
    pattern = '^' + re + (dirOnly ? '(?:/|$)' : '$');
  } else {
    pattern = '(?:^|/)' + re + (dirOnly ? '(?:/|$)' : '$');
  }
  return { negate, regex: new RegExp(pattern) };
}

const HARDCODED_DEFAULTS = [
  'node_modules/', '.git/', 'vendor/', 'venv/', '.venv/', '__pycache__/',
  'dist/', 'build/', 'out/', 'coverage/', '.next/', '.cache/', '.turbo/', 'target/', 'obj/',
  '*.lock', 'package-lock.json', 'yarn.lock', 'pnpm-lock.yaml',
  '*.png', '*.jpg', '*.jpeg', '*.gif', '*.svg', '*.ico',
  '*.woff', '*.woff2', '*.ttf', '*.eot',
  '*.mp3', '*.mp4', '*.pdf', '*.zip', '*.tar', '*.gz',
  '*.min.js', '*.min.css', '*.map', '*.generated.*',
  '.idea/', '.vscode/',
  'LICENSE', '.gitignore', '.editorconfig', '.prettierrc', '.eslintrc*', '*.log',
];

function readIgnoreFile(p) {
  const c = safeRead(p);
  if (!c) return [];
  return c.split('\n').map(l => l.trim()).filter(l => l && !l.startsWith('#'));
}

const userPatternsA = readIgnoreFile(path.join(root, '.understand-anything', '.understandignore'));
const userPatternsB = readIgnoreFile(path.join(root, '.understandignore'));
const hasUserIgnore = userPatternsA.length > 0 || userPatternsB.length > 0;

const allPatterns = [...HARDCODED_DEFAULTS, ...userPatternsA, ...userPatternsB];
const rules = allPatterns.map(compileRule).filter(Boolean);

function isIgnoredBy(rs, file) {
  let ig = false;
  for (const r of rs) { if (r.regex.test(file)) ig = !r.negate; }
  return ig;
}

const filtered = allFiles.filter(f => !isIgnoredBy(rules, f));

let filteredByIgnore = 0;
if (hasUserIgnore) {
  const hardcodedRules = HARDCODED_DEFAULTS.map(compileRule).filter(Boolean);
  const hardcodedKept = allFiles.filter(f => !isIgnoredBy(hardcodedRules, f));
  filteredByIgnore = hardcodedKept.length - filtered.length;
  if (filteredByIgnore < 0) filteredByIgnore = 0;
}

// --------------------------------------------------------------------------
// Step 3 & 4: Language + category
// --------------------------------------------------------------------------
const EXT_LANG = {
  '.ts': 'typescript', '.tsx': 'typescript',
  '.js': 'javascript', '.jsx': 'javascript', '.mjs': 'javascript', '.cjs': 'javascript',
  '.py': 'python',
  '.go': 'go',
  '.rs': 'rust',
  '.java': 'java',
  '.rb': 'ruby',
  '.cpp': 'cpp', '.cc': 'cpp', '.cxx': 'cpp', '.h': 'cpp', '.hpp': 'cpp',
  '.c': 'c',
  '.cs': 'csharp',
  '.swift': 'swift',
  '.kt': 'kotlin',
  '.php': 'php',
  '.vue': 'vue',
  '.svelte': 'svelte',
  '.sh': 'shell', '.bash': 'shell',
  '.ps1': 'powershell',
  '.bat': 'batch', '.cmd': 'batch',
  '.md': 'markdown', '.rst': 'markdown',
  '.yaml': 'yaml', '.yml': 'yaml',
  '.json': 'json',
  '.jsonc': 'jsonc',
  '.toml': 'toml',
  '.sql': 'sql',
  '.graphql': 'graphql', '.gql': 'graphql',
  '.proto': 'protobuf',
  '.tf': 'terraform', '.tfvars': 'terraform',
  '.html': 'html', '.htm': 'html',
  '.css': 'css', '.scss': 'css', '.sass': 'css', '.less': 'css',
  '.xml': 'xml',
  '.cfg': 'config', '.ini': 'config', '.env': 'config',
};

function detectLanguage(file) {
  const base = path.basename(file);
  if (base === 'Dockerfile' || base.startsWith('Dockerfile.')) return 'dockerfile';
  if (base === 'Makefile') return 'makefile';
  if (base === 'Jenkinsfile') return 'jenkinsfile';
  if (base.endsWith('.blade.php')) return 'blade';
  const ext = path.extname(base).toLowerCase();
  if (ext && EXT_LANG[ext]) return EXT_LANG[ext];
  if (!ext) return 'unknown';
  return ext.slice(1).toLowerCase();
}

function detectCategory(file) {
  const base = path.basename(file);
  const ext = path.extname(base).toLowerCase();
  const lower = file.toLowerCase();

  if (base === 'Dockerfile' || base.startsWith('Dockerfile.')) return 'infra';
  if (base.startsWith('docker-compose')) return 'infra';
  if (ext === '.tf' || ext === '.tfvars') return 'infra';
  if (base === 'Makefile') return 'infra';
  if (base === 'Jenkinsfile') return 'infra';
  if (base === 'Procfile' || base === 'Vagrantfile') return 'infra';
  if (lower.startsWith('.github/workflows/')) return 'infra';
  if (base === '.gitlab-ci.yml') return 'infra';
  if (lower.startsWith('.circleci/')) return 'infra';
  if (base.endsWith('.k8s.yaml') || base.endsWith('.k8s.yml')) return 'infra';
  if (/(^|\/)k8s\//.test(file) || /(^|\/)kubernetes\//.test(file)) return 'infra';

  if (['.md', '.rst', '.txt'].includes(ext) && base !== 'LICENSE') return 'docs';

  const configExts = ['.yaml', '.yml', '.json', '.jsonc', '.toml', '.xml', '.cfg', '.ini', '.env'];
  if (configExts.includes(ext)) return 'config';
  if (['tsconfig.json', 'package.json', 'pyproject.toml', 'Cargo.toml', 'go.mod', 'composer.json'].includes(base)) return 'config';

  if (['.sql', '.graphql', '.gql', '.proto', '.prisma', '.csv'].includes(ext)) return 'data';
  if (base.endsWith('.schema.json')) return 'data';

  if (['.sh', '.bash', '.ps1', '.bat'].includes(ext)) return 'script';

  if (['.html', '.htm', '.css', '.scss', '.sass', '.less'].includes(ext)) return 'markup';

  return 'code';
}

// --------------------------------------------------------------------------
// Step 5: Line counts (read files and count newlines — avoids spawning wc)
// --------------------------------------------------------------------------
const lineCounts = new Map();
function countLines(rel) {
  try {
    const buf = fs.readFileSync(path.join(root, rel));
    if (buf.length === 0) return 0;
    let n = 0;
    for (let i = 0; i < buf.length; i++) {
      if (buf[i] === 10) n++;
    }
    // If file doesn't end with newline, treat last partial line as a line
    if (buf[buf.length - 1] !== 10) n++;
    return n;
  } catch { return 0; }
}
for (const f of filtered) lineCounts.set(f, countLines(f));

// --------------------------------------------------------------------------
// Step 6: Framework detection
// --------------------------------------------------------------------------
const frameworks = new Set();
let projectName = null;
let rawDescription = '';

const composerJsonRaw = safeRead(path.join(root, 'composer.json'));
let composerJson = null;
if (composerJsonRaw) { try { composerJson = JSON.parse(composerJsonRaw); } catch {} }

const packageJsonRaw = safeRead(path.join(root, 'package.json'));
let packageJson = null;
if (packageJsonRaw) { try { packageJson = JSON.parse(packageJsonRaw); } catch {} }

if (composerJson) {
  const deps = { ...(composerJson.require || {}), ...(composerJson['require-dev'] || {}) };
  const depNames = Object.keys(deps);
  const has = (n) => depNames.some(d => d.toLowerCase() === n.toLowerCase());
  const hasPrefix = (p) => depNames.some(d => d.toLowerCase().startsWith(p.toLowerCase()));

  if (has('laravel/framework')) frameworks.add('Laravel');
  if (has('filament/filament') || hasPrefix('filament/')) frameworks.add('Filament');
  if (has('livewire/livewire')) frameworks.add('Livewire');
  if (has('pestphp/pest') || hasPrefix('pestphp/')) frameworks.add('Pest');
  if (has('phpunit/phpunit')) frameworks.add('PHPUnit');
  if (has('laravel/pint')) frameworks.add('Pint');
  if (has('rector/rector')) frameworks.add('Rector');
  if (has('laravel/boost')) frameworks.add('Laravel Boost');
  if (has('laravel/sanctum')) frameworks.add('Sanctum');
  if (hasPrefix('spatie/')) frameworks.add('Spatie');
  if (has('clickbar/laravel-magellan')) frameworks.add('Magellan (PostGIS)');
  if (has('laravel/horizon')) frameworks.add('Horizon');
  if (has('laravel/telescope')) frameworks.add('Telescope');

  if (!projectName && composerJson.name) projectName = composerJson.name;
  if (composerJson.description) rawDescription = composerJson.description;
}

if (packageJson) {
  const deps = { ...(packageJson.dependencies || {}), ...(packageJson.devDependencies || {}) };
  const has = (n) => Object.prototype.hasOwnProperty.call(deps, n);
  if (has('react')) frameworks.add('React');
  if (has('vue')) frameworks.add('Vue');
  if (has('svelte')) frameworks.add('Svelte');
  if (has('@angular/core')) frameworks.add('Angular');
  if (has('next')) frameworks.add('Next.js');
  if (has('nuxt')) frameworks.add('Nuxt');
  if (has('vite') || has('laravel-vite-plugin')) frameworks.add('Vite');
  if (has('vitest')) frameworks.add('Vitest');
  if (has('jest')) frameworks.add('Jest');
  if (has('mocha')) frameworks.add('Mocha');
  if (has('tailwindcss')) frameworks.add('Tailwind CSS');
  if (has('alpinejs')) frameworks.add('Alpine.js');
  if (has('axios')) frameworks.add('Axios');
  if (!projectName && packageJson.name) projectName = packageJson.name;
  if (!rawDescription && packageJson.description) rawDescription = packageJson.description;
}

for (const f of filtered) {
  const base = path.basename(f);
  if (base === 'Dockerfile' || base.startsWith('Dockerfile.')) frameworks.add('Docker');
  if (base.startsWith('docker-compose')) frameworks.add('Docker Compose');
  if (f.endsWith('.tf')) frameworks.add('Terraform');
  if (f.startsWith('.github/workflows/') && (f.endsWith('.yml') || f.endsWith('.yaml'))) frameworks.add('GitHub Actions');
  if (base === '.gitlab-ci.yml') frameworks.add('GitLab CI');
  if (base === 'Jenkinsfile') frameworks.add('Jenkins');
}

if (!projectName) projectName = path.basename(root);

// --------------------------------------------------------------------------
// Step 7: complexity
// --------------------------------------------------------------------------
function complexityFor(n) {
  if (n <= 30) return 'small';
  if (n <= 150) return 'moderate';
  if (n <= 500) return 'large';
  return 'very-large';
}

// --------------------------------------------------------------------------
// README head
// --------------------------------------------------------------------------
let readmeHead = '';
for (const c of ['README.md', 'README.rst', 'README.txt', 'README']) {
  const r = safeRead(path.join(root, c));
  if (r) { readmeHead = r.split('\n').slice(0, 10).join('\n'); break; }
}

// --------------------------------------------------------------------------
// Build files array
// --------------------------------------------------------------------------
const files = filtered.map(p => ({
  path: p,
  language: detectLanguage(p),
  sizeLines: lineCounts.get(p) ?? 0,
  fileCategory: detectCategory(p),
})).sort((a, b) => a.path.localeCompare(b.path));

const languages = Array.from(new Set(files.map(f => f.language))).sort();

// --------------------------------------------------------------------------
// Step 9: Import resolution
// --------------------------------------------------------------------------
const fileSet = new Set(files.map(f => f.path));
const importMap = {};

const psr4Map = {};
if (composerJson && composerJson.autoload && composerJson.autoload['psr-4']) {
  for (const [ns, dir] of Object.entries(composerJson.autoload['psr-4'])) {
    psr4Map[ns] = Array.isArray(dir) ? dir : [dir];
  }
}
if (composerJson && composerJson['autoload-dev'] && composerJson['autoload-dev']['psr-4']) {
  for (const [ns, dir] of Object.entries(composerJson['autoload-dev']['psr-4'])) {
    psr4Map[ns] = Array.isArray(dir) ? dir : [dir];
  }
}

function tryResolve(candidate) {
  return fileSet.has(candidate) ? candidate : null;
}

function resolveJsLike(fromFile, importStr) {
  if (!importStr.startsWith('.')) return null;
  const fromDir = path.posix.dirname(fromFile);
  const base = path.posix.normalize(path.posix.join(fromDir, importStr));
  const exts = ['.ts', '.tsx', '.js', '.jsx', '.mjs', '.cjs', '.vue', '.svelte'];
  if (tryResolve(base)) return base;
  for (const e of exts) { const r = tryResolve(base + e); if (r) return r; }
  for (const e of exts) { const r = tryResolve(base + '/index' + e); if (r) return r; }
  return null;
}

function resolvePhp(fqcn) {
  // Try every PSR-4 prefix, longest first
  const nsKeys = Object.keys(psr4Map).sort((a, b) => b.length - a.length);
  for (const ns of nsKeys) {
    if (fqcn.startsWith(ns)) {
      const rel = fqcn.slice(ns.length).replace(/\\/g, '/');
      for (const dir of psr4Map[ns]) {
        const cand = path.posix.normalize((dir.replace(/\/$/, '')) + '/' + rel + '.php');
        if (tryResolve(cand)) return cand;
      }
    }
  }
  return null;
}

function extractPhpImports(content) {
  const results = [];
  // Strip line comments to avoid `// use Foo\Bar;` false positives
  const stripped = content.replace(/\/\/[^\n]*/g, '').replace(/\/\*[\s\S]*?\*\//g, '');
  // Plain: use Vendor\Pkg\Class; (with optional 'as Alias' and optional 'function'/'const' keyword)
  const useRe = /^\s*use\s+(?:function\s+|const\s+)?([A-Za-z_\\][\w\\]*)(?:\s+as\s+\w+)?\s*;/gm;
  let m;
  while ((m = useRe.exec(stripped)) !== null) results.push(m[1].trim());
  // Grouped: use Vendor\Pkg\{A, B as C};
  const groupRe = /^\s*use\s+(?:function\s+|const\s+)?([A-Za-z_\\][\w\\]*)\\\{([^}]+)\}\s*;/gm;
  while ((m = groupRe.exec(stripped)) !== null) {
    const prefix = m[1];
    const inner = m[2].split(',').map(s => s.trim().replace(/\s+as\s+\w+$/i, '').trim()).filter(Boolean);
    for (const x of inner) results.push(prefix + '\\' + x);
  }
  return results;
}

function extractJsImports(content) {
  const results = [];
  const importRe = /import\s+(?:[^'"]+?\s+from\s+)?['"]([^'"]+)['"]/g;
  const requireRe = /require\(\s*['"]([^'"]+)['"]\s*\)/g;
  const dynRe = /import\(\s*['"]([^'"]+)['"]\s*\)/g;
  let m;
  while ((m = importRe.exec(content)) !== null) results.push(m[1]);
  while ((m = requireRe.exec(content)) !== null) results.push(m[1]);
  while ((m = dynRe.exec(content)) !== null) results.push(m[1]);
  return results;
}

for (const f of files) {
  importMap[f.path] = [];
  if (f.fileCategory !== 'code') continue;

  let content;
  try { content = fs.readFileSync(path.join(root, f.path), 'utf8'); } catch { continue; }
  if (!content) continue;

  const resolved = new Set();
  const lang = f.language;

  if (lang === 'php' || lang === 'blade') {
    for (const imp of extractPhpImports(content)) {
      const r = resolvePhp(imp);
      if (r && r !== f.path) resolved.add(r);
    }
  } else if (lang === 'javascript' || lang === 'typescript' || lang === 'vue' || lang === 'svelte') {
    for (const imp of extractJsImports(content)) {
      const r = resolveJsLike(f.path, imp);
      if (r && r !== f.path) resolved.add(r);
    }
  }

  importMap[f.path] = Array.from(resolved).sort();
}

// --------------------------------------------------------------------------
// Write
// --------------------------------------------------------------------------
const result = {
  scriptCompleted: true,
  name: projectName || path.basename(root),
  rawDescription: rawDescription || '',
  readmeHead,
  languages,
  frameworks: Array.from(frameworks).sort(),
  files,
  totalFiles: files.length,
  filteredByIgnore,
  estimatedComplexity: complexityFor(files.length),
  importMap,
};

fs.mkdirSync(path.dirname(outPath), { recursive: true });
fs.writeFileSync(outPath, JSON.stringify(result, null, 2));
console.log(`Wrote ${files.length} files (from ${originalCount} discovered, usedGit=${usedGit}, filteredByIgnore=${filteredByIgnore})`);
