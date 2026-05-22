<script setup lang="ts">
import { Head, router, setLayoutProps, usePage } from '@inertiajs/vue3';
import { CalendarDays, RotateCcw } from '@lucide/vue';
import { computed, ref, watch, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';

type FunnelMetric = {
    status: ProposalStatusValue;
    count: number;
    value_cents: number;
};

type MonthlyRevenueMetric = {
    month: string;
    revenue_cents: number;
};

type ProposalStatusValue =
    | 'draft'
    | 'sent'
    | 'viewed'
    | 'accepted'
    | 'declined';

type DashboardMetrics = {
    range: {
        from: string;
        to: string;
    };
    active: {
        count: number;
        value_cents: number;
    };
    conversion: {
        rolling_30_days: {
            sent: number;
            accepted: number;
            rate: number;
        };
    };
    revenue_won: {
        range_cents: number;
    };
    funnel: FunnelMetric[];
    monthly_revenue: MonthlyRevenueMetric[];
};

type Props = {
    metrics: DashboardMetrics;
    filters: {
        start_date: string | null;
        end_date: string | null;
    };
};

type DateRange = (Date | null)[] | null;

const props = defineProps<Props>();

const { t } = useTranslations();
const page = usePage();
const selectedRange = ref<DateRange>(initialRange());

const dashboardUrl = '/dashboard';
const statusColors: Record<ProposalStatusValue, string> = {
    draft: '#64748b',
    sent: '#2563eb',
    viewed: '#f59e0b',
    accepted: '#16a34a',
    declined: '#dc2626',
};

const locale = computed(() => String(page.props.locale ?? 'en'));

const wonCount = computed(() => countForStatus('accepted'));
const lostCount = computed(() => countForStatus('declined'));
const conversionRate = computed(
    () => props.metrics.conversion.rolling_30_days.rate,
);

const kpis = computed(() => [
    {
        label: t('ui.dashboard.kpis.pipeline_value'),
        value: formatCurrency(props.metrics.active.value_cents),
    },
    {
        label: t('ui.dashboard.kpis.active_count'),
        value: new Intl.NumberFormat(locale.value).format(
            props.metrics.active.count,
        ),
    },
    {
        label: t('ui.dashboard.kpis.conversion_rate'),
        value: formatPercent(conversionRate.value),
    },
    {
        label: t('ui.dashboard.kpis.won_lost'),
        value: `${wonCount.value} / ${lostCount.value}`,
    },
]);

const monthlyTrendData = computed(() => ({
    labels: props.metrics.monthly_revenue.map((metric) =>
        formatMonth(metric.month),
    ),
    datasets: [
        {
            type: 'bar',
            label: t('ui.dashboard.series.won_value'),
            data: props.metrics.monthly_revenue.map((metric) =>
                centsToDollars(metric.revenue_cents),
            ),
            backgroundColor: '#2563eb',
            borderRadius: 6,
        },
        {
            type: 'line',
            label: t('ui.dashboard.series.won_value'),
            data: props.metrics.monthly_revenue.map((metric) =>
                centsToDollars(metric.revenue_cents),
            ),
            borderColor: '#16a34a',
            backgroundColor: '#16a34a',
            tension: 0.35,
        },
    ],
}));

const stateDistributionData = computed(() => ({
    labels: props.metrics.funnel.map((metric) =>
        t(`ui.proposals.statuses.${metric.status}`),
    ),
    datasets: [
        {
            data: props.metrics.funnel.map((metric) => metric.count),
            backgroundColor: props.metrics.funnel.map(
                (metric) => statusColors[metric.status],
            ),
            borderWidth: 0,
        },
    ],
}));

const trendOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            callbacks: {
                label: (context: { parsed: { y: number } }) =>
                    formatCurrency(Math.round(context.parsed.y * 100)),
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
        },
        y: {
            ticks: {
                callback: (value: string | number) =>
                    formatCurrency(Number(value) * 100),
            },
        },
    },
}));

const distributionOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        },
    },
};

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: t('ui.dashboard.index.head'),
                href: dashboardUrl,
            },
        ],
    });
});

watch(selectedRange, (range) => {
    if (!Array.isArray(range) || !(range[0] instanceof Date)) {
        return;
    }

    if (range[1] !== null && !(range[1] instanceof Date)) {
        return;
    }

    applyFilters(range[0], range[1] ?? null);
});

function initialRange(): DateRange {
    const start = props.filters.start_date
        ? dateFromIso(props.filters.start_date)
        : dateFromIso(props.metrics.range.from);
    const end = props.filters.end_date
        ? dateFromIso(props.filters.end_date)
        : dateFromIso(props.metrics.range.to);

    return [start, end];
}

