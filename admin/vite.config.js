import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  base: './',
  server: {
    port: 3000,
    host: '0.0.0.0',
    proxy: {
      '/api': {
        // target: 'http://127.0.0.1:8000/',
        target: 'https://tougao.njlinks.cn/',
        changeOrigin: true,
        secure: true,
        configure: (proxy, options) => {
          proxy.on('error', (err, req, res) => {
            console.log('代理错误:', err.message);
          });
          proxy.on('proxyReq', (proxyReq, req, res) => {
            console.log('代理请求:', req.method, req.url);
          });
        },
      }
    }
  },
  build: {
    outDir: '../admin-dist', // 编译后的静态资源放到 public/admin-dist
    emptyOutDir: true,
    assetsDir: 'assets',
    sourcemap: false,
    minify: 'terser',
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
  define: {
    __VUE_PROD_DEVTOOLS__: false,
  }
}); 