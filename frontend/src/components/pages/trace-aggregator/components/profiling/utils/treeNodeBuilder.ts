import {ProfilingItem, ProfilingTreeNode} from "../../../../../../store/traceAggregatorProfilingStore.ts";
import {ProfilingItemFinder} from "./itemFinder.ts";

export class ProfilingTreeNodeBuilder {
    private itemFinder = new ProfilingItemFinder()

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
                    leaf: !this.hasChildren(item.callable, items),
                }
            })
    }

    private hasChildren(caller: string, items: Array<ProfilingItem>): boolean {
        return !!this.itemFinder.findByCalling(caller, items)
    }
}
