import {ProfilingItem} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class IndicatorsCollector {
    private indicators: Array<string> = []

    public collect(items: Array<ProfilingItem>): Array<string> {
        this.indicators = []

        this.collectRecursive(items)

        return this.indicators
    }

    private collectRecursive(items: Array<ProfilingItem>): void {
        items.map((item: ProfilingItem) => {
            item.data.map(itemData => {
                if (this.indicators.indexOf(itemData.name) === -1) {
                    this.indicators.push(itemData.name)
                }
            })

            this.collectRecursive(item.callables)
        })
    }
}
