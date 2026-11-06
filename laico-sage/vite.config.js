import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig(({ command }) => ({
  base: command === 'build' ? './' : '/',
  publicDir: false,
  build: {
    outDir: 'public/bundle',
    emptyOutDir: true,
    manifest: 'manifest.json',
    assetsDir: 'assets',
    assetsInlineLimit: 0,
    sourcemap: false,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.js'),
        styles: resolve(__dirname, 'resources/css/app.css'),
        editor: resolve(__dirname, 'resources/js/editor.js'),
        'editor-styles': resolve(__dirname, 'resources/css/editor.css'),
        admin: resolve(__dirname, 'resources/js/admin.js'),
        'admin-styles': resolve(__dirname, 'resources/css/admin.css'),
      },
      output: {
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },
  resolve: {
    alias: {
      '@scripts': resolve(__dirname, 'resources/js'),
      '@styles': resolve(__dirname, 'resources/css'),
      '@fonts': resolve(__dirname, 'resources/fonts'),
      '@images': resolve(__dirname, 'resources/images'),
    },
  },
}));
