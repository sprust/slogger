import {AdminApi, DirectionEnum} from "../../../../api-schema/admin-api-schema.ts";
import {ApiContainer} from "../../../../utils/apiContainer.ts";
import {defineStore} from "pinia";
import {ElMessage} from "element-plus";
import {handleApiError, handleApiRequest} from "../../../../utils/handleApiRequest.ts";

export type LogFile = AdminApi.LogsFilesList.ResponseBody['data'][number]
export type LogEntriesPage = AdminApi.LogsEntriesCreate.ResponseBody['data']
export type LogEntry = LogEntriesPage['items'][number]

type EntriesBody = AdminApi.LogsEntriesCreate.RequestBody

// How long an answer that the files are still being indexed waits before asking again.
const INDEXING_RETRY_MS = 1000

const PER_PAGE = 50

interface LogsViewerStoreInterface {
    filesLoading: boolean,
    filesLoaded: boolean,
    files: Array<LogFile>,
    selectedFileIds: Array<string>,
    levels: Array<string>,
    from: string | null,
    to: string | null,
    searchQuery: string,
    loading: boolean,
    continuing: boolean,
    page: LogEntriesPage | null,
    // Records checked by the search so far, over this page and its continuations.
    scanned: number,
    // Entries the last request brought; fewer than a page means the search stopped early.
    lastCount: number,
    requestNo: number,
    retryTimerId: number | null,
}

export const useLogsViewerStore = defineStore('logsViewerStore', {
    state: (): LogsViewerStoreInterface => {
        return {
            filesLoading: false,
            filesLoaded: false,
            files: [],
            selectedFileIds: [],
            levels: [],
            from: null,
            to: null,
            searchQuery: '',
            loading: false,
            continuing: false,
            page: null,
            scanned: 0,
            lastCount: 0,
            requestNo: 0,
            retryTimerId: null,
        }
    },
    getters: {
        isSearch(): boolean {
            return this.searchQuery.trim() !== ''
        },
        // A search stopped by its time or bytes budget before the page was full.
        canContinueSearch(): boolean {
            if (!this.page || !this.isSearch || !this.page.older_cursor) {
                return false
            }

            return this.lastCount < PER_PAGE
        },
    },
    actions: {
        async findFiles() {
            this.filesLoading = true

            await handleApiRequest(
                () => ApiContainer.get()
                    .logsFilesList()
                    .then((response) => {
                        this.files = response.data.data

                        const knownIds = new Set(this.files.map((file) => file.id))

                        this.selectedFileIds = this.selectedFileIds.filter((id) => knownIds.has(id))
                        this.filesLoaded = true

                        return response
                    })
                    .finally(() => {
                        this.filesLoading = false
                    })
            )
        },
        // The newest file of the first Laravel source, or nothing when there is none.
        selectDefaultFile() {
            const laravelFiles = this.files.filter((file) => file.type === 'laravel')

            if (!laravelFiles.length) {
                this.selectedFileIds = []

                return
            }

            const source = laravelFiles[0].source

            const newest = laravelFiles
                .filter((file) => file.source === source)
                .sort((a, b) => b.modified_at.localeCompare(a.modified_at))[0]

            this.selectedFileIds = [newest.id]
        },
        async findEntries(direction: DirectionEnum = DirectionEnum.Older, cursor: string | null = null) {
            this.stopRetry()

            if (!this.selectedFileIds.length) {
                this.page = null
                this.scanned = 0

                return
            }

            const requestNo = ++this.requestNo

            this.loading = true

            const page = await this.requestEntries(direction, cursor, requestNo)

            if (requestNo !== this.requestNo) {
                return
            }

            this.loading = false

            if (!page) {
                return
            }

            // The newest page asked for what came after it: keep it when nothing did.
            if (direction === DirectionEnum.Newer && cursor && !page.items.length && this.page) {
                ElMessage.info('No newer entries')

                return
            }

            this.page = page
            this.scanned = page.scanned
            this.lastCount = page.items.length
        },
        async continueSearch() {
            if (!this.page?.older_cursor) {
                return
            }

            this.stopRetry()

            const requestNo = ++this.requestNo

            this.continuing = true

            const page = await this.requestEntries(DirectionEnum.Older, this.page.older_cursor, requestNo)

            if (requestNo !== this.requestNo) {
                return
            }

            this.continuing = false

            if (!page || !this.page) {
                return
            }

            this.page = {
                ...page,
                items: [...this.page.items, ...page.items],
                newer_cursor: this.page.newer_cursor,
            }
            this.scanned += page.scanned
            this.lastCount = page.items.length
        },
        // Asks again while the files are being indexed; null when the request failed or
        // a newer one took its place.
        async requestEntries(
            direction: DirectionEnum,
            cursor: string | null,
            requestNo: number
        ): Promise<LogEntriesPage | null> {
            const body: EntriesBody = {
                files: this.selectedFileIds,
                direction: direction,
                per_page: PER_PAGE,
            }

            if (this.levels.length) {
                body.levels = this.levels
            }

            if (this.from) {
                body.from = this.from
            }

            if (this.to) {
                body.to = this.to
            }

            if (this.isSearch) {
                body.search_query = this.searchQuery.trim()
            }

            if (cursor) {
                body.cursor = cursor
            }

            const response = await handleApiRequest(
                () => ApiContainer.get().logsEntriesCreate(body)
            )

            if (requestNo !== this.requestNo || !response) {
                return null
            }

            const page = response.data.data

            if (!page.indexing) {
                return page
            }

            // The page shows how far indexing has got while it waits.
            this.page = {...page, items: this.page?.items ?? []}

            await new Promise<void>((resolve) => {
                this.retryTimerId = window.setTimeout(resolve, INDEXING_RETRY_MS)
            })

            if (requestNo !== this.requestNo) {
                return null
            }

            return this.requestEntries(direction, page.older_cursor ?? cursor, requestNo)
        },
        stopRetry() {
            if (this.retryTimerId !== null) {
                window.clearTimeout(this.retryTimerId)

                this.retryTimerId = null
            }
        },
        // True when the file is gone; the list is read again either way.
        async deleteFile(file: LogFile): Promise<boolean> {
            const response = await handleApiRequest(
                () => ApiContainer.get().logsFilesDelete(file.id)
            )

            await this.findFiles()

            return !!response
        },
        async downloadFile(file: LogFile) {
            try {
                const response = await ApiContainer.get().logsFilesDownloadList(file.id, {format: 'blob'})

                const url = URL.createObjectURL(response.data as Blob)

                const link = document.createElement('a')

                link.href = url
                link.download = file.name
                link.click()

                URL.revokeObjectURL(url)
            } catch (error: any) {
                // With the blob format the error body comes as a blob too.
                if (error?.error instanceof Blob) {
                    try {
                        error.error = JSON.parse(await error.error.text())
                    } catch {
                        error.error = null
                    }
                }

                handleApiError(error)
            }
        },
    },
})
