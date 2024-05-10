import {ProfilingItem} from "../../../../../store/traceAggregatorProfilingStore.ts";

export class ProfilingItemFinder {
    public find(id: string, items: Array<ProfilingItem>): ProfilingItem | null {
        return this.findRecursive(id, items)
    }

    private findRecursive(id: string, items: Array<ProfilingItem>): ProfilingItem | null {
        for (let i = 0; i < items.length; i++) {
            const item = items[i]

            if (item.id == id) {
                return item;
            }

            const foundItem = this.findRecursive(id, item.callables);

            if (foundItem) {
                return foundItem
            }
        }

        return null
    }
}
