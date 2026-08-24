#!/usr/bin/env node
/**
 * ARIKARTECH — Local HTTPS Reverse Proxy
 *
 * Terminates TLS for all three local dev services using the shared mkcert certificate.
 *
 *   https://127.0.0.1:3000  →  http://127.0.0.1:3001  (Next.js)
 *   https://127.0.0.1:8000  →  http://127.0.0.1:8080  (Laravel artisan serve)
 *   https://127.0.0.1:5173  →  http://127.0.0.1:5174  (Vite admin)
 *
 * Usage: node scripts/local-https-proxy.mjs
 */

import https from 'node:https';
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const CERT_DIR = path.join(__dirname, '..', '.certs');

const tlsOptions = {
  key: fs.readFileSync(path.join(CERT_DIR, 'local-key.pem')),
  cert: fs.readFileSync(path.join(CERT_DIR, 'local-cert.pem')),
};

const PROXIES = [
  { name: 'Next.js',  httpsPort: 3000, httpPort: 3001 },
  { name: 'Laravel',  httpsPort: 8000, httpPort: 8080 },
  { name: 'Vite',     httpsPort: 5173, httpPort: 5174 },
];

const HOST = '127.0.0.1';

function createProxy({ name, httpsPort, httpPort }) {
  const server = https.createServer(tlsOptions, (req, res) => {
    const proxyReq = http.request(
      {
        host: HOST,
        port: httpPort,
        path: req.url,
        method: req.method,
        headers: {
          ...req.headers,
          host: `${HOST}:${httpPort}`,
          'x-forwarded-proto': 'https',
          'x-forwarded-for': req.socket.remoteAddress || HOST,
        },
      },
      (proxyRes) => {
        res.writeHead(proxyRes.statusCode, proxyRes.headers);
        proxyRes.pipe(res, { end: true });
      }
    );

    proxyReq.on('error', (err) => {
      if (!res.headersSent) {
        res.writeHead(502, { 'Content-Type': 'text/plain' });
        res.end(`502 — ${name} upstream (port ${httpPort}) unreachable: ${err.message}`);
      }
    });

    req.pipe(proxyReq, { end: true });
  });

  server.listen(httpsPort, HOST, () => {
    console.log(`[proxy] ${name.padEnd(8)} https://${HOST}:${httpsPort}  →  http://${HOST}:${httpPort}`);
  });

  server.on('error', (err) => {
    console.error(`[proxy] ${name} error on :${httpsPort} — ${err.message}`);
    if (err.code === 'EADDRINUSE') {
      console.error(`        Kill the process on port ${httpsPort} and retry.`);
    }
  });
}

console.log('[proxy] Starting ARIKARTECH HTTPS proxy...');
PROXIES.forEach(createProxy);
console.log('[proxy] Press Ctrl+C to stop.\n');
