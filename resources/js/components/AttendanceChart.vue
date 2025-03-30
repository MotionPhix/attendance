<template>
  <div class="attendance-chart-container">
    <div v-if="loading" class="flex justify-center items-center h-64">
      <Loader2Icon class="animate-spin h-12 w-12 text-blue-500" />
    </div>
    <div v-else>
      <apexchart
        :type="type"
        :height="height"
        :options="chartOptions"
        :series="series">
      </apexchart>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { Loader2Icon } from 'lucide-vue-next';
import { useColorMode } from '@vueuse/core';

interface TrendData {
  month: string;
  attendance_rate: number;
  late_arrivals: number;
  early_departures: number;
}

interface Props {
  trends: TrendData[];
  type?: 'line' | 'bar' | 'area';
  height?: number;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'line',
  height: 350
});

const loading = ref(true);
const { isDark } = useColorMode();

// Prepare series data for ApexCharts
const series = computed(() => [
  {
    name: 'Attendance Rate (%)',
    type: 'line',
    data: props.trends.map(trend => trend.attendance_rate)
  },
  {
    name: 'Late Arrivals',
    type: 'column',
    data: props.trends.map(trend => trend.late_arrivals)
  },
  {
    name: 'Early Departures',
    type: 'column',
    data: props.trends.map(trend => trend.early_departures)
  }
]);

// Chart options
const chartOptions = computed(() => ({
  chart: {
    height: props.height,
    type: props.type,
    stacked: false,
    toolbar: {
      show: true
    },
    zoom: {
      enabled: true
    },
    fontFamily: 'inherit',
    foreColor: isDark.value ? '#CBD5E1' : '#475569',
    background: 'transparent'
  },
  plotOptions: {
    bar: {
      columnWidth: '50%'
    }
  },
  stroke: {
    width: [3, 1, 1],
    curve: 'smooth'
  },
  colors: ['#3b82f6', '#f59e0b', '#ef4444'],
  title: {
    text: 'Attendance Trends',
    align: 'left',
    style: {
      fontSize: '16px',
      fontWeight: 600,
      color: isDark.value ? '#F1F5F9' : '#1E293B'
    }
  },
  fill: {
    opacity: [0.85, 0.75, 0.75],
    gradient: {
      inverseColors: false,
      shade: 'light',
      type: "vertical",
      opacityFrom: 0.85,
      opacityTo: 0.55,
    }
  },
  markers: {
    size: 0,
    hover: {
      sizeOffset: 6
    }
  },
  xaxis: {
    categories: props.trends.map(trend => trend.month),
    labels: {
      style: {
        colors: isDark.value ? '#CBD5E1' : '#475569'
      }
    }
  },
  yaxis: [
    {
      title: {
        text: 'Attendance Rate (%)',
        style: {
          color: isDark.value ? '#CBD5E1' : '#475569'
        }
      },
      min: 0,
      max: 100,
      labels: {
        style: {
          colors: isDark.value ? '#CBD5E1' : '#475569'
        }
      }
    },
    {
      opposite: true,
      title: {
        text: 'Count',
        style: {
          color: isDark.value ? '#CBD5E1' : '#475569'
        }
      },
      min: 0,
      labels: {
        style: {
          colors: isDark.value ? '#CBD5E1' : '#475569'
        }
      }
    }
  ],
  tooltip: {
    shared: true,
    intersect: false,
    theme: isDark.value ? 'dark' : 'light',
    y: {
      formatter: function (value: number, { seriesIndex }: { seriesIndex: number }) {
        if (seriesIndex === 0) {
          return value.toFixed(1) + '%';
        }
        return value;
      }
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'left',
    offsetX: 40,
    labels: {
      colors: isDark.value ? '#CBD5E1' : '#475569'
    }
  },
  grid: {
    borderColor: isDark.value ? '#334155' : '#E2E8F0',
    strokeDashArray: 4
  }
}));

onMounted(() => {
  loading.value = false;
});

// Watch for dark mode changes to update chart theme
watch(isDark, () => {
  // The computed chartOptions will automatically update with new theme colors
}, { immediate: true });
</script>

<style scoped>
.attendance-chart-container {
  @apply bg-white dark:bg-gray-800 p-4 rounded-lg shadow;
  min-height: v-bind('props.height + "px"');
}
</style>
