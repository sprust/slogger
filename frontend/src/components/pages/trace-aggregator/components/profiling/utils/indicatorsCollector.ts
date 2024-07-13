import {ProfilingNode} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class IndicatorsCollector {
    private indicators: Array<string> = []

    public collect(items: Array<ProfilingNode>): Array<string> {
        this.indicators = []

        this.collectRecursive(items)

        return this.indicators
    }

    private collectRecursive(items: Array<ProfilingNode>): void {
        items.map((item: ProfilingNode) => {
            item.data.map(itemData => {
                if (this.indicators.indexOf(itemData.name) === -1) {
                    this.indicators.push(itemData.name)
                }
            })

            if (item.children) {
                // @ts-ignore
                this.collect(item.children)
            }
        })
    }
}
