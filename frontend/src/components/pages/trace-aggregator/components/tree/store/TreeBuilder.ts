import {TraceAggregatorTreeRow, TraceTreeNode} from "./traceAggregatorTreeStore.ts";

export class TreeBuilder {
    public build(rows: TraceAggregatorTreeRow[]): TraceTreeNode[] {
        const rootNodes: TraceTreeNode[] = [];
        const nodeMap = new Map<string, TraceTreeNode>();

        rows.forEach(row => {
            const node = this.createNode(row);

            nodeMap.set(row.trace_id, node);

            if (!row.parent_trace_id) {
                rootNodes.push(node);
            } else {
                const parentNode = nodeMap.get(row.parent_trace_id);
                if (parentNode) {
                    if (!parentNode.children) {
                        parentNode.children = [];
                    }

                    parentNode.children.push(node);
                }
            }
        });

        return rootNodes;
    }

    private createNode(row: TraceAggregatorTreeRow): TraceTreeNode {
        return {
            id: row.trace_id,
            primary: row,
            children: []
        };
    }
}
