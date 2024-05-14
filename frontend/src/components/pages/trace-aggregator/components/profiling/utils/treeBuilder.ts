import {ProfilingItem, ProfilingTreeNode} from "../../../../../../store/traceAggregatorProfilingStore.ts";

export class ProfilingTreeBuilder {
    public build(caller: string, items: Array<ProfilingItem>): Array<ProfilingTreeNode> {
        return items
            .filter((item: ProfilingItem) => {
                return item.calling === caller
            })
            .map((item: ProfilingItem) => {
                return {
                    key: item.id,
                    label: item.callable,
                    disabled: false,
                    isLeaf: this.hasChildren(item.callable, items),
                }
            })
    }

    private hasChildren(caller: string, items: Array<ProfilingItem>): boolean {
        return items.findIndex((item: ProfilingItem) => {
            return item.calling === caller
        }) !== -1
    }
}
