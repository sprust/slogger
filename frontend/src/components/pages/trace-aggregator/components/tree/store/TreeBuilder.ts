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

        const nodes: TraceTreeNode[] = [];

        this.fillDepthRecursive(nodes, 0, rootNodes);

        return nodes;
    }

    private createNode(row: TraceAggregatorTreeRow): TraceTreeNode {
        return {
            id: row.trace_id,
            depth: 0,
            primary: row,
            children: [],
            collapsed: false,
            isHiddenByFilter: false,
            indicatorPercent: 0,
        };
    }

    private fillDepthRecursive(nodes: TraceTreeNode[], depth: number, rows: TraceTreeNode[]) {
        rows.sort((a, b) => {
            return new Date(a.primary.logged_at).getTime() - new Date(b.primary.logged_at).getTime()
        })

        rows.forEach(row => {
            row.depth = depth;

            nodes.push(row);

            this.fillDepthRecursive(nodes, depth + 1, row.children);
        })
    }
}
