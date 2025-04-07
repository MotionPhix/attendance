import '../css/app.css';
import 'v-calendar/style.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { renderApp, ModalLink } from '@inertiaui/modal-vue'
import { createPinia } from 'pinia'
import VueApexCharts from "vue3-apexcharts";
import { setupCalendar, Calendar, DatePicker } from 'v-calendar';

import { initializeTheme } from './composables/useAppearance';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
  interface ImportMetaEnv {
    readonly VITE_APP_NAME: string;

    [key: string]: string | boolean | undefined;
  }

  interface ImportMeta {
    readonly env: ImportMetaEnv;
    readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
  }
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia()

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    //createApp({ render: () => h(App, props) })
    createApp({ render: renderApp(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(pinia)
      .use(setupCalendar, {})
      .use(VueApexCharts)
      .component('ModalTrigger', ModalLink)
      .component('VCalendar', Calendar)
      .component('VDatePicker', DatePicker)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});

// This will set light / dark mode on page load...
initializeTheme();
