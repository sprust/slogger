import {ProfilingItem, ProfilingTreeNode} from "../../../../../store/traceAggregatorProfilingStore.ts";

export class ProfilingTreeBuilder {
    public build(items: Array<ProfilingItem>): Array<ProfilingTreeNode> {
        return items.map((item: ProfilingItem) => {
            return {
                key: item.id,
                label: item.call,
                children: this.build(item.callables),
                disabled: false,
            }
        })
    }
}
