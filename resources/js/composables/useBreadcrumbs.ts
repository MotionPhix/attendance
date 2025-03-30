import { useBreadcrumbStore } from '@/stores/breadcrumbStore';
import type { BreadcrumbItemType } from '@/types';
import { onMounted, onUnmounted } from 'vue';

/**
 * Composable for managing breadcrumbs
 */
export function useBreadcrumbs() {
  const breadcrumbStore = useBreadcrumbStore();

  /**
   * Set breadcrumbs
   */
  function setBreadcrumbs(breadcrumbs: BreadcrumbItemType[]) {
    breadcrumbStore.setBreadcrumbs(breadcrumbs);
  }

  /**
   * Set breadcrumbs and automatically clear them when the component is unmounted
   */
  function setPageBreadcrumbs(breadcrumbs: BreadcrumbItemType[]) {
    onMounted(() => {
      breadcrumbStore.setBreadcrumbs(breadcrumbs);
    });

    onUnmounted(() => {
      breadcrumbStore.clearBreadcrumbs();
    });
  }

  return {
    setBreadcrumbs,
    setPageBreadcrumbs,
    addBreadcrumb: breadcrumbStore.addBreadcrumb,
    clearBreadcrumbs: breadcrumbStore.clearBreadcrumbs,
  };
}
