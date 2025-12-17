import {TraceTreeNode} from "./traceAggregatorTreeStore.ts";

export class TreeFilter {
    private serviceIds: Array<number>
    private types: Array<string>
    private tags: Array<string>
    private statuses: Array<string>

    constructor(
        serviceIds: Array<number>,
        types: Array<string>,
        tags: Array<string>,
        statuses: Array<string>,
    ) {
        this.serviceIds = serviceIds
        this.types = types
        this.tags = tags
        this.statuses = statuses
    }

    public apply(rows: TraceTreeNode[]): void {
        rows.forEach((row: TraceTreeNode) => {
            this.applyForRow(row)

            this.apply(row.children)

            const hasVisibleDescendant = this.hasVisibleDescendants(row)

            if (hasVisibleDescendant) {
                row.isHiddenByFilter = false
            }
        })
    }

    private hasVisibleDescendants(node: TraceTreeNode): boolean {
        if (!node.isHiddenByFilter) {
            return true
        }

        return node.children.some(child => this.hasVisibleDescendants(child))
    }

    private applyForRow(row: TraceTreeNode): void {
        if (!row.primary) {
            row.isHiddenByFilter = true
            return
        }

        const primary = row.primary

        if (this.isServiceFiltered(primary.service_id)) {
            row.isHiddenByFilter = true
            return
        }

        if (this.isTypeFiltered(primary.type)) {
            row.isHiddenByFilter = true
            return
        }

        if (this.areTagsFiltered(primary.tags)) {
            row.isHiddenByFilter = true
            return
        }

        if (this.isStatusFiltered(primary.status)) {
            row.isHiddenByFilter = true
            return
        }

        row.isHiddenByFilter = false
    }

    private isServiceFiltered(serviceId: number): boolean {
        return this.serviceIds.length > 0 && !this.serviceIds.includes(serviceId)
    }

    private isTypeFiltered(type: string): boolean {
        return this.types.length > 0 && !this.types.includes(type)
    }

    private areTagsFiltered(tags: string[]): boolean {
        if (this.tags.length === 0) {
            return false
        }
        if (tags.length === 0) {
            return true
        }
        return !tags.some(tag => this.tags.includes(tag))
    }

    private isStatusFiltered(status: string): boolean {
        return this.statuses.length > 0 && !this.statuses.includes(status)
    }
}
