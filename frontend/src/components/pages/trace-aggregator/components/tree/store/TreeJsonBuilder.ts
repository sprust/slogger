import {TraceTreeNode} from "./traceAggregatorTreeStore.ts";

type ServiceNames = {
    [key: number]: { name: string }
}

export class TreeJsonBuilder {
    private readonly services: ServiceNames

    public constructor(services: ServiceNames) {
        this.services = services
    }

    public build(nodes: TraceTreeNode[]): unknown {
        if (nodes.length === 1) {
            return this.node(nodes[0])
        }

        return nodes.map(node => this.node(node))
    }

    public count(nodes: TraceTreeNode[]): number {
        return nodes.reduce((total, node) => total + 1 + this.count(node.children), 0)
    }

    private node(node: TraceTreeNode): Record<string, unknown> {
        const row = node.primary

        const json: Record<string, unknown> = {
            trace_id: row.trace_id,
            parent_trace_id: row.parent_trace_id ?? null,
            service_id: row.service_id,
            service: this.services[row.service_id]?.name ?? null,
            type: row.type,
            tags: row.tags,
            status: row.status,
            logged_at: row.logged_at,
            memory: row.memory ?? null,
            cpu: row.cpu ?? null,
            duration: row.duration ?? null,
        }

        if (node.children.length) {
            json.children = node.children.map(child => this.node(child))
        }

        return json
    }
}
