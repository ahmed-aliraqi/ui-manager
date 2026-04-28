import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default defineConfig({
  // Public URL from which the assets are served after vendor:publish.
  base: '/vendor/ui-manager/',

  // Never copy a publicDir into outDir — the package has no static public dir.
  publicDir: false,

  plugins: [
    vue(),
    tailwindcss(),
  ],

  css: {
    preprocessorOptions: {
      scss: {
        // Bootstrap 5.3 uses legacy Sass APIs — silence the noise.
        silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'],
      },
    },
  },

  resolve: {
    alias: {
      '@': resolve(__dirname, 'resources/js/ui-manager'),
    },
  },

  build: {
    // Output to dist/ — a clean, flat distribution directory.
    // The service provider publishes dist/ → <app>/public/vendor/ui-manager/
    // so the manifest lands at public/vendor/ui-manager/manifest.json.
    outDir: 'dist',
    emptyOutDir: true,
    manifest: 'manifest.json',
    rollupOptions: {
      input: {
        panel: resolve(__dirname, 'resources/js/ui-manager/panel.js'),
      },
      output: {
        entryFileNames: '[name]-[hash].js',
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash][extname]',
      },
    },
  },

  server: {
    hmr: {
      host: 'localhost',
    },
  },
})
