import {TraceAggregatorTreeRow, TraceTreeNode} from "./traceAggregatorTreeStore.ts";

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

        this.fillDepthRecursive(0, rootNodes);

        return rootNodes;
    }

    private createNode(row: TraceAggregatorTreeRow): TraceTreeNode {
        return {
            id: row.trace_id,
            depth: 0,
            primary: row,
            children: []
        };
    }

    private fillDepthRecursive(depth: number, rows: TraceTreeNode[]) {
        rows.forEach(row => {
            row.depth = depth;

            this.fillDepthRecursive(depth + 1, row.children);
        })
    }
}
