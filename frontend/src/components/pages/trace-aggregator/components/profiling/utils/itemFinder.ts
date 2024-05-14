import {ProfilingItem} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class ProfilingItemFinder {
    // TODO: find by calling and callable
    public find(calling: string, items: Array<ProfilingItem>): ProfilingItem | null {
        for (let i = 0; i < items.length; i++) {
            const item = items[i]

            if (item.calling == calling) {
                return item;
            }
        }

        return null
    }
}
