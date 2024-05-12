import {ProfilingItem, ProfilingMetrics} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class MetricsBuilder {
    private readonly indicatorName: string
    private readonly profilingItems: Array<ProfilingItem>

    public constructor(indicatorName: string, profilingItems: Array<ProfilingItem>) {
        this.indicatorName = indicatorName
        this.profilingItems = profilingItems
    }

    public build(): ProfilingMetrics {
        const profilingMetrics: ProfilingMetrics = {
            totalCount: 0,
            hardestItemIds: []
        }

        if (this.indicatorName) {
            this.buildRecursive(this.profilingItems, profilingMetrics, true)
        }

        return profilingMetrics
    }

    private buildRecursive(
        items: Array<ProfilingItem>,
        profilingMetrics: ProfilingMetrics,
        isHardestFlow: boolean
    ): void {
        if (items.length === 0) {
            return
        }

        const itemWithMaxCpuTime = items.reduce((prev, current) => {
            const prevValue = this.findIndicatorValue(prev)
            const currentValue = this.findIndicatorValue(current)

            return (prevValue > currentValue) ? prev : current
        })

        items.map((item: ProfilingItem) => {
            ++profilingMetrics.totalCount

            // @ts-ignore // todo: recursive oa scheme
            this.buildRecursive(item.callables, profilingMetrics, isHardestFlow && itemWithMaxCpuTime.id === item.id)
        })

        if (isHardestFlow) {
            profilingMetrics.hardestItemIds.push(itemWithMaxCpuTime?.id)
        }
    }

    private findIndicatorValue(profilingItem: ProfilingItem): number {
        const found = profilingItem.data.find(itemData => {
            return itemData.name === this.indicatorName
        })

        return found?.value ?? 0
    }
}
