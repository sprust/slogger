import {ProfilingItem} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class IndicatorsCollector {
    public collect(items: Array<ProfilingItem>): Array<string> {
        const indicators: Array<string> = []

        items.map((item: ProfilingItem) => {
            item.data.map(itemData => {
                if (indicators.indexOf(itemData.name) === -1) {
                    indicators.push(itemData.name)
                }
            })
        })

        return indicators
    }
}
