import {TraceAggregatorTreeRow, TraceTreeNode} from "./traceAggregatorTreeStore.ts";

// TODO: refactor for recursive

export class TreeBuilder {
    public build(rows: TraceAggregatorTreeRow[]): TraceTreeNode[] {
        const rootNodes: TraceTreeNode[] = [];
        const nodeMap = new Map<string, TraceTreeNode>();

        rows.forEach(row => {
            const node = this.createNode(row);
            nodeMap.set(row.trace_id, node);
        });

        rows.forEach(row => {
            const node = nodeMap.get(row.trace_id);

            if (!row.parent_trace_id) {
                rootNodes.push(node!);
            } else {
                const parentNode = nodeMap.get(row.parent_trace_id);

                if (parentNode) {
                    parentNode.children.push(node!);
                }
            }
        })

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
