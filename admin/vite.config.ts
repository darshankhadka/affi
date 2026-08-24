import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

/**
 * Vite admin dev server configuration.
 *
 * In development, Vite runs on plain HTTP at http://127.0.0.1:5174.
 * The HTTPS TLS proxy (scripts/local-https-proxy.mjs) terminates TLS
 * and exposes https://127.0.0.1:5173 to the browser.
 *
 * Run the full HTTPS stack via: npm run dev:https (from repo root)
 */
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5174,
    host: '127.0.0.1',
    // No HTTPS here — TLS is handled by the shared proxy in scripts/local-https-proxy.mjs
    proxy: {
      '/api': {
        // Proxy API calls through the HTTPS Laravel endpoint
        target: 'https://127.0.0.1:8000',
        changeOrigin: true,
        // Accept the mkcert certificate from the proxy upstream
        secure: true,
      },
    },
  },
});
