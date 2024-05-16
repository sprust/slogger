import {ProfilingItem, ProfilingMetrics} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class MetricsCollector {
    private readonly indicatorName: string
    private readonly caller: string
    private readonly profilingItems: Array<ProfilingItem>

    private collectedCallers: Array<string> = []

    public constructor(indicatorName: string, caller: string, profilingItems: Array<ProfilingItem>) {
        this.indicatorName = indicatorName
        this.caller = caller
        this.profilingItems = profilingItems
    }

    public build(): ProfilingMetrics {
        const profilingMetrics: ProfilingMetrics = {
            totalCount: 0,
            hardestItemIds: []
        }

        this.collectedCallers = []

        if (this.indicatorName) {
            this.buildRecursive(this.filterItems(this.caller), profilingMetrics)
        }

        return profilingMetrics
    }

    private buildRecursive(
        items: Array<ProfilingItem>,
        profilingMetrics: ProfilingMetrics
    ): void {
        if (items.length === 0) {
            return
        }

        const itemWithMaxHardest = items.reduce((prev, current) => {
            const prevValue = this.findIndicatorValue(prev)
            const currentValue = this.findIndicatorValue(current)

            return (prevValue > currentValue) ? prev : current
        })

        if (!itemWithMaxHardest) {
            return;
        }

        if (this.collectedCallers.indexOf(itemWithMaxHardest.id) !== -1) {
            return;
        }

        this.collectedCallers.push(itemWithMaxHardest.id)

        profilingMetrics.hardestItemIds.push(itemWithMaxHardest?.id)

        this.buildRecursive(this.filterItems(itemWithMaxHardest.callable), profilingMetrics)
    }

    private findIndicatorValue(profilingItem: ProfilingItem): number {
        const found = profilingItem.data.find(itemData => {
            return itemData.name === this.indicatorName
        })

        return found?.value ?? 0
    }

    private filterItems(caller: string): Array<ProfilingItem> {
        return this.profilingItems.filter((item: ProfilingItem) => {
            return item.calling === caller
        })
    }
}
