import {TraceAggregatorTreeRow, TraceTreeNode} from "./traceAggregatorTreeStore.ts";

// TODO: refactor for recursive

export class TreeBuilder {
    public build(rows: TraceAggregatorTreeRow[]): TraceTreeNode[] {
        const rootNodes: TraceTreeNode[] = [];
        const nodeMap = new Map<string, TraceTreeNode>();

        const remainRows = rows.filter(row => {
            const node = this.createNode(row);

            nodeMap.set(row.trace_id, node);

            if (!row.parent_trace_id) {
                rootNodes.push(node);

                return false
            } else {
                const parentNode = nodeMap.get(row.parent_trace_id);

                if (parentNode) {
                    if (!parentNode.children) {
                        parentNode.children = [];
                    }

                    parentNode.children.push(node);
                    parentNode.children = parentNode.children.sort(
                        (a, b) => new Date(a.primary.logged_at).getTime() - new Date(b.primary.logged_at).getTime()
                    )

                    return false
                }

                return true
            }
        })

        remainRows.forEach(row => {
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
                    parentNode.children = parentNode.children.sort(
                        (a, b) => new Date(a.primary.logged_at).getTime() - new Date(b.primary.logged_at).getTime()
                    )
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
