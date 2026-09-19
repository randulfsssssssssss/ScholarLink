const http = require('http');
const fs = require('fs');
const path = require('path');

const ROOT = __dirname;
const PUBLIC = path.join(ROOT, 'public');

const MIME = {
  '.html': 'text/html',
  '.css': 'text/css',
  '.js': 'application/javascript',
  '.json': 'application/json',
  '.png': 'image/png',
  '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.gif': 'image/gif',
  '.svg': 'image/svg+xml',
  '.ico': 'image/x-icon',
  '.woff': 'font/woff',
  '.woff2': 'font/woff2',
  '.ttf': 'font/ttf',
  '.eot': 'application/vnd.ms-fontobject',
  '.otf': 'font/otf',
  '.webp': 'image/webp',
};

const ROUTE_MAP = {
  '/': 'dashboard/auth/login.php',
  '/login': 'dashboard/auth/login.php',
  '/register': 'dashboard/auth/register.php',
  '/forgot': 'dashboard/auth/forgot-password.php',
  '/reset': 'dashboard/auth/reset-password.php',
  '/dashboard': 'dashboard/student/index.php',
  '/scholarships': 'dashboard/student/scholarships.php',
  '/bookmarks': 'dashboard/student/bookmarks.php',
  '/applications': 'dashboard/student/applications.php',
  '/messages': 'dashboard/messages/index.php',
  '/profile': 'dashboard/profile/index.php',
};

function stripPhp(content) {
  return content.replace(/<\?[\s\S]*?\?>/g, '');
}

function extractPhpIncludes(filePath) {
  const content = fs.readFileSync(filePath, 'utf-8');
  const includes = [];
  const patterns = [
    /(?:require_once|require|include_once|include)\s+SCHOLARLINK_ROOT\s*\.\s*['"]([^'"]+)['"]/g,
    /(?:require_once|require|include_once|include)\s+['"]([^'"]+)['"]/g,
  ];
  for (const pattern of patterns) {
    let match;
    while ((match = pattern.exec(content)) !== null) {
      includes.push(match[1]);
    }
  }
  return includes;
}

function buildPage(filePath) {
  if (!filePath || !fs.existsSync(filePath)) return null;
  const visited = new Set();

  function readAndResolve(fp) {
    if (visited.has(fp)) return '';
    visited.add(fp);
    if (!fs.existsSync(fp)) return '';
    const content = fs.readFileSync(fp, 'utf-8');
    const includes = extractPhpIncludes(fp);
    let result = content;
    for (const inc of includes) {
      let resolved;
      if (inc.startsWith('/')) {
        resolved = path.join(ROOT, inc);
      } else {
        resolved = path.join(path.dirname(fp), inc);
      }
      resolved = path.normalize(resolved);
      if (fs.existsSync(resolved) && fs.statSync(resolved).isFile()) {
        const incHtml = readAndResolve(resolved);
        const escaped = inc.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        result = result.replace(
          new RegExp(`<\\?php\\s*(?:require_once|require|include_once|include)\\s+(?:SCHOLARLINK_ROOT\\s*\\.\\s*)?['"]${escaped}['"]\\s*;\\s*\\?>`, 'g'),
          incHtml
        );
      }
    }
    return stripPhp(result);
  }

  let html = readAndResolve(filePath);

  if (!html.includes('site-header')) {
    const headerPath = path.join(ROOT, 'includes/header.php');
    const footerPath = path.join(ROOT, 'includes/footer.php');
    let wrap = '';
    if (fs.existsSync(headerPath)) wrap += readAndResolve(headerPath);
    wrap += html;
    if (fs.existsSync(footerPath)) wrap += readAndResolve(footerPath);
    html = wrap;
  }

  return html;
}

function resolvePhpFilePath(route) {
  if (!route) return null;
  const candidates = [path.join(ROOT, route)];
  for (const c of candidates) {
    if (fs.existsSync(c) && fs.statSync(c).isFile()) return c;
  }
  return null;
}

function getContentType(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  return MIME[ext] || 'application/octet-stream';
}

const server = http.createServer((req, res) => {
  const urlPath = decodeURIComponent(req.url.split('?')[0]);
  const filePath = path.join(PUBLIC, urlPath);

  if (fs.existsSync(filePath) && fs.statSync(filePath).isFile()) {
    const ext = path.extname(filePath).toLowerCase();
    if (ext === '.php') {
      const html = buildPage(filePath);
      if (html) {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(html);
        return;
      }
    } else {
      res.writeHead(200, { 'Content-Type': getContentType(filePath) });
      res.end(fs.readFileSync(filePath));
      return;
    }
  }

  const routeFile = resolveRoute(urlPath);
  if (routeFile) {
    const phpPath = resolvePhpFilePath(routeFile);
    if (phpPath) {
      const html = buildPage(phpPath);
      if (html) {
        res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
        res.end(html);
        return;
      }
    }
  }

  res.writeHead(404, { 'Content-Type': 'text/html' });
  res.end('<h1>404 Not Found</h1><p>Try <a href="/login">/login</a></p>');
});

function resolveRoute(urlPath) {
  const clean = urlPath.split('?')[0].replace(/\/+$/, '');
  if (clean === '') return 'dashboard/auth/login.php';
  if (clean.endsWith('.php')) return clean.slice(1);
  if (ROUTE_MAP[clean]) return ROUTE_MAP[clean];
  return null;
}

const PORT = 3000;
server.listen(PORT, () => {
  console.log(`ScholarLink preview server running at http://localhost:${PORT}`);
  console.log(`Login page: http://localhost:${PORT}/login`);
});
