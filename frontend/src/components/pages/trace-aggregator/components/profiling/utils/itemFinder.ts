import {ProfilingItem} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class ProfilingItemFinder {
    public findByCalling(calling: string, items: Array<ProfilingItem>): ProfilingItem | null {
        return items.find((item: ProfilingItem) => {
            return item.calling === calling
        }) ?? null
    }

    public findById(id: string, items: Array<ProfilingItem>): ProfilingItem | null {
        return items.find((item: ProfilingItem) => {
            return item.id === id
        }) ?? null
    }
}