function applyFilters(startDate: Date, endDate: Date | null): void {
    router.get(
        dashboardUrl,
        {
            start_date: formatDate(startDate),
            end_date: endDate ? formatDate(endDate) : undefined,
        },
        {
            only: ['metrics', 'filters'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function clearFilters(): void {
    selectedRange.value = null;

    router.get(
        dashboardUrl,
        {},
        {
            only: ['metrics', 'filters'],
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function countForStatus(status: ProposalStatusValue): number {
    return (
        props.metrics.funnel.find((metric) => metric.status === status)
            ?.count ?? 0
    );
}

function centsToDollars(cents: number): number {
    return cents / 100;
}

function formatCurrency(cents: number): string {
    return new Intl.NumberFormat(locale.value, {
        style: 'currency',
        currency: 'USD',
        maximumFractionDigits: 0,
    }).format(centsToDollars(cents));
}

function formatPercent(value: number): string {
    return new Intl.NumberFormat(locale.value, {
        style: 'percent',
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    }).format(value / 100);
}

function formatMonth(month: string): string {
    return new Intl.DateTimeFormat(locale.value, {
        month: 'short',
        year: 'numeric',
    }).format(dateFromIso(`${month}-01`));
}

function formatDate(date: Date): string {
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${month}-${day}`;
}

function dateFromIso(value: string): Date {
    return new Date(`${value}T00:00:00`);
}
</script>

<template>
    <Head :title="t('ui.dashboard.index.head')" />

    <div class="flex flex-col gap-6 p-4">
        <div
            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
        >
            <Heading
                :title="t('ui.dashboard.index.title')"
                :description="t('ui.dashboard.index.description')"
            />

            <div
                class="flex w-full flex-col gap-2 sm:w-auto sm:min-w-80 sm:flex-row sm:items-end"
            >
                <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                    <label
                        id="dashboard-date-range-label"
                        for="dashboard-date-range"
                        class="text-sm font-medium text-surface-700 dark:text-surface-200"
                    >
                        {{ t('ui.dashboard.filters.date_range') }}
                    </label>
                    <DatePicker
                        v-model="selectedRange"
                        input-id="dashboard-date-range"
                        name="date_range"
                        selection-mode="range"
                        date-format="yy-mm-dd"
                        :manual-input="false"
                        :show-clear="true"
                        :aria-labelledby="'dashboard-date-range-label'"
                        :placeholder="t('ui.dashboard.filters.placeholder')"
                        fluid
                    />
                </div>

                <Button
                    severity="secondary"
                    variant="outlined"
                    class="shrink-0"
                    :aria-label="t('ui.dashboard.filters.clear')"
                    @click="clearFilters"
                >
                    <RotateCcw class="size-4" />
                    <span>{{ t('ui.dashboard.filters.clear') }}</span>
                </Button>
            </div>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="kpi in kpis"
                :key="kpi.label"
                class="rounded-md border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900"
            >
                <p
                    class="text-sm font-medium text-surface-500 dark:text-surface-400"
                >
                    {{ kpi.label }}
                </p>
                <p
                    class="mt-3 truncate text-2xl font-semibold text-surface-950 dark:text-surface-0"
                >
                    {{ kpi.value }}
                </p>
            </div>
        </section>

        <section
            class="grid gap-4 xl:grid-cols-[minmax(0,1.6fr)_minmax(20rem,1fr)]"
        >
            <div
                class="rounded-md border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900"
            >
                <div class="mb-4 flex items-center gap-2">
                    <CalendarDays class="size-4 text-surface-500" />
                    <h3
                        class="text-base font-semibold text-surface-950 dark:text-surface-0"
                    >
                        {{ t('ui.dashboard.charts.value_trend') }}
                    </h3>
                </div>
                <div class="h-80">
                    <Chart
                        type="bar"
                        :data="monthlyTrendData"
                        :options="trendOptions"
                    />
                </div>
            </div>

            <div
                class="rounded-md border border-surface-200 bg-surface-0 p-5 dark:border-surface-700 dark:bg-surface-900"
            >
                <h3
                    class="mb-4 text-base font-semibold text-surface-950 dark:text-surface-0"
                >
                    {{ t('ui.dashboard.charts.state_distribution') }}
                </h3>
                <div class="h-80">
                    <Chart
                        type="doughnut"
                        :data="stateDistributionData"
                        :options="distributionOptions"
                    />
                </div>
            </div>
        </section>
    </div>
</template>
