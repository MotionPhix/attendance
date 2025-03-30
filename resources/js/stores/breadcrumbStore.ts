import { defineStore } from 'pinia';
import type { BreadcrumbItemType } from '@/types';

export const useBreadcrumbStore = defineStore('breadcrumb', {
  state: () => ({
    breadcrumbs: [] as BreadcrumbItemType[],
  }),

  actions: {
    /**
     * Set the current breadcrumbs
     */
    setBreadcrumbs(breadcrumbs: BreadcrumbItemType[]) {
      this.breadcrumbs = breadcrumbs;
    },

    /**
     * Add a breadcrumb item
     */
    addBreadcrumb(breadcrumb: BreadcrumbItemType) {
      this.breadcrumbs.push(breadcrumb);
    },

    /**
     * Clear all breadcrumbs
     */
    clearBreadcrumbs() {
      this.breadcrumbs = [];
    },

    /**
     * Set breadcrumbs from a simple array of labels and optional hrefs
     */
    setBreadcrumbsFromArray(items: Array<{ label: string, href?: string }>) {
      this.breadcrumbs = items.map(item => ({
        label: item.label,
        href: item.href,
      }));
    },
  },
});
